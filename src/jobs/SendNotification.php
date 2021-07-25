<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;

use Craft;
use craft\db\Table;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\queue\BaseJob;

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

    /**
     * @inheritDoc
     */
    public function updatePayload($event)
    {
        // When an error occurs on the job, we want to update the Job Data for the job. This helps immensly with
        // debugging, and provides the customer with context on exactly _what_ is trying to be sent.
        // We have to do a direct database update however, because the Job Data is only serialized when the job 
        // is created. The payload is changed via multiple calls in the task, so we want to reflect that,
        $jobData = $this->_jobData($event->job);

        // Serialize it again ready to save
        $message = Craft::$app->getQueue()->serializer->serialize($jobData);

        Db::update(Table::QUEUE, ['job' => $message], ['id' => $event->id], [], false);
    }


    // Private Methods
    // =========================================================================

    /**
     * Checks if $job is a resource and if so, convert it to a serialized format.
     *
     * @param string|resource $job
     * @return string
     */
    private function _jobData($job)
    {
        if (is_resource($job)) {
            $job = stream_get_contents($job);

            if (is_string($job) && strpos($job, 'x') === 0) {
                $hex = substr($job, 1);
                if (StringHelper::isHexadecimal($hex)) {
                    $job = hex2bin($hex);
                }
            }
        }

        return $job;
    }
}
