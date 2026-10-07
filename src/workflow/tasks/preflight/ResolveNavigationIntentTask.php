<?php
namespace verbb\formie\workflow\tasks\preflight;

use verbb\formie\Formie;
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

        if ($command->isInteractive()) {
            $progress = Formie::$plugin->getSubmissionProgress()->getProgressState($form);
            $pages = $form->getPages();
            $authoritative = $pages[0] ?? null;

            foreach ($pages as $page) {
                if ($progress && (int)$page->id === (int)$progress->currentPageId) {
                    $authoritative = $page;
                }
            }

            if ($authoritative) {
                $form->setCurrentPage($authoritative);

                foreach ($pages as $page) {
                    if ($command->pageId === (int)$page->id && $form->getPageIndex($page) > $form->getPageIndex($authoritative)) {
                        $command->submission->addError('form', Craft::t('formie', 'The requested page is unavailable.'));
                        return TaskResult::stop($context->result(SubmissionOutcomeType::VALIDATION_FAILED));
                    }
                }
            }
        }

        if ($command->pageId !== null) {
            foreach ($form->getPages() as $page) {
                if ((int)$page->id === $command->pageId) {
                    $form->setCurrentPage($page);
                    return TaskResult::continue();
                }
            }

            $command->submission->addError('form', Craft::t('formie', 'The requested page is unavailable.'));
            return TaskResult::stop($context->result(SubmissionOutcomeType::VALIDATION_FAILED));
        }

        return TaskResult::continue();
    }
}
