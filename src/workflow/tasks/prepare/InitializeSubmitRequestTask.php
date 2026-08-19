<?php
namespace verbb\formie\workflow\tasks\prepare;

use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

use craft\helpers\Session;

class InitializeSubmitRequestTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::PREPARE->value;
    }

    public function getName(): string
    {
        return Task::PREPARE_INITIALIZE_SUBMIT_REQUEST->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        // Only initial submit and payment-replay completion runs should suppress
        // status-change notifications in afterSave. Edit and draft saves must
        // leave the flag false so operator/status edits notify correctly (#2932).
        if (in_array($context->request->processMode, [
            SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            SubmissionWorkflow::PROCESS_MODE_PAYMENT_REPLAY,
        ], true)) {
            $context->request->submission->isNewSubmission = true;
        }

        // Ensure the session is started before submission-state persistence reads/writes.
        // There's no Session::open() function yet, so set a value to kick off sessions.
        Session::set('formie:nonce', rand());

        return TaskResult::continue();
    }
}
