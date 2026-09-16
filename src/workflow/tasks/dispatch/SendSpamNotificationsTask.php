<?php
namespace verbb\formie\workflow\tasks\dispatch;

use verbb\formie\Formie;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;


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

        $dispatchState->runOnce(DispatchState::MARKER_SPAM_NOTIFICATIONS, function () use ($context): void {
            $this->_sendSpamNotifications($context);
        });

        return TaskResult::continue();
    }


    // Private Methods
    // =========================================================================

    private function _sendSpamNotifications(WorkflowContext $context): void
    {
        $submission = $context->request->submission;
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
