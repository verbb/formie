<?php
namespace verbb\formie\workflow\tasks\dispatch;

use verbb\formie\Formie;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

class GuardDispatchEligibilityTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::DISPATCH->value;
    }

    public function getName(): string
    {
        return Task::DISPATCH_GUARD_DISPATCH_ELIGIBILITY->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        $submission = $context->request->submission;

        if ($submission->hasErrors()) {
            return TaskResult::halt(false, ['reason' => 'submissionHasErrors']);
        }

        $dispatchState = new DispatchState($context->request, $context->processingSuccess);

        if (!$dispatchState->isDispatchable()) {
            return TaskResult::halt(true, ['reason' => 'dispatchSkippedForMode']);
        }

        if ($dispatchState->isAlreadyFinalized()) {
            Formie::info('Skipping duplicate dispatch workflow for submission #{id} ({traceId}).', [
                'id' => $submission->id,
                'traceId' => $dispatchState->traceId,
            ]);

            return TaskResult::halt(true, ['reason' => 'alreadyFinalized']);
        }

        $dispatchState->applySpamFailureIfNeeded();
        $context->taskState['dispatch.state'] = $dispatchState;

        return TaskResult::continue();
    }
}
