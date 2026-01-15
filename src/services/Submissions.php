<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\Integration;
use verbb\formie\base\SingleNestedFieldInterface;
use verbb\formie\base\SubFieldInterface;
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\elements\db\SubmissionQuery;
use verbb\formie\events\PruneSubmissionEvent;
use verbb\formie\events\SendNotificationEvent;
use verbb\formie\events\SubmissionEvent;
use verbb\formie\events\SubmissionSpamCheckEvent;
use verbb\formie\events\TriggerIntegrationEvent;
use verbb\formie\fields\data\MultiOptionsFieldData;
use verbb\formie\fields as formiefields;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SpamHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\Variables;
use verbb\formie\jobs\SendNotification;
use verbb\formie\jobs\TriggerIntegration;
use verbb\formie\jobs\UpdateSubmissionContent;
use verbb\formie\models\Address;
use verbb\formie\models\IntegrationResponse;
use verbb\formie\models\Name;
use verbb\formie\models\Notification;
use verbb\formie\models\Settings;

use Craft;
use craft\base\PreviewableFieldInterface;
use craft\base\SortableFieldInterface;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\elements\Asset;
use craft\elements\User;
use craft\events\DefineSourceSortOptionsEvent;
use craft\events\DefineSourceTableAttributesEvent;
use craft\events\DefineUserContentSummaryEvent;
use craft\events\IndexKeywordsEvent;
use craft\events\SearchEvent;
use craft\helpers\App;
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

    public function getSubmissionById(int $id, ?string $siteId = '*', array $criteria = []): ?Submission
    {
        // Always included incomplete or spam submissions
        $criteria['isIncomplete'] = null;
        $criteria['isSpam'] = null;

        return Craft::$app->getElements()->getElementById($id, Submission::class, $siteId, $criteria);
    }

    public function defineSourceTableAttributes(DefineSourceTableAttributesEvent $event): void
    {
        if ($event->elementType !== Submission::class) {
            return;
        }

        if (preg_match('/^form:(\d+)$/', $event->source, $matches) && ($fields = Formie::$plugin->getFields()->getAllFieldsForForm($matches[1]))) {
            foreach ($fields as $field) {
                if ($field instanceof PreviewableFieldInterface) {
                    $event->attributes["field:{$field->handle}"] = ['label' => $field->label];
                }
            }
        }
    }

    public function defineSourceSortOptions(DefineSourceSortOptionsEvent $event): void
    {
        if ($event->elementType !== Submission::class) {
            return;
        }

        if (preg_match('/^form:(\d+)$/', $event->source, $matches) && ($fields = Formie::$plugin->getFields()->getAllFieldsForForm($matches[1]))) {
            foreach ($fields as $field) {
                if ($field instanceof SortableFieldInterface) {
                    $event->sortOptions["field:{$field->handle}"] = $field->getSortOption();
                }
            }
        }
    }

    public function beforeIndexKeywords(IndexKeywordsEvent $event)
    {
        if (!($event->element instanceof Submission)) {
            return;
        }

        if (!$event->element->hasSearchIndexAttribute($event->attribute)) {
            $event->isValid = false;
        }
    }

    public function beforeSearch(SearchEvent $event)
    {
        if (!($event->elementQuery instanceof SubmissionQuery)) {
            return;
        }
    }

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
        if ($success && !$this->processPayments($submission)) {
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

    public function sendNotifications(Submission $submission): void
    {
        // Get all enabled notifications, and push them to the queue for performance
        $form = $submission->getForm();
        $notifications = $form->getEnabledNotifications();

        foreach ($notifications as $notification) {
            $this->sendNotification($notification, $submission);
        }
    }

    public function sendNotification(Notification $notification, Submission $submission): void
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        // Evaluate conditions for each notification
        if (!Formie::$plugin->getNotifications()->evaluateConditions($notification, $submission)) {
            return;
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

            // Allow integrations to add extra data before running
            $integration->populateContext();

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

    public function processPayments(Submission $submission): bool
    {
        foreach ($submission->getFields() as $field) {
            if ($field instanceof formiefields\Payment) {
                // No need to proceed further if field is conditionally hidden or disabled
                if ($field->isConditionallyHidden($submission) || $field->getIsDisabled()) {
                    continue;
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

    public function pruneIncompleteSubmissions($consoleInstance = null): void
    {
        App::maxPowerCaptain();

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
                $success = Craft::$app->getElements()->deleteElement($submission, true);

                if ($consoleInstance) {
                    if ($success) {
                        $consoleInstance->stdout("Pruned incomplete submission with ID: #{$submission->id}." . PHP_EOL, Console::FG_GREEN);
                    } else {
                        throw new Exception(Json::encode($submission->getErrors()));
                    }
                }
            } catch (Throwable $e) {
                Formie::error("Failed to prune incomplete submission with ID: #{$submission->id}." . $e->getMessage());

                if ($consoleInstance) {
                    $consoleInstance->stdout("Failed to prune incomplete submission with ID: #{$submission->id}. " . $e->getMessage() . PHP_EOL, Console::FG_RED);
                }
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
                    $success = Craft::$app->getElements()->deleteElement($submission, true);

                    if ($consoleInstance) {
                        if ($success) {
                            $consoleInstance->stdout("Pruned spam submission with ID: #{$submission->id}." . PHP_EOL, Console::FG_GREEN);
                        } else {
                            throw new Exception(Json::encode($submission->getErrors()));
                        }
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

    public function pruneDataRetentionSubmissions($consoleInstance = null): void
    {
        App::maxPowerCaptain();

        // Find all the forms with data retention settings
        $forms = (new Query())
            ->select(['id', 'handle', 'dataRetention', 'dataRetentionValue'])
            ->from([Table::FORMIE_FORMS])
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
                    $success = Craft::$app->getElements()->deleteElement($submission, true);

                    $event = new PruneSubmissionEvent([
                        'submission' => $submission,
                    ]);
                    $this->trigger(self::EVENT_AFTER_PRUNE_SUBMISSION, $event);

                    if ($consoleInstance) {
                        if ($success) {
                            $consoleInstance->stdout("Pruned submission with ID: #{$submission->id}." . PHP_EOL, Console::FG_GREEN);
                        } else {
                            throw new Exception(Json::encode($submission->getErrors()));
                        }
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

    public function defineUserSubmissions(DefineUserContentSummaryEvent $event): void
    {
        App::maxPowerCaptain();

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

    public function deleteUserSubmissions(Event $event): void
    {
        App::maxPowerCaptain();

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
            Db::update(Table::FORMIE_SUBMISSIONS, ['userId' => $inheritorOnDelete->id], ['userId' => $user->id]);
        } else {
            // We just want to delete each submission - bye!
            foreach ($submissions as $submission) {
                try {
                    $success = Craft::$app->getElements()->deleteElement($submission);
                } catch (Throwable $e) {
                    Formie::error("Failed to delete user submission with ID: #{$submission->id}." . $e->getMessage());
                }
            }
        }
    }

    public function restoreUserSubmissions(Event $event): void
    {
        App::maxPowerCaptain();
        
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
            // Start evaluating keyword rules
            $fieldValues = implode(' ', $submission->getValuesAsString());
            $spamEvaluation = SpamHelper::checkContent($fieldValues);

            if ($spamEvaluation) {
                if ($spamEvaluation['type'] === 'text') {
                    $submission->isSpam = true;
                    $submission->spamReason = Craft::t('formie', 'Contains banned keyword: “{c}”', ['c' => $spamEvaluation['value']]);
                }

                if ($spamEvaluation['type'] === 'ip') {
                    $submission->isSpam = true;
                    $submission->spamReason = Craft::t('formie', 'Contains banned IP: “{c}”', ['c' => $spamEvaluation['value']]);
                }
            }
        }

        // Fire an 'afterSpamCheck' event
        $event = new SubmissionSpamCheckEvent([
            'submission' => $submission,
        ]);
        $this->trigger(self::EVENT_AFTER_SPAM_CHECK, $event);
    }

    public function logSpam(Submission $submission): void
    {
        $fieldValues = $submission->getSerializedFieldValues();
        $fieldValues = array_filter($fieldValues);

        $error = Craft::t('formie', 'Submission marked as spam - “{r}” - {j}.', [
            'r' => $submission->spamReason,
            'j' => Json::encode($fieldValues),
        ]);

        Formie::info($error);
    }

    public function populateFakeSubmission(Submission $submission): void
    {
        $fields = $submission->getFields();
        $fieldContent = $this->getFakeFieldContent($fields);

        $submission->setFieldValues($fieldContent);

        // Set some submission attributes as well
        $submission->id = '1234';
        $submission->uid = StringHelper::UUID();
        $submission->dateCreated = new DateTime();
        $submission->setUser(User::find()->one());
    }

    public function updateSubmissionContent(Form $form): void
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        // Check if we've moved fields in or our of Group fields. Their content needs to be re-arranged.
        // More performant if we don't spin up the queue job unless we need to
        $hasGroupField = array_filter($form->getFields(), function($field) {
            return $field instanceof formiefields\Group;
        });

        if ($hasGroupField) {
            Queue::push(new UpdateSubmissionContent([
                'formId' => $form->id,
            ]), $settings->queuePriority);
        }
    }


    // Private Methods
    // =========================================================================

    public function getFakeFieldContent(array $fields): array
    {
        $fieldContent = [];

        $faker = Faker\Factory::create();

        foreach ($fields as $key => $field) {
            $fieldContent[$field->handle] = $field->getValueForEmailPreview($faker);
        }

        return $fieldContent;
    }

    private function _getArrayFromMultiline(?string $string): array
    {
        $array = [];

        if ($string) {
            $array = array_map('trim', explode(PHP_EOL, $string));
        }

        return array_filter($array);
    }
}
