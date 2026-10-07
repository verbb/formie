<?php
namespace verbb\formie\workflow\tasks\persist;

use verbb\formie\Formie;
use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\services\SubmissionPersistence;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

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

        if (!(new SubmissionPersistence())->persist($context->command, $context->becameComplete, static function() use ($context): void {
            Formie::$plugin->getSubmissionDispatches()->recordIntent($context);
        })) {
            return TaskResult::stop($context->result(SubmissionOutcomeType::VALIDATION_FAILED));
        }
        $context->processingSuccess = true;
        $context->taskState['save.success'] = true;
        return TaskResult::continue();
    }
}
