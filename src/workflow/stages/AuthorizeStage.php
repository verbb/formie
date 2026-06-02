<?php
namespace verbb\formie\workflow\stages;

use verbb\formie\enums\workflow\Stage;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\workflow\StageInterface;
use verbb\formie\workflow\StageResult;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\authorize\HaltOnSubmissionErrorsTask;
use verbb\formie\workflow\tasks\authorize\ResolvePaymentStateTask;

class AuthorizeStage implements StageInterface
{
    // Public Methods
    // =========================================================================

    public function __construct(private SubmissionWorkflow $workflow)
    {
    }

    public function getName(): string
    {
        return Stage::AUTHORIZE->value;
    }

    public function execute(WorkflowContext $context): StageResult
    {
        return $this->workflow->runStageTasks($context, $this->getName(), [
            new HaltOnSubmissionErrorsTask(),
            new ResolvePaymentStateTask(),
        ]);
    }
}
