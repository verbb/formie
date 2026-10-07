<?php
namespace verbb\formie\workflow\tasks\preflight;

use verbb\formie\Formie;
use verbb\formie\conditions\ConditionVisibility;
use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

use Craft;

use RuntimeException;

class ClearHiddenValuesTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function execute(WorkflowContext $context): TaskResult
    {
        $submission = $context->command->submission;

        try {
            (new ConditionVisibility())->clear($submission);
        } catch (RuntimeException $exception) {
            Formie::warning($exception->getMessage());
            $submission->addError('form', Craft::t('formie', 'This form has an invalid condition configuration.'));
            return TaskResult::stop($context->result(SubmissionOutcomeType::VALIDATION_FAILED));
        }

        return TaskResult::continue();
    }
}
