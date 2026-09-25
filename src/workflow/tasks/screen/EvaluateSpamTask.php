<?php
namespace verbb\formie\workflow\tasks\screen;

use verbb\formie\enums\NavigationIntent;
use verbb\formie\helpers\SpamHelper;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

class EvaluateSpamTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function execute(WorkflowContext $context): TaskResult
    {
        $request = $context->command;

        if ($request->navigation !== NavigationIntent::ADVANCE) {
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

        $maximumLinksMatch = SpamHelper::checkMaximumLinks($submission);

        if ($maximumLinksMatch) {
            $submission->isSpam = true;
            $submission->spamReason = SpamHelper::spamReasonFromMaximumLinks($maximumLinksMatch);

            return TaskResult::continue();
        }

        $suspiciousTextMatch = SpamHelper::checkSuspiciousText($submission);

        if ($suspiciousTextMatch) {
            $submission->isSpam = true;
            $submission->spamReason = SpamHelper::spamReasonFromSuspiciousText($suspiciousTextMatch);

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
