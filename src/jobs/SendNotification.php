<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;

use Craft;
use craft\queue\BaseJob;

class SendNotification extends BaseJob
{
    // Public Properties
    // =========================================================================

    /**
     * @var int
     */
    public $submissionId;

    /**
     * @var int
     */
    public $notificationId;


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

        $sentResponse = Formie::$plugin->getEmails()->sendEmail($notification, $submission);
        $success = $sentResponse['success'] ?? false;
        $error = $sentResponse['error'] ?? false;

        if ($error) {
            throw new \Exception('Failed to send notification email.');
        }

        $this->setProgress($queue, 1);
    }
}
