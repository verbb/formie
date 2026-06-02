<?php
namespace verbb\formie\workflow\tasks\finalize;

use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\Formie;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

class ApplyProgressionStateTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::FINALIZE->value;
    }

    public function getName(): string
    {
        return Task::FINALIZE_APPLY_PROGRESSION_STATE->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        if (!$context->processingSuccess) {
            return TaskResult::halt(false, ['reason' => 'finalizeFailed']);
        }

        $request = $context->request;

        if ($context->nextPage) {
            $request->form->setCurrentPage($context->nextPage);
            $request->form->setCurrentSubmission($request->submission);
        }

        if (!$request->submission->isIncomplete) {
            $request->form->resetCurrentPage();
            $request->form->resetCurrentSubmission();

            if ($request->submission->id) {
                Formie::$plugin->getFileUploads()->finalizeSubmissionUploads((int)$request->submission->id);
            }
        }

        return TaskResult::continue();
    }
}
