<?php
namespace verbb\formie\workflow\tasks\dispatch;

use verbb\formie\Formie;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

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

        if (!$isSubmissionEdit && $dispatchState->hasMarker(DispatchState::MARKER_INTEGRATIONS)) {
            return TaskResult::continue(['reason' => 'integrationsAlreadyMarked']);
        }

        Formie::$plugin->getIntegrations()->triggerIntegrations(
            $context->request->submission,
            $context->request->processMode,
        );

        if (!$isSubmissionEdit) {
            $dispatchState->markMarker(DispatchState::MARKER_INTEGRATIONS);
        }

        return TaskResult::continue();
    }
}
