<?php
namespace verbb\formie\workflow\tasks\normalize;

use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
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

        if (!$submission->statusId) {
            $submission->setStatus($form->getDefaultStatus());
        }

        if (!$submission->title) {
            $submission->title = $form->getDefaultSubmissionTitle($submission);
        }

        return TaskResult::continue();
    }
}
