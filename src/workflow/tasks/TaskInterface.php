<?php
namespace verbb\formie\workflow\tasks;

use verbb\formie\workflow\WorkflowContext;

interface TaskInterface
{
    // Public Methods
    // =========================================================================

    public function execute(WorkflowContext $context): TaskResult;
}
