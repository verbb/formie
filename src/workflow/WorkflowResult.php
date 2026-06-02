<?php
namespace verbb\formie\workflow;

class WorkflowResult
{
    // Properties
    // =========================================================================

    public bool $success = false;
    public bool $halted = false;
    public ?string $haltedAtStage = null;
    public array $stageResults = [];


    // Public Methods
    // =========================================================================

    public function addStageResult(string $stageName, StageResult $result): void
    {
        $this->stageResults[$stageName] = $result;
    }
}
