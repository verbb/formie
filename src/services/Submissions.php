<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\Element;
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\elements\NestedFieldRow;
use verbb\formie\elements\Submission;
use verbb\formie\elements\db\NestedFieldRowQuery;
use verbb\formie\events\SubmissionEvent;
use verbb\formie\events\SendNotificationEvent;
use verbb\formie\events\TriggerIntegrationEvent;
use verbb\formie\fields\formfields;
use verbb\formie\jobs\SendNotification;
use verbb\formie\jobs\TriggerIntegration;
use verbb\formie\models\FakeElement;
use verbb\formie\models\FakeElementQuery;
use verbb\formie\models\Settings;

use Craft;
use craft\helpers\Json;

use yii\base\Component;
use DateInterval;
use DateTime;
use Throwable;
use Faker;

class Submissions extends Component
{
    // Constants
    // =========================================================================

    const EVENT_AFTER_SUBMISSION = 'afterSubmission';
    const EVENT_BEFORE_SEND_NOTIFICATION = 'beforeSendNotification';
    const EVENT_BEFORE_TRIGGER_INTEGRATION = 'beforeTriggerIntegration';


    // Public Methods
    // =========================================================================

    /**
     * Returns a submission by it's ID.
     *
     * @param $submissionId
     * @param null $siteId
     * @return Submission|null
     */
    public function getSubmissionById($submissionId, $siteId = '*')
    {
        /* @var Submission $submission */
        $submission = Craft::$app->getElements()->getElementById($submissionId, Submission::class, $siteId);
        return $submission;
    }

    /**
     * Executed after a submission has been saved.
     *
     * @param bool $success whether the submission was successful
     * @param Submission $submission
     * @see SubmissionsController::actionSubmit()
     */
    public function onAfterSubmission(bool $success, Submission $submission)
    {
        // Check if the submission is spam
        if ($submission->isSpam) {
            $success = false;
        }

        // Fire an 'afterSubmission' event
        $event = new SubmissionEvent([
            'submission' => $submission,
            'success' => $success,
        ]);
        $this->trigger(self::EVENT_AFTER_SUBMISSION, $event);

        if ($event->success) {
            // Send off some emails, if all good!
            $this->sendNotifications($event->submission);

            // Trigger any integrations
            $this->triggerIntegrations($event->submission);
        }
    }

