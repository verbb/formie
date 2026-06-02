<?php
namespace verbb\formie\workflow\tasks\dispatch;

use verbb\formie\Formie;
use verbb\formie\events\SendNotificationEvent;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\jobs\SendNotification;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

use craft\helpers\Queue;

class SendSpamNotificationsTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::DISPATCH->value;
    }

    public function getName(): string
    {
        return Task::DISPATCH_SEND_SPAM_NOTIFICATIONS->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        $dispatchState = $context->taskState['dispatch.state'] ?? null;

        if (!$dispatchState instanceof DispatchState || !$dispatchState->shouldRunSpamNotifications()) {
            return TaskResult::continue();
        }

        if ($dispatchState->hasMarker(DispatchState::MARKER_SPAM_NOTIFICATIONS)) {
            return TaskResult::continue(['reason' => 'spamNotificationsAlreadyMarked']);
        }

        $this->_sendSpamNotifications($context);
        $dispatchState->markMarker(DispatchState::MARKER_SPAM_NOTIFICATIONS);

        return TaskResult::continue();
    }


    // Private Methods
    // =========================================================================

    private function _sendSpamNotifications(WorkflowContext $context): void
    {
        $settings = Formie::$plugin->getSettings();
        $submission = $context->request->submission;
        $form = $submission->getForm();

        if (!$form) {
            return;
        }

        $notifications = $form->getEnabledNotifications();

        foreach ($notifications as $notification) {
            // Evaluate conditions for each notification.
            if (!Formie::$plugin->getNotifications()->evaluateConditions($notification, $submission)) {
                continue;
            }

            if ($settings->useQueueForNotifications) {
                Queue::push(new SendNotification([
                    'submissionId' => $submission->id,
                    'notificationId' => $notification->id,
                ]), $settings->queuePriority);
                continue;
            }

            $this->_sendNotificationEmail($notification, $submission);
        }
    }

    private function _sendNotificationEmail($notification, $submission, $queueJob = null): array|bool
    {
        // Fire a before-send event so integrations can stop delivery.
        $event = new SendNotificationEvent([
            'submission' => $submission,
            'notification' => $notification,
        ]);
        Formie::$plugin->getNotifications()->trigger(Formie::$plugin->getNotifications()::EVENT_BEFORE_SEND_NOTIFICATION, $event);

        if (!$event->isValid) {
            return true;
        }

        return Formie::$plugin->getEmails()->sendEmail($event->notification, $event->submission, $queueJob);
    }
}
