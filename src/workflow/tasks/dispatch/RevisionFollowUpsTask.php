<?php
namespace verbb\formie\workflow\tasks\dispatch;

use verbb\formie\Formie;
use verbb\formie\enums\SubmissionAuthorityType;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

class RevisionFollowUpsTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function execute(WorkflowContext $context): TaskResult
    {
        $command = $context->command;
        $state = $context->taskState['dispatch.state'];
        $state->runOnce('revisionFollowUps', function () use ($command): void {
            Formie::$plugin->getNotificationTriggers()->dispatchStatusChange($command->submission);
            if ($command->authority->type === SubmissionAuthorityType::CONTROL_PANEL && $command->submission->hasSpamChanged(true, false)) {
                Formie::$plugin->getIntegrationTriggers()->dispatchSpamUnmark(
                    $command->submission, $command->sendNotificationsOnSpamUnmark, $command->triggerIntegrationsOnSpamUnmark,
                );
            }
        });
        return TaskResult::continue();
    }
}
