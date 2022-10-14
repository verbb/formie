<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\elements\db\NestedFieldRowQuery;
use verbb\formie\events\PruneSubmissionEvent;
use verbb\formie\events\SendNotificationEvent;
use verbb\formie\events\SubmissionEvent;
use verbb\formie\events\SubmissionSpamCheckEvent;
use verbb\formie\events\TriggerIntegrationEvent;
use verbb\formie\fields\formfields;
use verbb\formie\helpers\Variables;
use verbb\formie\jobs\SendNotification;
use verbb\formie\jobs\TriggerIntegration;
use verbb\formie\models\Address;
use verbb\formie\models\FakeElement;
use verbb\formie\models\FakeElementQuery;
use verbb\formie\models\IntegrationResponse;
use verbb\formie\models\Name;
use verbb\formie\models\Notification;
use verbb\formie\models\Settings;

use Craft;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\elements\Asset;
use craft\elements\User;
use craft\events\DefineUserContentSummaryEvent;
use craft\fields\data\MultiOptionsFieldData;
use craft\helpers\ArrayHelper;
use craft\helpers\Console;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\Queue;

use yii\base\Event;
use yii\base\Component;

use DateInterval;
use DateTime;
use Throwable;
use Exception;

use Faker;
use libphonenumber\PhoneNumberUtil;

