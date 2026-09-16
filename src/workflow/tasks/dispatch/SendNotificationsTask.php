<?php
namespace verbb\formie\workflow\tasks\dispatch;

use verbb\formie\Formie;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\services\IntegrationDispatch;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

class SendNotificationsTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::DISPATCH->value;
    }

    public function getName(): string
    {
        return Task::DISPATCH_SEND_NOTIFICATIONS->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        $dispatchState = $context->taskState['dispatch.state'] ?? null;

        if (!$dispatchState instanceof DispatchState || !$dispatchState->success) {
            return TaskResult::continue();
        }

        $dispatchState->runOnce(DispatchState::MARKER_NOTIFICATIONS, function () use ($context): void {
            $submission = $context->request->submission;
            $form = $submission->getForm();

            if ($form && Formie::$plugin->getIntegrationDispatch()->shouldOrchestrate($form)) {
                Formie::$plugin->getIntegrationDispatch()->sendNotifications($submission, IntegrationDispatch::PHASE_BEFORE);
            } else {
                Formie::$plugin->getNotifications()->sendNotifications($submission);
            }
        });

        return TaskResult::continue();
    }
}
