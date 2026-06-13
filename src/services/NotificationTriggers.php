<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\models\Notification;

use yii\base\Component;

class NotificationTriggers extends Component
{
    // Public Methods
    // =========================================================================

    public function dispatchStatusChange(Submission $submission): void
    {
        if ($submission->isNewSubmission || !$submission->hasStatusChanged()) {
            return;
        }

        $form = $submission->getForm();

        if (!$form) {
            return;
        }

        foreach ($form->getEnabledNotifications() as $notification) {
            if (!$this->_shouldSendStatusChangeNotification($notification, $submission)) {
                continue;
            }

            Formie::$plugin->getSubmissions()->sendNotification($notification, $submission);
        }
    }


    // Private Methods
    // =========================================================================

    private function _shouldSendStatusChangeNotification(Notification $notification, Submission $submission): bool
    {
        $statusHandle = $notification->getStatusCondition($submission);

        if ($statusHandle === null || $statusHandle === '') {
            return false;
        }

        $status = $submission->getStatus();

        return $status && (string)$status->handle === (string)$statusHandle;
    }
}
