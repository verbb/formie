<?php
namespace verbb\formie\workflow\tasks\prepare;

use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

class ApplyDraftContextTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::PREPARE->value;
    }

    public function getName(): string
    {
        return Task::PREPARE_APPLY_DRAFT_CONTEXT->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        $request = $context->request;

        if ($request->draftContext) {
            $request->form->setDraftContext($request->draftContext);
        }

        return TaskResult::continue();
    }
}
