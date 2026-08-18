<?php
namespace verbb\formie\workflow\tasks\validate;

use verbb\formie\Formie;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

class ValidateQuizRetakeTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::VALIDATE->value;
    }

    public function getName(): string
    {
        return Task::VALIDATE_QUIZ_RETAKE->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        if ($context->request->submitAction === SubmissionWorkflow::SUBMIT_ACTION_BACK) {
            return TaskResult::continue();
        }

        $form = $context->request->form;
        $submission = $context->request->submission;
        $currentPage = $form->getCurrentPage();

        if (!$form->isLastPage($currentPage, $submission)) {
            return TaskResult::continue();
        }

        $error = Formie::$plugin->getQuestionnaireScoring()->getRetakeError($form, $submission);

        if ($error !== null) {
            $submission->addError('form', $error);
        }

        return TaskResult::continue();
    }
}
