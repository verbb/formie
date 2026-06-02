<?php
namespace verbb\formie\workflow\stages;

use verbb\formie\enums\workflow\Stage;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\workflow\StageInterface;
use verbb\formie\workflow\StageResult;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\dispatch\GuardDispatchEligibilityTask;
use verbb\formie\workflow\tasks\dispatch\MarkDispatchFinalizedTask;
use verbb\formie\workflow\tasks\dispatch\SendNotificationsTask;
use verbb\formie\workflow\tasks\dispatch\SendSpamNotificationsTask;
use verbb\formie\workflow\tasks\dispatch\TriggerIntegrationsTask;

class DispatchStage implements StageInterface
{
    // Public Methods
    // =========================================================================

    public function __construct(private SubmissionWorkflow $workflow)
    {
    }

    public function getName(): string
    {
        return Stage::DISPATCH->value;
    }

    public function execute(WorkflowContext $context): StageResult
    {
        return $this->workflow->runStageTasks($context, $this->getName(), [
            new GuardDispatchEligibilityTask(),
            new SendNotificationsTask(),
            new TriggerIntegrationsTask(),
            new SendSpamNotificationsTask(),
            new MarkDispatchFinalizedTask(),
        ]);
    }
}
