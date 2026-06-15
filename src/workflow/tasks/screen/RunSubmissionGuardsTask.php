<?php
namespace verbb\formie\workflow\tasks\screen;

use verbb\formie\Formie;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

class RunSubmissionGuardsTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::SCREEN->value;
    }

    public function getName(): string
    {
        return Task::SCREEN_RUN_SUBMISSION_GUARDS->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        $request = $context->request;
        $reason = Formie::$plugin->getSubmissionGuards()->validateRequest($request);

        if ($reason) {
            $request->submission->isSpam = true;
            $request->submission->spamReason = $reason;
        }

        return TaskResult::continue();
    }
}
