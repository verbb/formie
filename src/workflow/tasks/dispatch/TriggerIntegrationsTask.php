<?php
namespace verbb\formie\workflow\tasks\dispatch;

use verbb\formie\Formie;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\helpers\IntegrationTriggerEvents;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

class TriggerIntegrationsTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::DISPATCH->value;
    }

    public function getName(): string
    {
        return Task::DISPATCH_TRIGGER_INTEGRATIONS->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        $dispatchState = $context->taskState['dispatch.state'] ?? null;

        if (!$dispatchState instanceof DispatchState || !$dispatchState->success) {
            return TaskResult::continue();
        }

        $isSubmissionEdit = $dispatchState->isSubmissionEditDispatch();

        $dispatch = function () use ($context): void {
            Formie::$plugin->getIntegrationTriggers()->dispatchFromWorkflow(
                $context->request->submission,
                $context->request->processMode,
                IntegrationTriggerEvents::resolveFromProcessMode($context->request->processMode),
            );
        };

        if ($isSubmissionEdit) {
            $dispatch();
        } else {
            $dispatchState->runOnce(DispatchState::MARKER_INTEGRATIONS, $dispatch);
        }

        return TaskResult::continue();
    }
}
