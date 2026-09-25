<?php
namespace verbb\formie\workflow\tasks\persist;

use verbb\formie\Formie;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

use Craft;

use RuntimeException;

class PersistSubmissionTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function execute(WorkflowContext $context): TaskResult
    {
        $submission = $context->command->submission;

        if ($context->taskState['persist.discardSpam'] ?? false) {
            Formie::$plugin->getSubmissions()->logSpam($submission);
            $context->processingSuccess = true;
            return TaskResult::continue();
        }

        // Validate owns validation. Draft persistence must never re-enable it.
        if (!Craft::$app->getElements()->saveElement($submission, false)) {
            throw new RuntimeException('Unable to persist the accepted submission.');
        }

        Formie::$plugin->getSubmissionOperations()->bindSubmission($context->command, (int)$submission->id);
        $context->processingSuccess = true;
        $context->taskState['save.success'] = true;

        return TaskResult::continue();
    }
}
