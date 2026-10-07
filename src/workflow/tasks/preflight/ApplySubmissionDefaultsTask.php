<?php
namespace verbb\formie\workflow\tasks\preflight;

use verbb\formie\enums\SubmissionOperation;
use verbb\formie\services\RuntimeConfiguration;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

class ApplySubmissionDefaultsTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function execute(WorkflowContext $context): TaskResult
    {
        $form = $context->command->form;
        $submission = $context->command->submission;
        (new RuntimeConfiguration())->applyValues($submission);
        $isRevision = $context->command->operation === SubmissionOperation::REVISE;

        // Revision edits keep an explicit operator/posted status; only fill when missing
        // on brand-new CP submissions that have not chosen a status yet.
        if (!$isRevision || !$submission->statusId) {
            if (!$submission->statusId) {
                $submission->setStatus($form->getDefaultStatus());
            }
        }

        if (!$submission->title) {
            $submission->title = $form->getDefaultSubmissionTitle($submission);
        }

        return TaskResult::continue();
    }
}
