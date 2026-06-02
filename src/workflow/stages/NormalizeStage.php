<?php
namespace verbb\formie\workflow\stages;

use verbb\formie\enums\workflow\Stage;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\workflow\StageInterface;
use verbb\formie\workflow\StageResult;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\normalize\ClearConditionallyHiddenFieldsTask;
use verbb\formie\workflow\tasks\normalize\EnsureSubmissionDefaultsTask;
use verbb\formie\workflow\tasks\normalize\HandleBackNavigationTask;
use verbb\formie\workflow\tasks\normalize\ResolvePageFlowTask;

class NormalizeStage implements StageInterface
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
        return Stage::NORMALIZE->value;
    }

    public function execute(WorkflowContext $context): StageResult
    {
        return $this->process->runStageTasks($context, $this->getName(), [
            new HandleBackNavigationTask(),
            new ResolvePageFlowTask(),
            new ClearConditionallyHiddenFieldsTask(),
            new EnsureSubmissionDefaultsTask(),
        ]);
    }
}
