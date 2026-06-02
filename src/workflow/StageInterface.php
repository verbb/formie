<?php
namespace verbb\formie\workflow;

interface StageInterface
{
    // Public Methods
    // =========================================================================

    public function getName(): string;
    public function execute(WorkflowContext $context): StageResult;
}
