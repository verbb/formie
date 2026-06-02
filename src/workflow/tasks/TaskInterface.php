<?php
namespace verbb\formie\workflow\tasks;

use verbb\formie\workflow\WorkflowContext;

interface TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string;
    public function getName(): string;
    public function execute(WorkflowContext $context): TaskResult;
}
