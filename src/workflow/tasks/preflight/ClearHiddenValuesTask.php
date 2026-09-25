<?php
namespace verbb\formie\workflow\tasks\preflight;

use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

class ClearHiddenValuesTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function execute(WorkflowContext $context): TaskResult
    {
        $submission = $context->command->submission;

        if (!$context->command->clearConditionallyHiddenFields) {
            return TaskResult::continue();
        }

        foreach ($context->command->form->getFields() as $field) {
            if ($field->isConditionallyHidden($submission)) {
                $submission->setFieldValue($field->handle, null);
            }
        }

        return TaskResult::continue();
    }
}
