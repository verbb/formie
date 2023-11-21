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

    public function execute($queue): void
    {
        $this->setProgress($queue, 0.25);

        $notification = Formie::$plugin->getNotifications()->getNotificationById($this->notificationId);
        $submission = Formie::$plugin->getSubmissions()->getSubmissionById($this->submissionId);

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

        $this->submission = $submission->toArray();
        $this->notification = $notification->toArray();
        $this->notification['content'] = $notification->getParsedContent();

        // Add a little extra info for submission fields
        foreach ($submission->getFields() as $field) {
            $this->submission['fields'][] = [
                'type' => get_class($field),
                'handle' => $field->handle,
                'settings' => $field->settings,
                'value' => $submission->getFieldValue($field->handle),
            ];
        }

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
}
