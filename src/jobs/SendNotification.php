<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\models\Notification;

use Craft;
use craft\helpers\Json;
use Exception;

class SendNotification extends BaseJob
{
    // Properties
    // =========================================================================

    public ?int $submissionId = null;
    public ?int $notificationId = null;
    public array $submissionData = [];
    public array $notificationData = [];
    public mixed $email = null;


    // Public Methods
    // =========================================================================

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

        $this->setProgress($queue, 0.75);

        $sentResponse = Formie::$plugin->getSubmissions()->sendNotificationEmail($notification, $submission, $this);
        $success = $sentResponse['success'] ?? false;
        $error = $sentResponse['error'] ?? false;

        if ($error) {
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

    protected function handleError(mixed $job, mixed $jobData): void
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
        }

        $jobData->email = $job->email;
    }


    // Private Methods
    // =========================================================================

    private function _getNotificationData(Notification $notification): array
    {
        $notificationData = $notification->toArray();
        $notificationData['content'] = $notification->getParsedContent();

        return $notificationData;
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
        $submissionData['fields'] = $submission->getValuesAsJson();

        return $submissionData;
    }
}
