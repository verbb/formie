<?php
namespace verbb\formie\workflow\tasks\preflight;

use verbb\formie\Formie;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

class CaptureMetadataTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function execute(WorkflowContext $context): TaskResult
    {
        Formie::$plugin->getSubmissionMetadata()->captureForSubmission(
            $context->command->submission,
            $context->command->form,
        );

        return TaskResult::continue();
    }
}
