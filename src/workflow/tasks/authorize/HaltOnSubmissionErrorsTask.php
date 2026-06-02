<?php
namespace verbb\formie\workflow\tasks\authorize;

use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

class HaltOnSubmissionErrorsTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::AUTHORIZE->value;
    }

    public function getName(): string
    {
        return Task::AUTHORIZE_HALT_ON_SUBMISSION_ERRORS->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        if ($context->request->submission->hasErrors()) {
            $context->processingSuccess = false;

            return TaskResult::halt(false, ['reason' => 'submissionHasErrors']);
        }

        return TaskResult::continue();
    }
}
