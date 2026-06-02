<?php
namespace verbb\formie\workflow\tasks\normalize;

use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

class ClearConditionallyHiddenFieldsTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::NORMALIZE->value;
    }

    public function getName(): string
    {
        return Task::NORMALIZE_CLEAR_CONDITIONALLY_HIDDEN_FIELDS->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        $submission = $context->request->submission;

        if (!$context->request->clearConditionallyHiddenFields) {
            return TaskResult::continue();
        }

        foreach ($context->request->form->getFields() as $field) {
            if ($field->isConditionallyHidden($submission)) {
                $submission->setFieldValue($field->handle, null);
            }
        }

        return TaskResult::continue();
    }
}
