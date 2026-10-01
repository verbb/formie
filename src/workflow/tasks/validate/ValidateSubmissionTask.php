<?php
namespace verbb\formie\workflow\tasks\validate;

use verbb\formie\Formie;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

use craft\base\Element;

class ValidateSubmissionTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function execute(WorkflowContext $context): TaskResult
    {
        $command = $context->command;
        $submission = $command->submission;
        // Preflight owns the transition; validation consumes that decision without
        // resolving the route again or changing completion intent.
        $submission->setScenario(Element::SCENARIO_LIVE);
        $submission->validateCurrentPageOnly = $command->usesVisitorProgression() && !$context->attemptedCompletion;
        $submission->validate();

        if ($context->attemptedCompletion) {
            $error = Formie::$plugin->getQuestionnaireScoring()->getRetakeError($command->form, $submission);

            if ($error !== null) {
                $submission->addError('form', $error);
            }
        }

        return $submission->hasErrors()
            ? TaskResult::stop($context->result(\verbb\formie\enums\SubmissionOutcomeType::VALIDATION_FAILED))
            : TaskResult::continue();
    }

}
