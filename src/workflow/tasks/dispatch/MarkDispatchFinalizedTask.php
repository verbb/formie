<?php
namespace verbb\formie\workflow\tasks\dispatch;

use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

class MarkDispatchFinalizedTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::DISPATCH->value;
    }

    public function getName(): string
    {
        return Task::DISPATCH_MARK_DISPATCH_FINALIZED->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        $dispatchState = $context->taskState['dispatch.state'] ?? null;

        if (!$dispatchState instanceof DispatchState) {
            return TaskResult::continue();
        }

        if (
            !$dispatchState->hasMarker(DispatchState::MARKER_FINALIZED)
            && (
                ($dispatchState->hasMarker(DispatchState::MARKER_NOTIFICATIONS)
                    && $dispatchState->hasMarker(DispatchState::MARKER_INTEGRATIONS))
                || $dispatchState->hasMarker(DispatchState::MARKER_SPAM_NOTIFICATIONS)
            )
        ) {
            $dispatchState->markMarker(DispatchState::MARKER_FINALIZED);
        }

        $context->processingSuccess = $dispatchState->success;

        return TaskResult::continue();
    }
}