    /**
     * Sends enabled notifications for a submission.
     *
     * @param Submission $submission
     */
    public function sendNotifications(Submission $submission)
    {
        $settings = Formie::$plugin->getSettings();

        // Get all enabled notifications, and push them to the queue for performance
        $form = $submission->getForm();
        $notifications = $form->getEnabledNotifications();

        foreach ($notifications as $notification) {
            if ($settings->useQueueForNotifications) {
                Craft::$app->getQueue()->push(new SendNotification([
                    'submissionId' => $submission->id,
                    'notificationId' => $notification->id,
                ]));
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
     */
    public function sendNotificationEmail($notification, $submission)
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

        return Formie::$plugin->getEmails()->sendEmail($event->notification, $event->submission);
    }

    /**
     * Triggers any enabled integrations.
     *
     * @param Submission $submission
     */
    public function triggerIntegrations(Submission $submission)
    {
        $settings = Formie::$plugin->getSettings();

        $form = $submission->getForm();

        $integrations = Formie::$plugin->getIntegrations()->getAllEnabledIntegrationsForForm($form);

        foreach ($integrations as $integration) {
            if (!$integration->supportsPayloadSending()) {
                continue;
            }

            // Add additional useful info for the integration
            $integration->referrer = Craft::$app->getRequest()->getReferrer();

            if ($settings->useQueueForIntegrations) {
                Craft::$app->getQueue()->push(new TriggerIntegration([
                    'submissionId' => $submission->id,
                    'integration' => $integration,
                ]));
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
     */
    public function sendIntegrationPayload($integration, Submission $submission)
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
     * Deletes incomplete submissions older than the configured interval.
     */
    public function pruneSubmissions()
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
            ->dateUpdated('< ' . $date->format('c'))
            ->all();

        foreach ($submissions as $submission) {
            try {
                Craft::$app->getElements()->deleteElement($submission, true);
            } catch (Throwable $e) {
                Formie::error('Failed to prune submission with ID: ' . $submission->id);
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

            foreach ($submissions as $submission) {
                try {
                    Craft::$app->getElements()->deleteElement($submission, true);
                } catch (Throwable $e) {
                    Formie::error('Failed to prune spam submission with ID: ' . $submission->id);
                }
            }
        }
    }

    /**
     * Performs spam checks on a submission.
     *
     * @param Submission $submission
     */
    public function spamChecks(Submission $submission)
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        // Is it already spam? Return
        if ($submission->isSpam) {
            return;
        }

        $excludes = $this->_getArrayFromMultiline($settings->spamKeywords);

        // Build a string based on field content - much easier to find values
        // in a single string than iterate through multiple arrays
        $fieldValues = $this->_getContentAsString($submission);

        foreach ($excludes as $exclude) {
            // Check if string contains
            if (strstr($fieldValues, strtolower($exclude))) {
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

    /**
     * Logs spam to the Formie log.
     *
     * @param Submission $submission
     */
    public function logSpam(Submission $submission)
    {
        $fieldValues = $submission->getSerializedFieldValues();
        $fieldValues = array_filter($fieldValues);

        $error = Craft::t('formie', 'Submission marked as spam - “{r}” - {j}.', [
            'r' => $submission->spamReason,
            'j' => Json::encode($fieldValues),
        ]);

        Formie::log($error);
    }

    /**
     * @inheritdoc
     */
    public function populateFakeSubmission(Submission $submission)
    {
        $fields = $submission->getFieldLayout()->getFields();
        $fieldContent = [];

        $fieldContent = $this->_getFakeFieldContent($fields);

        $submission->setFieldValues($fieldContent);
    }


    // Private Methods
    // =========================================================================

    /**
     * Converts a multiline string to an array.
     *
     * @param $string
     * @return array
     */
    private function _getArrayFromMultiline($string)
    {
        $array = [];

        if ($string) {
            $array = array_map('trim', explode(PHP_EOL, $string));
        }

        return $array;
    }

    /**
     * Converts a field value to a string.
     *
     * @param $submission
     * @return string
     */
    private function _getContentAsString($submission)
    {
        $fieldValues = [];

        foreach ($submission->getSerializedFieldValues() as $fieldValue) {
            // TODO: handle array values (repeater fields).
            if (!is_array($fieldValue) && (string)$fieldValue) {
                $fieldValues[] = (string)$fieldValue;
            }
        }

        return implode(' ', $fieldValues);
    }

    /**
     * @inheritdoc
     */
    public function _getFakeFieldContent($fields)
    {
        $fieldContent = [];

        $faker = Faker\Factory::create();

        foreach ($fields as $key => $field) {
            switch (get_class($field)) {
                case formfields\Address::class:
                    $fieldContent[$field->handle]['address1'] = $faker->address;
                    $fieldContent[$field->handle]['address2'] = $faker->buildingNumber;
                    $fieldContent[$field->handle]['address3'] = $faker->streetSuffix;
                    $fieldContent[$field->handle]['city'] = $faker->city;
                    $fieldContent[$field->handle]['zip'] = $faker->postcode;
                    $fieldContent[$field->handle]['state'] = $faker->state;
                    $fieldContent[$field->handle]['country'] = $faker->country;

                    break;
                case formfields\Checkboxes::class:
                    $values = $faker->randomElement($field->options)['value'] ?? '';
                    $fieldContent[$field->handle] = [$values];

                    break;
                case formfields\Date::class:
                    $fieldContent[$field->handle] = $faker->iso8601;

                    break;
                case formfields\Dropdown::class:
                    $fieldContent[$field->handle] = $faker->randomElement($field->options)['value'] ?? '';

                    break;
                case formfields\Email::class:
                    $fieldContent[$field->handle] = $faker->email;

                    break;
                case formfields\Group::class:
                    // Create a fake object to query. Maybe one day I'll figure out how to generate a fake elementQuery.
                    // The fields rely on a NestedRowQuery for use in emails, so we need some similar.
                    $query = new FakeElementQuery(FakeElement::class);

                    if ($fieldLayout = $field->getFieldLayout()) {
                        $content = $this->_getFakeFieldContent($fieldLayout->getFields());
                        $query->setFieldValues($content);
                    }

                    $fieldContent[$field->handle] = $query;

                    break;
                case formfields\MultiLineText::class:
                    $fieldContent[$field->handle] = $faker->realText;

                    break;
                case formfields\Name::class:
                    if ($field->useMultipleFields) {
                        $fieldContent[$field->handle]['prefix'] = $faker->title;
                        $fieldContent[$field->handle]['firstName'] = $faker->firstName;
                        $fieldContent[$field->handle]['middleName'] = $faker->firstName;
                        $fieldContent[$field->handle]['lastName'] = $faker->lastName;
                    } else {
                        $fieldContent[$field->handle] = $faker->name;
                    }

                    break;
                case formfields\Number::class:
                    $fieldContent[$field->handle] = $faker->randomDigit;

                    break;
                case formfields\Phone::class:
                    $fieldContent[$field->handle] = $faker->phoneNumber;

                    break;
                case formfields\Radio::class:
                    $fieldContent[$field->handle] = $faker->randomElement($field->options)['value'] ?? '';

                    break;
                case formfields\Recipients::class:
                    $fieldContent[$field->handle] = $faker->email;

                    break;                    
                default:
                    $fieldContent[$field->handle] = $faker->text;

                    break;
            }
        }

        return $fieldContent;
    }
}
