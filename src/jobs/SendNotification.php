<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;
use verbb\formie\jobs\BaseJob;

use Craft;
use craft\helpers\Json;

class SendNotification extends BaseJob
{
    // Public Properties
    // =========================================================================

    public $submissionId;
    public $submission;
    public $notificationId;
    public $notification;
    public $notificationContent;
    public $email;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Sending form notification.');
    }

    /**
     * @inheritDoc
     */
    public function execute($queue)
    {
        $this->setProgress($queue, 0);

        $notification = Formie::$plugin->getNotifications()->getNotificationById($this->notificationId);
        $submission = Formie::$plugin->getSubmissions()->getSubmissionById($this->submissionId);

        if (!$notification) {
            throw new \Exception('Unable to find notification: ' . $this->notificationId . '.');
        }

        if (!$submission) {
            throw new \Exception('Unable to find submission: ' . $this->submissionId . '.');
        }

        $this->submission = $submission;
        $this->notification = $notification;
        $this->notificationContent = $notification->getParsedContent();

        $sentResponse = Formie::$plugin->getSubmissions()->sendNotificationEmail($notification, $submission, $this);
        $success = $sentResponse['success'] ?? false;
        $error = $sentResponse['error'] ?? false;

        if ($error) {
            // Check if should send the nominated admin(s) an email about this error.
            Formie::$plugin->getEmails()->sendFailAlertEmail($notification, $submission, $sentResponse);

            throw new \Exception('Failed to send notification email: ' . Json::encode($sentResponse) . '.');
        }

        $this->setProgress($queue, 1);
    }
}
