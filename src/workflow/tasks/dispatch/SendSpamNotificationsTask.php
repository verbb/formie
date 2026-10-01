<?php
namespace verbb\formie\workflow\tasks\dispatch;

use verbb\formie\Formie;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

class SendSpamNotificationsTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function execute(WorkflowContext $context): TaskResult
    {
        $dispatchState = $context->taskState['dispatch.state'] ?? null;

        if (!$dispatchState instanceof DispatchState || !$dispatchState->shouldRunSpamNotifications()) {
            return TaskResult::continue();
        }

        $dispatchState->runOnce(DispatchState::MARKER_SPAM_NOTIFICATIONS, function() use ($context): void {
            $this->_sendSpamNotifications($context);
        });

        return TaskResult::continue();
    }


    // Private Methods
    // =========================================================================

    private function _sendSpamNotifications(WorkflowContext $context): void
    {
        $submission = $context->command->submission;
        $form = $submission->getForm();

        if (!$form) {
            return;
        }

        $notifications = $form->getEnabledNotifications();

        foreach ($notifications as $notification) {
            Formie::$plugin->getNotifications()->sendNotification($notification, $submission);
        }
    }
}
