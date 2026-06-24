<?php
namespace verbb\formie\workflow\tasks\normalize;

use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\Formie;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

class CaptureSubmissionMetadataTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::NORMALIZE->value;
    }

    public function getName(): string
    {
        return Task::NORMALIZE_CAPTURE_SUBMISSION_METADATA->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        Formie::$plugin->getSubmissionMetadata()->captureForSubmission(
            $context->request->submission,
            $context->request->form,
        );

        return TaskResult::continue();
    }
}
