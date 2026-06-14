<?php
namespace verbb\formie\workflow\tasks\save;

use verbb\formie\Formie;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

use Craft;

class PersistSubmissionWorkflowTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::SAVE->value;
    }

    public function getName(): string
    {
        return Task::SAVE_PERSIST_SUBMISSION_WORKFLOW->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        $submission = $context->request->submission;
        $settings = Formie::$plugin->getSettings();

        // Spam marked in SCREEN should still reach FINALIZE spam behaviour even
        // when discarded submissions are not persisted to the database.
        if (
            $submission->isSpam
            && Craft::$app->getRequest()->getIsSiteRequest()
            && !$settings->shouldSaveSpam($submission)
        ) {
            Formie::$plugin->getSubmissions()->logSpam($submission);
            $context->taskState['save.success'] = true;
            $context->taskState['save.spamDiscarded'] = true;

            return TaskResult::continue();
        }

        $context->taskState['save.success'] = Craft::$app->getElements()->saveElement($submission);

        return TaskResult::continue();
    }
}
