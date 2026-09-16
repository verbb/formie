<?php
namespace verbb\formie\workflow\stages;

use verbb\formie\enums\workflow\Stage;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\workflow\StageInterface;
use verbb\formie\workflow\StageResult;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\save\ApplyCompletionFromPaymentStateTask;
use verbb\formie\workflow\tasks\save\PersistQuestionnaireScoringTask;
use verbb\formie\workflow\tasks\save\PersistSubmissionDirectTask;
use verbb\formie\workflow\tasks\save\PersistSubmissionWorkflowTask;
use verbb\formie\workflow\tasks\save\ProcessPaymentsTask;
use verbb\formie\workflow\tasks\save\SetProcessingSuccessTask;

class SaveStage implements StageInterface
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
        return Stage::SAVE->value;
    }

    public function execute(WorkflowContext $context): StageResult
    {
        // A webhook replay already has a persisted payment. Resolve its completion
        // state before saving; initial submissions still need an ID before charging.
        $replay = $context->request->processMode === SubmissionWorkflow::PROCESS_MODE_PAYMENT_REPLAY;

        return $this->process->runStageTasks($context, $this->getName(), [
            ...($replay ? [new ProcessPaymentsTask()] : []),
            new PersistSubmissionWorkflowTask(),
            new PersistSubmissionDirectTask(),
            ...($replay ? [] : [new ProcessPaymentsTask()]),
            new ApplyCompletionFromPaymentStateTask(),
            new PersistQuestionnaireScoringTask(),
            new SetProcessingSuccessTask(),
        ]);
    }
}
