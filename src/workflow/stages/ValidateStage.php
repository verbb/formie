<?php
namespace verbb\formie\workflow\stages;

use verbb\formie\enums\workflow\Stage;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\workflow\StageInterface;
use verbb\formie\workflow\StageResult;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\validate\ValidateSubmissionTask;

class ValidateStage implements StageInterface
{
    // Properties
    // =========================================================================

    private SubmissionWorkflow $process;
    

    // Public Methods
    // =========================================================================

    public function __construct(SubmissionWorkflow $process)
    {
        $this->process = $process;
    }

    public function getName(): string
    {
        return Stage::VALIDATE->value;
    }

    public function execute(WorkflowContext $context): StageResult
    {
        return $this->process->runStageTasks($context, $this->getName(), [
            new ValidateSubmissionTask(),
        ]);
    }
}
