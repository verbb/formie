<?php
namespace verbb\formie\workflow\tasks\normalize;

use verbb\formie\Formie;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

class HandleBackNavigationTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::NORMALIZE->value;
    }

    public function getName(): string
    {
        return Task::NORMALIZE_HANDLE_BACK_NAVIGATION->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        $request = $context->request;
        $formieSettings = Formie::$plugin->getSettings();

        if ($request->submitAction === SubmissionWorkflow::SUBMIT_ACTION_BACK && !$formieSettings->enableBackSubmission) {
            $nextPage = $request->form->getPreviousPage(null, $request->submission, true);

            if (is_numeric($request->targetPageId)) {
                $nextPage = $this->_findPageById((int)$request->targetPageId, $request->form->getPages()) ?? $nextPage;
            }

            if (!$nextPage) {
                $nextPage = $request->form->getCurrentPage();
            }

            $request->form->setCurrentPage($nextPage);
            $context->nextPage = $nextPage;
            $context->success = true;

            return TaskResult::halt(true, ['reason' => 'backNavigationOnly']);
        }

        return TaskResult::continue();
    }


    // Private Methods
    // =========================================================================

    private function _findPageById(int $pageId, array $pages): ?FieldLayoutPage
    {
        foreach ($pages as $page) {
            if ((int)$page->id === $pageId) {
                return $page;
            }
        }

        return null;
    }
}
