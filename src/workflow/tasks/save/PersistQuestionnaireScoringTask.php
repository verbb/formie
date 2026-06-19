<?php
namespace verbb\formie\workflow\tasks\save;

use verbb\formie\Formie;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

class PersistQuestionnaireScoringTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::SAVE->value;
    }

    public function getName(): string
    {
        return Task::SAVE_PERSIST_QUESTIONNAIRE_SCORING->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        if (!($context->taskState['save.success'] ?? false)) {
            return TaskResult::continue();
        }

        $submission = $context->request->submission;
        $form = $context->request->form;
        $scoring = Formie::$plugin->getQuestionnaireScoring();

        if (!$scoring->shouldScoreSubmission($form, $submission)) {
            return TaskResult::continue();
        }

        $quizResult = $scoring->scoreSubmission($submission);

        if ($quizResult !== null) {
            $context->taskState['questionnaireScoring.result'] = $quizResult;
        }

        return TaskResult::continue();
    }
}
