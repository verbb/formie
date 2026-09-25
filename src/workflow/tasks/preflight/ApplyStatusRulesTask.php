<?php
namespace verbb\formie\workflow\tasks\preflight;

use verbb\formie\helpers\SubmissionStatusRulesHelper;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

class ApplyStatusRulesTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function execute(WorkflowContext $context): TaskResult
    {
        SubmissionStatusRulesHelper::applyRules(
            $context->command->form,
            $context->command->submission,
            $context->command,
            $context->nextPage !== null,
        );

        return TaskResult::continue();
    }
}
