<?php
namespace verbb\formie\workflow\tasks\preflight;

use verbb\formie\enums\SubmissionOperation;
use verbb\formie\enums\SubmissionPolicy;
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

        // A title typed into a new control panel submission is final.
        $hasTypedTitle = $submission->title && $context->command->policy === SubmissionPolicy::ADMINISTRATIVE_CREATE;

        if (!$submission->title) {
            $submission->title = $form->getDefaultSubmissionTitle($submission);
        }

        // Saving supplies the submission's ID and date, and later pages supply more field values.
        if (!$isRevision && !$hasTypedTitle) {
            $submission->setUpdateTitle(true);
        }

        return TaskResult::continue();
    }
}
