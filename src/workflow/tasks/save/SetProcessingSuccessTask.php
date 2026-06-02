<?php
namespace verbb\formie\workflow\tasks\save;

use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

class SetProcessingSuccessTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::SAVE->value;
    }

    public function getName(): string
    {
        return Task::SAVE_SET_PROCESSING_SUCCESS->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        $saveSuccess = (bool)($context->taskState['save.success'] ?? false);
        $context->processingSuccess = $saveSuccess && !$context->request->submission->hasErrors();

        if (!$context->processingSuccess) {
            return TaskResult::halt(false, ['reason' => 'saveFailed']);
        }

        return TaskResult::continue();
    }
}
