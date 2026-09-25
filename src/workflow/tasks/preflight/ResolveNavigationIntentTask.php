<?php
namespace verbb\formie\workflow\tasks\preflight;

use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

use Craft;

class ResolveNavigationIntentTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function execute(WorkflowContext $context): TaskResult
    {
        $command = $context->command;
        $form = $command->form;

        if ($command->pageId !== null) {
            foreach ($form->getPages() as $page) {
                if ((int)$page->id === $command->pageId) {
                    $form->setCurrentPage($page);
                    return TaskResult::continue();
                }
            }

            $command->submission->addError('form', Craft::t('formie', 'The requested page is unavailable.'));
            return TaskResult::stop($context->result(SubmissionOutcomeType::REJECTED));
        }

        return TaskResult::continue();
    }
}
