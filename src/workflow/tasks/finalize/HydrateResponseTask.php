<?php
namespace verbb\formie\workflow\tasks\finalize;

use verbb\formie\Formie;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\models\SubmissionQuizResult;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

class HydrateResponseTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::FINALIZE->value;
    }

    public function getName(): string
    {
        return Task::FINALIZE_HYDRATE_RESPONSE->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        if (!$context->processingSuccess) {
            return TaskResult::halt(false, ['reason' => 'finalizeFailed']);
        }

        $request = $context->request;
        $context->response->form = $request->form;
        $context->response->submission = $request->submission;
        $context->success = true;

        $quizResult = $context->taskState['questionnaireScoring.result'] ?? null;

        if ($quizResult instanceof SubmissionQuizResult && $request->form->settings->quizShowScoreAfterSubmit) {
            $context->response->quizResult = Formie::$plugin->getQuestionnaireScoring()->getQuizResultPayload(
                $quizResult,
                $request->form,
                true,
            );
        }

        return TaskResult::halt(true, ['reason' => 'completed']);
    }
}
