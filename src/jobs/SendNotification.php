<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;

use Craft;
use craft\helpers\Json;
use Exception;

class SendNotification extends BaseJob
{
    // Properties
    // =========================================================================

    public ?int $submissionId = null;
    public ?array $submission = null;
    public ?int $notificationId = null;
    public ?array $notification = null;
    public mixed $email = null;


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
    public function execute($queue): void
    {
        $this->setProgress($queue, 0);

        $notification = Formie::$plugin->getNotifications()->getNotificationById($this->notificationId);
        $submission = Formie::$plugin->getSubmissions()->getSubmissionById($this->submissionId);

        if (!$notification) {
            throw new Exception('Unable to find notification: ' . $this->notificationId . '.');
        }

        if (!$submission) {
            throw new Exception('Unable to find submission: ' . $this->submissionId . '.');
        }

        $this->submission = $submission->toArray();
        $this->notification = $notification->toArray();
        $this->notification['content'] = $notification->getParsedContent();

        // Add a little extra info for submission fields
        if ($fieldLayout = $submission->getFieldLayout()) {
            foreach ($fieldLayout->getCustomFields() as $field) {
                $this->submission['fields'][] = [
                    'type' => get_class($field),
                    'handle' => $field->handle,
                    'settings' => $field->settings,
                    'value' => $submission->getFieldValue($field->handle),
                ];
            }
        }

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
}
