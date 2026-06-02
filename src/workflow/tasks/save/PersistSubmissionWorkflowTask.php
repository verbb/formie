<?php
namespace verbb\formie\workflow\tasks\save;

use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

use Craft;

class PersistSubmissionWorkflowTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::SAVE->value;
    }

    public function getName(): string
    {
        return Task::SAVE_PERSIST_SUBMISSION_WORKFLOW->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        $context->taskState['save.success'] = Craft::$app->getElements()->saveElement($context->request->submission);

        return TaskResult::continue();
    }
}
