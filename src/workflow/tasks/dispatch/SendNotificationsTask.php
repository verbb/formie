<?php
namespace verbb\formie\workflow\tasks\dispatch;

use verbb\formie\Formie;
use verbb\formie\services\IntegrationDispatcher;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

class SendNotificationsTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function execute(WorkflowContext $context): TaskResult
    {
        $dispatchState = $context->taskState['dispatch.state'] ?? null;

        if (!$dispatchState instanceof DispatchState || !$dispatchState->success) {
            return TaskResult::continue();
        }

        $dispatchState->runOnce(DispatchState::MARKER_NOTIFICATIONS, function () use ($context): void {
            $submission = $context->command->submission;
            $form = $submission->getForm();

            if ($form && Formie::$plugin->getIntegrationDispatcher()->shouldOrchestrate($form)) {
                Formie::$plugin->getIntegrationDispatcher()->sendNotifications($submission, IntegrationDispatcher::PHASE_BEFORE);
            } else {
                Formie::$plugin->getNotifications()->sendNotifications($submission);
            }
        });

        return TaskResult::continue();
    }
}