class Submissions extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SUBMISSION = 'beforeSubmission';
    public const EVENT_BEFORE_INCOMPLETE_SUBMISSION = 'beforeIncompleteSubmission';
    public const EVENT_AFTER_SUBMISSION = 'afterSubmission';
    public const EVENT_AFTER_INCOMPLETE_SUBMISSION = 'afterIncompleteSubmission';
    public const EVENT_BEFORE_SPAM_CHECK = 'beforeSpamCheck';
    public const EVENT_AFTER_SPAM_CHECK = 'afterSpamCheck';
    public const EVENT_BEFORE_SEND_NOTIFICATION = 'beforeSendNotification';
    public const EVENT_BEFORE_TRIGGER_INTEGRATION = 'beforeTriggerIntegration';
    public const EVENT_AFTER_PRUNE_SUBMISSION = 'afterPruneSubmission';


    // Public Methods
    // =========================================================================

    /**
     * Returns a submission by its ID.
     *
     * @param int $id
     * @param string|null $siteId
     * @return Submission|null
     */
    public function getSubmissionById(int $id, ?string $siteId = '*'): ?Submission
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getElements()->getElementById($id, Submission::class, $siteId);
    }

    /**
     * Executed before a submission has been saved.
     *
     * @param Submission $submission
     * @param string $submitAction
     * @see SubmissionsController::actionSubmit()
     */
    public function onBeforeSubmission(Submission $submission, string $submitAction = 'submit'): void
    {
        $event = new SubmissionEvent([
            'submission' => $submission,
            'submitAction' => $submitAction,
        ]);

        if ($submission->isIncomplete) {
            // Fire an 'beforeIncompleteSubmission' event
            $this->trigger(self::EVENT_BEFORE_INCOMPLETE_SUBMISSION, $event);

            return;
        }

        // Fire an 'beforeSubmission' event
        $this->trigger(self::EVENT_BEFORE_SUBMISSION, $event);
    }

    /**
     * Executed after a submission has been saved.
     *
     * @param bool $success whether the submission was successful
     * @param Submission $submission
     * @param string $submitAction
     * @see SubmissionsController::actionSubmit()
     */
    public function onAfterSubmission(bool $success, Submission $submission, string $submitAction = 'submit'): void
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        // Check to see if this is an incomplete submission. Return immediately, but fire an event
        if ($submission->isIncomplete) {
            // Fire an 'afterIncompleteSubmission' event
            $event = new SubmissionEvent([
                'submission' => $submission,
                'submitAction' => $submitAction,
                'success' => $success,
            ]);

            // Default handled state, only for backward compatibility to return early
            $event->handled = true;

            $this->trigger(self::EVENT_AFTER_INCOMPLETE_SUBMISSION, $event);

            if ($event->handled) {
                return;
            }
        }

        // Check if the submission is spam
        if ($submission->isSpam) {
            $success = false;
        }

        // Trigger any payment integrations - but note these can fail
        if (!$this->processPayments($submission)) {
            $success = false;
        }

        // Fire an 'afterSubmission' event
        $event = new SubmissionEvent([
            'submission' => $submission,
            'submitAction' => $submitAction,
            'success' => $success,
        ]);
        $this->trigger(self::EVENT_AFTER_SUBMISSION, $event);

        if (!$submission->isIncomplete) {
            if ($event->success) {
                // Send off some emails, if all good!
                $this->sendNotifications($event->submission);

                // Trigger any integrations
                $this->triggerIntegrations($event->submission);
            } else if ($submission->isSpam && $settings->spamEmailNotifications) {
                // Special-case for wanting to send emails for spam
                $this->sendNotifications($event->submission);
            }
        }
    }

    /**
     * Sends enabled notifications for a submission.
     *
     * @param Submission $submission
     */
    public function sendNotifications(Submission $submission): void
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        // Get all enabled notifications, and push them to the queue for performance
        $form = $submission->getForm();
        $notifications = $form->getEnabledNotifications();

        foreach ($notifications as $notification) {
            // Evaluate conditions for each notification
            if (!Formie::$plugin->getNotifications()->evaluateConditions($notification, $submission)) {
                continue;
            }

            if ($settings->useQueueForNotifications) {
                Queue::push(new SendNotification([
                    'submissionId' => $submission->id,
                    'notificationId' => $notification->id,
                ]), $settings->queuePriority);
            } else {
                $this->sendNotificationEmail($notification, $submission);
            }
        }
    }

    /**
     * Sends a notification email. Normally called from the queue job.
     *
     * @param Notification $notification
     * @param Submission $submission
     * @param null $queueJob
     * @return array|bool
     */
    public function sendNotificationEmail(Notification $notification, Submission $submission, $queueJob = null): array|bool
    {
        // Fire a 'beforeSendNotification' event
        $event = new SendNotificationEvent([
            'submission' => $submission,
            'notification' => $notification,
        ]);
        $this->trigger(self::EVENT_BEFORE_SEND_NOTIFICATION, $event);

        if (!$event->isValid) {
            return true;
        }

        return Formie::$plugin->getEmails()->sendEmail($event->notification, $event->submission, $queueJob);
    }

    /**
     * Triggers any enabled integrations.
     *
     * @param Submission $submission
     */
    public function triggerIntegrations(Submission $submission): void
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        $form = $submission->getForm();

        $integrations = Formie::$plugin->getIntegrations()->getAllEnabledIntegrationsForForm($form);

        foreach ($integrations as $integration) {
            if (!$integration->supportsPayloadSending()) {
                continue;
            }

            // Add additional useful info for the integration
            // TODO: refactor this to allow integrations access to control this
            $integration->referrer = Craft::$app->getRequest()->getReferrer();
            $integration->ipAddress = Craft::$app->getRequest()->getUserIP();

            if ($settings->useQueueForIntegrations) {
                Queue::push(new TriggerIntegration([
                    'submissionId' => $submission->id,
                    'integration' => $integration,
                ]), $settings->queuePriority);
            } else {
                $this->sendIntegrationPayload($integration, $submission);
            }
        }
    }

    /**
     * Triggers an integration's payload to be sent. Normally called from the queue job.
     *
     * @param Integration $integration
     * @param Submission $submission
     * @return bool|IntegrationResponse
     */
    public function sendIntegrationPayload(Integration $integration, Submission $submission): bool|IntegrationResponse
    {
        // Fire a 'beforeTriggerIntegration' event
        $event = new TriggerIntegrationEvent([
            'submission' => $submission,
            'type' => get_class($integration),
            'integration' => $integration,
        ]);
        $this->trigger(self::EVENT_BEFORE_TRIGGER_INTEGRATION, $event);

        if (!$event->isValid) {
            return true;
        }

        return $integration->sendPayLoad($event->submission);
    }

    /**
     * Processes any payment fields for a submission.
     *
     * @param Submission $submission
     */
    public function processPayments(Submission $submission): bool
    {
        foreach ($submission->getFieldLayout()->getCustomFields() as $field) {
            if ($field instanceof formfields\Payment) {
                // No need to proceed further if field is conditionally hidden
                if ($field->isConditionallyHidden($submission)) {
                    return true;
                }
                
                if ($paymentIntegration = $field->getPaymentIntegration()) {
                    // Set the payment field on the integration, for ease-of-use
                    $paymentIntegration->setField($field);

                    if (!$paymentIntegration->processPayment($submission)) {
                        // Because payment processing happens after a submission has been completed, and an error has occurred,
                        // switch the submission back to incomplete and re-save to prevent it from completing normally.
                        $submission->isIncomplete = true;

                        Craft::$app->getElements()->saveElement($submission, false);

                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Deletes incomplete submissions older than the configured interval.
     */
    public function pruneIncompleteSubmissions($consoleInstance = null): void
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        if ($settings->maxIncompleteSubmissionAge <= 0) {
            return;
        }

        $interval = new DateInterval("P{$settings->maxIncompleteSubmissionAge}D");
        $date = new DateTime();
        $date->sub($interval);

        $submissions = Submission::find()
            ->isIncomplete(true)
            // Don't use `Db::prepareDateForDb()` because the `dateCreated` param will already do that
            ->dateUpdated('< ' . $date->format('Y-m-d H:i:s'))
            ->all();

        foreach ($submissions as $submission) {
            try {
                Craft::$app->getElements()->deleteElement($submission, true);
            } catch (Throwable $e) {
                Formie::error("Failed to prune submission with ID: #{$submission->id}." . $e->getMessage());
            }
        }

        // Also check for spam pruning
        if ($settings->saveSpam) {
            if ($settings->spamLimit <= 0) {
                return;
            }

            $submissions = Submission::find()
                ->limit(null)
                ->offset($settings->spamLimit)
                ->isSpam(true)
                ->orderBy(['dateCreated' => SORT_DESC])
                ->all();

            if ($submissions && $consoleInstance) {
                $consoleInstance->stdout('Preparing to prune ' . count($submissions) . ' submissions.' . PHP_EOL, Console::FG_YELLOW);
            }

            foreach ($submissions as $submission) {
                try {
                    Craft::$app->getElements()->deleteElement($submission, true);

                    if ($consoleInstance) {
                        $consoleInstance->stdout("Pruned spam submission with ID: #{$submission->id}." . PHP_EOL, Console::FG_GREEN);
                    }
                } catch (Throwable $e) {
                    Formie::error("Failed to prune spam submission with ID: #{$submission->id}." . $e->getMessage());

                    if ($consoleInstance) {
                        $consoleInstance->stdout("Failed to prune spam submission with ID: #{$submission->id}. " . $e->getMessage() . PHP_EOL, Console::FG_RED);
                    }
                }
            }
        }
    }

    /**
     * Deletes submissions older than the form data retention settings.
     */
    public function pruneDataRetentionSubmissions($consoleInstance = null): void
    {
        // Find all the forms with data retention settings
        $forms = (new Query())
            ->select(['id', 'handle', 'dataRetention', 'dataRetentionValue'])
            ->from(['{{%formie_forms}}'])
            ->where(['not', ['dataRetention' => 'forever']])
            ->all();

        foreach ($forms as $form) {
            $dataRetention = $form['dataRetention'] ?? '';
            $dataRetentionValue = (int)$form['dataRetentionValue'];

            if ($consoleInstance) {
                $consoleInstance->stdout(Craft::t('formie', 'Starting data retention checks for form “{f}”: {d} {c}.', [
                        'f' => $form['handle'],
                        'c' => $dataRetention,
                        'd' => $dataRetentionValue,
                    ]) . PHP_EOL, Console::FG_YELLOW);
            }

            // Setup intervals, depending on the setting
            $intervalLookup = ['minutes' => 'MIN', 'hours' => 'H', 'days' => 'D', 'weeks' => 'W', 'months' => 'M', 'years' => 'Y'];
            $intervalValue = $intervalLookup[$dataRetention] ?? '';

            if (!$intervalValue || !$dataRetentionValue) {
                continue;
            }

            // Handle weeks - not available built-in interval
            if ($intervalValue === 'W') {
                $intervalValue = 'D';
                $dataRetentionValue *= 7;
            }

            $period = ($intervalValue === 'H' || $intervalValue === 'MIN') ? 'PT' : 'P';

            if ($intervalValue === 'MIN') {
                $intervalValue = 'M';
            }

            $interval = new DateInterval("{$period}{$dataRetentionValue}{$intervalValue}");
            $date = new DateTime();
            $date->sub($interval);

            $submissions = Submission::find()
                // Don't use `Db::prepareDateForDb()` because the `dateCreated` param will already do that
                ->dateCreated('< ' . $date->format('Y-m-d H:i:s'))
                ->formId($form['id'])
                ->all();

            if ($consoleInstance) {
                if ($submissions) {
                    $consoleInstance->stdout(Craft::t('formie', 'Preparing to prune {c} submissions older than {d}.', [
                            'c' => count($submissions),
                            'd' => Db::prepareDateForDb($date),
                        ]) . PHP_EOL, Console::FG_YELLOW);
                } else {
                    $consoleInstance->stdout(Craft::t('formie', 'No submissions found to prune older than {d}.', [
                            'd' => Db::prepareDateForDb($date),
                        ]) . PHP_EOL, Console::FG_GREEN);
                }
            }

            foreach ($submissions as $submission) {
                try {
                    Craft::$app->getElements()->deleteElement($submission, true);

                    $event = new PruneSubmissionEvent([
                        'submission' => $submission,
                    ]);
                    $this->trigger(self::EVENT_AFTER_PRUNE_SUBMISSION, $event);

                    if ($consoleInstance) {
                        $consoleInstance->stdout("Pruned submission with ID: #{$submission->id}." . PHP_EOL, Console::FG_GREEN);
                    }
                } catch (Throwable $e) {
                    Formie::error("Failed to prune submission with ID: #{$submission->id}." . $e->getMessage());

                    if ($consoleInstance) {
                        $consoleInstance->stdout("Failed to prune submission with ID: #{$submission->id}. " . $e->getMessage() . PHP_EOL, Console::FG_RED);
                    }
                }
            }
        }
    }

    /**
     * Defining a summary of content owned by a user(s), before they are deleted.
     */
    public function defineUserSubmissions(DefineUserContentSummaryEvent $event): void
    {
        $userIds = Craft::$app->getRequest()->getRequiredBodyParam('userId');

        $submissionCount = Submission::find()
            ->userId($userIds)
            ->siteId('*')
            ->unique()
            ->status(null)
            ->count();

        if ($submissionCount) {
            $event->contentSummary[] = $submissionCount == 1 ? Craft::t('formie', '1 form submission') : Craft::t('formie', '{num} form submissions', ['num' => $submissionCount]);
        }
    }

    /**
     * Deletes any submissions related to a user.
     */
    public function deleteUserSubmissions(Event $event): void
    {
        /** @var User $user */
        $user = $event->sender;

        $submissions = Submission::find()
            ->userId($user->id)
            ->siteId('*')
            ->unique()
            ->status(null)
            ->all();

        if (!$submissions) {
            return;
        }

        // Are we transferring to another user, or just deleting?
        $inheritorOnDelete = $user->inheritorOnDelete ?? null;

        if ($inheritorOnDelete) {
            // Re-assign each submission to the new user
            Craft::$app->getDb()->createCommand()
                ->update('{{%formie_submissions}}', ['userId' => $inheritorOnDelete->id], ['userId' => $user->id])
                ->execute();
        } else {
            // We just want to delete each submission - bye!
            foreach ($submissions as $submission) {
                try {
                    Craft::$app->getElements()->deleteElement($submission);
                } catch (Throwable $e) {
                    Formie::error("Failed to delete user submission with ID: #{$submission->id}." . $e->getMessage());
                }
            }
        }
    }

    /**
     * Restores any submissions related to a user.
     */
    public function restoreUserSubmissions(Event $event): void
    {
        /** @var User $user */
        $user = $event->sender;

        $submissions = Submission::find()
            ->userId($user->id)
            ->siteId('*')
            ->unique()
            ->status(null)
            ->trashed(true)
            ->all();

        foreach ($submissions as $submission) {
            try {
                Craft::$app->getElements()->restoreElement($submission);
            } catch (Throwable $e) {
                Formie::error("Failed to restore user submission with ID: #{$submission->id}." . $e->getMessage());
            }
        }
    }

    /**
     * Performs spam checks on a submission.
     *
     * @param Submission $submission
     * @throws Exception
     */
    public function spamChecks(Submission $submission): void
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        // Fire an 'beforeSpamCheck' event
        $event = new SubmissionSpamCheckEvent([
            'submission' => $submission,
        ]);
        $this->trigger(self::EVENT_BEFORE_SPAM_CHECK, $event);

        // Is it already spam?
        if (!$submission->isSpam) {
            $excludes = $this->_getArrayFromMultiline($settings->spamKeywords);
            $extraExcludes = [];

            // Handle any Twig used in the field
            foreach ($excludes as $key => $exclude) {
                if (str_contains($exclude, '{')) {
                    unset($excludes[$key]);

                    $parsedString = $this->_getArrayFromMultiline(Variables::getParsedValue($exclude));
                    $extraExcludes[] = $parsedString;
                }
            }

            // For performance
            $excludes = array_merge($excludes, ...$extraExcludes);

            // Build a string based on field content - much easier to find values
            // in a single string than iterate through multiple arrays
            $fieldValues = $this->_getContentAsString($submission);

            foreach ($excludes as $exclude) {
                // Check if string contains
                if (strtolower($exclude) && str_contains(strtolower($fieldValues), strtolower($exclude))) {
                    $submission->isSpam = true;
                    $submission->spamReason = Craft::t('formie', 'Contains banned keyword: “{c}”', ['c' => $exclude]);

                    break;
                }

                // Check for IPs
                if ($submission->ipAddress && $submission->ipAddress === $exclude) {
                    $submission->isSpam = true;
                    $submission->spamReason = Craft::t('formie', 'Contains banned IP: “{c}”', ['c' => $exclude]);

                    break;
                }
            }
        }

        // Fire an 'afterSpamCheck' event
        $event = new SubmissionSpamCheckEvent([
            'submission' => $submission,
        ]);
        $this->trigger(self::EVENT_AFTER_SPAM_CHECK, $event);
    }

    /**
     * Logs spam to the Formie log.
     *
     * @param Submission $submission
     */
    public function logSpam(Submission $submission): void
    {
        $fieldValues = $submission->getSerializedFieldValues();
        $fieldValues = array_filter($fieldValues);

        $error = Craft::t('formie', 'Submission marked as spam - “{r}” - {j}.', [
            'r' => $submission->spamReason,
            'j' => Json::encode($fieldValues),
        ]);

        Formie::log($error);
    }

    public function populateFakeSubmission(Submission $submission): void
    {
        $fields = $submission->getFieldLayout()->getCustomFields();
        $fieldContent = $this->getFakeFieldContent($fields);

        $submission->setFieldValues($fieldContent);

        // Set some submission attributes as well
        $submission->id = '1234';
        $submission->dateCreated = new DateTime();
    }


    // Private Methods
    // =========================================================================

    public function getFakeFieldContent($fields): array
    {
        $fieldContent = [];

        $faker = Faker\Factory::create();

        foreach ($fields as $key => $field) {
            switch (get_class($field)) {
                case formfields\Categories::class:
                case formfields\Entries::class:
                case formfields\Products::class:
                case formfields\Tags::class:
                case formfields\Users::class:
                case formfields\Variants::class:
                    $query = $field->getElementsQuery()->orderBy('RAND()');

                    // Check if we should limit to 1 if a (single) dropdown or radio
                    if ($field->displayType === 'radio' || ($field->displayType === 'dropdown' && !$field->multiple)) {
                        $query->limit(1);
                    }

                    $fieldContent[$field->handle] = $query;

                    break;
                case formfields\Address::class:
                    $fieldContent[$field->handle] = new Address([
                        'address1' => $faker->address,
                        'address2' => $faker->buildingNumber,
                        'address3' => $faker->streetSuffix,
                        'city' => $faker->city,
                        'zip' => $faker->postcode,
                        'state' => $faker->state,
                        'country' => $faker->country,
                    ]);

                    break;
                case formfields\Checkboxes::class:
                    $values = $faker->randomElement($field->options)['value'] ?? '';
                    $fieldContent[$field->handle] = [$values];

                    break;
                case formfields\Date::class:
                    $fieldContent[$field->handle] = $faker->dateTime();

                    break;
                case formfields\Dropdown::class:
                    $values = $faker->randomElement($field->options)['value'] ?? '';

                    if ($field->multi) {
                        $values = [$values];
                    }

                    $fieldContent[$field->handle] = $values;

                    break;
                case formfields\Email::class:
                    $fieldContent[$field->handle] = $faker->email;

                    break;
                case formfields\FileUpload::class:
                    $fieldContent[$field->handle] = Asset::find()->limit(1);

                    break;
                case formfields\Group::class:
                case formfields\Repeater::class:
                    // Create a fake object to query. Maybe one day I'll figure out how to generate a fake elementQuery.
                    // The fields rely on a NestedRowQuery for use in emails, so we need some similar.
                    $query = new FakeElementQuery(FakeElement::class);

                    if ($fieldLayout = $field->getFieldLayout()) {
                        $content = $this->getFakeFieldContent($fieldLayout->getCustomFields());
                        $query->setFieldValues($content, $fieldLayout);
                    }

                    $fieldContent[$field->handle] = $query;

                    break;
                case formfields\MultiLineText::class:
                    $fieldContent[$field->handle] = $faker->realText;

                    break;
                case formfields\Name::class:
                    if ($field->useMultipleFields) {
                        $fieldContent[$field->handle] = new Name([
                            'prefix' => $faker->title,
                            'firstName' => $faker->firstName,
                            'middleName' => $faker->firstName,
                            'lastName' => $faker->lastName,
                        ]);
                    } else {
                        $fieldContent[$field->handle] = $faker->name;
                    }

                    break;
                case formfields\Number::class:
                    $fieldContent[$field->handle] = $faker->randomDigit;

                    break;
                case formfields\Phone::class:
                    if ($field->countryEnabled) {
                        $number = $faker->e164PhoneNumber;

                        $phoneUtil = PhoneNumberUtil::getInstance();
                        $numberProto = $phoneUtil->parse($number);

                        $fieldContent[$field->handle] = new \verbb\formie\models\Phone([
                            'number' => $number,
                            'country' => $phoneUtil->getRegionCodeForNumber($numberProto),
                        ]);
                    } else {
                        $fieldContent[$field->handle] = $faker->phoneNumber;
                    }

                    break;
                case formfields\Radio::class:
                    $fieldContent[$field->handle] = $faker->randomElement($field->options)['value'] ?? '';

                    break;
                case formfields\Recipients::class:
                    if ($field->displayType === 'checkboxes') {
                        $values = $faker->randomElement($field->options)['value'] ?? '';
                        $fieldContent[$field->handle] = [$values];
                    } else if ($field->displayType === 'dropdown' || $field->displayType === 'radio') {
                        $fieldContent[$field->handle] = $faker->randomElement($field->options)['value'] ?? '';
                    } else if ($field->displayType === 'hidden') {
                        $fieldContent[$field->handle] = $faker->email;
                    }

                    break;
                default:
                    $fieldContent[$field->handle] = $faker->text;

                    break;
            }
        }

        return $fieldContent;
    }

    public function getEditableSubmissions(User $currentUser): array
    {
        $editableIds = [];
        $submissions = [];
        $editableIds = [];

        // Fetch all submission UIDs
        $formInfo = (new Query())
            ->from('{{%formie_forms}}')
            ->select(['id', 'uid'])
            ->all();

        // Can the user edit _every_ submission?
        if ($currentUser->can('formie-editSubmissions')) {
            $editableIds = ArrayHelper::getColumn($formInfo, 'id');
        } else {
            // Find all UIDs the user has permission to
            foreach ($formInfo as $form) {
                if ($currentUser->can('formie-manageSubmission:' . $form['uid'])) {
                    $editableIds[] = $form['id'];
                }
            }
        }

        if ($editableIds) {
            $forms = Form::find()->id($editableIds)->trashed(null)->all();

            foreach ($forms as $form) {
                $submissions[] = [
                    'id' => (int)$form->id,
                    'handle' => $form->handle,
                    'name' => Craft::t('formie', $form->title),
                    'sites' => Craft::$app->getSites()->getAllSiteIds(),
                    'uid' => $form->uid,
                ];
            }
        }

        return $submissions;
    }

    /**
     * Converts a multiline string to an array.
     *
     * @param $string
     * @return array
     */
    private function _getArrayFromMultiline($string): array
    {
        $array = [];

        if ($string) {
            $array = array_map('trim', explode(PHP_EOL, $string));
        }

        return array_filter($array);
    }

    /**
     * Converts a field value to a string.
     *
     * @param $submission
     * @return string
     */
    private function _getContentAsString($submission): string
    {
        $fieldValues = [];

        if (($fieldLayout = $submission->getFieldLayout()) !== null) {
            foreach ($fieldLayout->getCustomFields() as $field) {
                try {
                    $value = $submission->getFieldValue($field->handle);

                    if ($value instanceof NestedFieldRowQuery) {
                        $values = [];

                        foreach ($value->all() as $row) {
                            $fieldValues[] = $this->_getContentAsString($row);
                        }

                        continue;
                    }

                    if ($value instanceof ElementQuery) {
                        $value = $value->one();
                    }

                    if ($value instanceof MultiOptionsFieldData) {
                        $value = implode(' ', array_map(function($item) {
                            return $item->value;
                        }, (array)$value));
                    }

                    $fieldValues[] = (string)$value;
                } catch (Throwable $e) {
                    continue;
                }
            }
        }

        return implode(' ', $fieldValues);
    }
}
