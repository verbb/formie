<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\QueueJobDataHelper;
use verbb\formie\models\Notification;

use Craft;
use craft\helpers\Json;
use craft\queue\BaseJob as CraftBaseJob;

use Exception;

class SendNotification extends CraftBaseJob implements DebuggableJobInterface
{
    // Traits
    // =========================================================================

    use DebuggableJobTrait;


    // Properties
    // =========================================================================

    public ?int $submissionId = null;
    public ?string $executionUid = null;
    public ?int $notificationId = null;
    public array $submissionData = [];
    public array $notificationData = [];
    public array $referenceMap = [];
    public mixed $email = null;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();
        $this->executionUid ??= \craft\helpers\StringHelper::UUID();
    }

    public function execute($queue): void
    {
        $this->setProgress($queue, 0.25);

        $notification = Formie::$plugin->getNotifications()->getNotificationById($this->notificationId);

        // Be sure to fetch spam submissions too, if we have Formie set to email those
        $submission = Submission::find()->id($this->submissionId)->isSpam(null)->one();

        if (!$notification) {
            throw new Exception('Unable to find notification: ' . $this->notificationId . '.');
        }

        if (!$submission) {
            throw new Exception('Unable to find submission: ' . $this->submissionId . '.');
        }

        $this->setProgress($queue, 0.5);

        // Ensure we set the correct language for a potential CLI request
        Craft::$app->language = $submission->getSite()->language;
        Craft::$app->set('locale', Craft::$app->getI18n()->getLocaleById($submission->getSite()->language));
        Craft::$app->getSites()->setCurrentSite($submission->getSite());

        // Store some context to the queue job description
        $this->submissionData = $this->_getSubmissionData($submission);
        $this->notificationData = $this->_getNotificationData($notification);
        $this->referenceMap = $this->_getReferenceMap($submission);

        $this->setProgress($queue, 0.75);

        $sentResponse = Formie::$plugin->getNotifications()->sendNotificationEmail($notification, $submission, $this, $this->_executionIdentity($queue));
        $success = $sentResponse === true || ($sentResponse['success'] ?? false);
        $error = $sentResponse['error'] ?? false;

        if (!$success) {
            // Check if we should send the nominated admin(s) an email about this error.
            Formie::$plugin->getEmails()->sendFailAlertEmail($notification, $submission, $sentResponse);

            throw new Exception('Failed to send notification email: ' . Json::encode($sentResponse) . '.');
        }

        $this->setProgress($queue, 1);
    }


    // Protected Methods
    // =========================================================================

    protected function defaultDescription(): string
    {
        return Craft::t('formie', 'Sending form notification.');
    }

    protected function updateDebugJobData(mixed $job, mixed $jobData): void
    {
        $notification = Formie::$plugin->getNotifications()->getNotificationById($this->notificationId);

        if ($notification) {
            $jobData->notificationData = $this->_getNotificationData($notification);
        }

        // Be sure to fetch spam submissions too, if we have Formie set to email those
        $submission = Submission::find()->id($this->submissionId)->isSpam(null)->one();

        if ($submission) {
            // Don't use the full submission class as an array, which can cause infinite loops
            // when used with dynamic variables in Hidden fields.
            $jobData->submissionData = $this->_getSubmissionData($submission);
            $jobData->referenceMap = $this->_getReferenceMap($submission);
        }

        $jobData->email = $job->email;
    }


    // Private Methods
    // =========================================================================

    private function _getNotificationData(Notification $notification): array
    {
        $notificationData = $notification->toArray();
        $notificationData['content'] = $notification->getParsedContent();

        return QueueJobDataHelper::sanitizeForSerialization($notificationData);
    }

    private function _getSubmissionData(Submission $submission): array
    {
        $submissionData = $submission->toArray([
            'id',
            'status',
            'userId',
            'ipAddress',
            'isIncomplete',
            'isSpam',
            'spamReason',
            'spamClass',
            'snapshot',
        ]);

        $submissionData['form'] = $submission->getFormHandle();
        $submissionData['fields'] = QueueJobDataHelper::sanitizeForSerialization($submission->getValuesAsArray());

        return QueueJobDataHelper::sanitizeForSerialization($submissionData);
    }

    private function _getReferenceMap(Submission $submission): array
    {
        $fields = [];

        foreach ($submission->getFields() as $field) {
            $reference = trim((string)($field->reference ?? ''));

            if ($reference === '') {
                continue;
            }

            $fields[$reference] = [
                'fieldId' => $field->fieldId ?? null,
                'uid' => $field->uid ?? null,
                'handle' => $field->handle ?? null,
                'label' => $field->label ?? null,
                'type' => get_class($field),
            ];
        }

        return [
            'fields' => $fields,
        ];
    }

    private function _executionIdentity($queue): string
    {
        if ($this->executionUid) {
            return $this->executionUid;
        }
        // Older serialized jobs predate executionUid. Craft's queue row provides
        // a stable fallback; never generate a new identity while retrying a job.
        if (method_exists($queue, 'getJobId') && ($id = $queue->getJobId())) {
            return 'queue:' . $id;
        }
        throw new \RuntimeException('Unable to identify this delivery attempt. Check its outcome before explicitly sending again.');
    }

}
