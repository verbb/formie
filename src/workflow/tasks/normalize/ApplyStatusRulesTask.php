<?php
namespace verbb\formie\workflow\tasks\normalize;

use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\helpers\SubmissionStatusRulesHelper;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

class ApplyStatusRulesTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::NORMALIZE->value;
    }

    public function getName(): string
    {
        return Task::NORMALIZE_APPLY_STATUS_RULES->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        SubmissionStatusRulesHelper::applyRules(
            $context->request->form,
            $context->request->submission,
            $context->request,
            $context->nextPage !== null,
        );

        return TaskResult::continue();
    }
}
