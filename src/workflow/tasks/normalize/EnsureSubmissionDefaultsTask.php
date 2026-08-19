<?php
namespace verbb\formie\workflow\tasks\normalize;

use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\helpers\SubmissionEditBehaviour;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

class EnsureSubmissionDefaultsTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::NORMALIZE->value;
    }

    public function getName(): string
    {
        return Task::NORMALIZE_ENSURE_SUBMISSION_DEFAULTS->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        $form = $context->request->form;
        $submission = $context->request->submission;
        $editBehaviour = SubmissionEditBehaviour::resolve($context->request);

        // Revision edits keep an explicit operator/posted status; only fill when missing
        // on brand-new CP submissions that have not chosen a status yet.
        if (!SubmissionEditBehaviour::isRevision($editBehaviour) || !$submission->statusId) {
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
