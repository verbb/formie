<?php
namespace verbb\formie\workflow\tasks\finalize;

use verbb\formie\Formie;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

class ConsumeReplayTokenTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::FINALIZE->value;
    }

    public function getName(): string
    {
        return Task::FINALIZE_CONSUME_REPLAY_TOKEN->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        if (!$context->processingSuccess) {
            return TaskResult::continue();
        }

        $request = $context->request;
        $submissionGuards = Formie::$plugin->getSubmissionGuards();

        if (!$submissionGuards->shouldConsumeReplayToken($request)) {
            return TaskResult::continue();
        }

        $submissionGuards->consumeReplayToken(
            (string)$request->form->uid,
            (string)$request->requestToken,
        );

        return TaskResult::continue();
    }
}
