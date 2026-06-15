<?php
namespace verbb\formie\workflow\tasks\screen;

use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\helpers\SpamHelper;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

class RunSpamChecksTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::SCREEN->value;
    }

    public function getName(): string
    {
        return Task::SCREEN_RUN_SPAM_CHECKS->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        $request = $context->request;

        if ($request->submitAction !== SubmissionWorkflow::SUBMIT_ACTION_SUBMIT) {
            return TaskResult::continue();
        }

        $submission = $request->submission;

        if ($submission->isSpam) {
            return TaskResult::continue();
        }

        $emailMatch = SpamHelper::checkGlobalEmailRules($submission);

        if ($emailMatch) {
            $submission->isSpam = true;
            $submission->spamReason = SpamHelper::spamReasonFromEmailMatch($emailMatch);

            return TaskResult::continue();
        }

        $match = SpamHelper::checkSubmission($submission);

        if ($match) {
            $submission->isSpam = true;
            $submission->spamReason = SpamHelper::spamReasonFromMatch($match);
        }

        return TaskResult::continue();
    }
}
