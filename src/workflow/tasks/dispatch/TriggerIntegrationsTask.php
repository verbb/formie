<?php
namespace verbb\formie\workflow\tasks\dispatch;

use verbb\formie\Formie;
use verbb\formie\enums\SubmissionAuthorityType;
use verbb\formie\helpers\IntegrationTriggerEvents;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

class TriggerIntegrationsTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function execute(WorkflowContext $context): TaskResult
    {
        $dispatchState = $context->taskState['dispatch.state'] ?? null;

        if (!$dispatchState instanceof DispatchState || !$dispatchState->success) {
            return TaskResult::continue();
        }

        $isSubmissionEdit = $dispatchState->isSubmissionEditDispatch();

        $dispatch = function() use ($context): void {
            Formie::$plugin->getIntegrationTriggers()->dispatchFromWorkflow(
                $context->command->submission,
                $context->command->operation,
                IntegrationTriggerEvents::resolveFromOperation($context->command->operation, $context->command->authority->type === SubmissionAuthorityType::CONTROL_PANEL),
            );
        };

        $dispatchState->runOnce(DispatchState::MARKER_INTEGRATIONS, $dispatch);

        return TaskResult::continue();
    }
}
