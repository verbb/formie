<?php
namespace verbb\formie\workflow\tasks\dispatch;

use verbb\formie\Formie;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

/**
 * Claim the replay token before notifications/integrations run.
 * Closing the validate→dispatch→finalize gap: concurrent completes that both
 * pass guards cannot both deliver side effects for the same requestToken.
 */
class ClaimReplayTokenTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::DISPATCH->value;
    }

    public function getName(): string
    {
        return Task::DISPATCH_CLAIM_REPLAY_TOKEN->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        $request = $context->request;
        $submissionGuards = Formie::$plugin->getSubmissionGuards();

        if (!$submissionGuards->shouldConsumeReplayToken($request)) {
            return TaskResult::continue();
        }

        if ($submissionGuards->claimReplayToken((string)$request->form->uid, (string)$request->requestToken)) {
            return TaskResult::continue();
        }

        Formie::info('Skipping duplicate dispatch for already-claimed replay token on submission #{id}.', [
            'id' => $request->submission->id,
        ]);

        // Treat as successful no-op: the other worker owns delivery.
        return TaskResult::halt(true, ['reason' => 'replayTokenAlreadyClaimed']);
    }
}
