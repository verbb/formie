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

        try {
            (new \verbb\formie\conditions\ConditionVisibility())->clear($submission);
        } catch (\RuntimeException $exception) {
            \verbb\formie\Formie::warning($exception->getMessage());
            $submission->addError('form', \Craft::t('formie', 'This form has an invalid condition configuration.'));
            return TaskResult::stop($context->result(\verbb\formie\enums\SubmissionOutcomeType::VALIDATION_FAILED));
        }

        return TaskResult::continue();
    }
}
