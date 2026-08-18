<?php
namespace verbb\formie\workflow\tasks\normalize;

use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

use craft\base\Element;

class ResolvePageFlowTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::NORMALIZE->value;
    }

    public function getName(): string
    {
        return Task::NORMALIZE_RESOLVE_PAGE_FLOW->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        $request = $context->request;
        $form = $request->form;
        $submission = $request->submission;

        if ($request->pageId !== null) {
            $currentPage = $this->_findPageById((int)$request->pageId, $form->getPages());

            if ($currentPage) {
                $form->setCurrentPage($currentPage);
            }
        }

        $targetPageId = $request->targetPageId;

        if (is_numeric($targetPageId)) {
            $nextPage = $this->_findPageById((int)$targetPageId, $form->getPages());
        } else if ($request->submitAction === SubmissionWorkflow::SUBMIT_ACTION_BACK) {
            $nextPage = $form->getPreviousPage(null, $submission, true);
        } else if ($request->submitAction === SubmissionWorkflow::SUBMIT_ACTION_SAVE) {
            $nextPage = $form->getCurrentPage();
        } else {
            $nextPage = $form->getNextPage(null, $submission);
        }

        if (!$nextPage && $request->submitAction === SubmissionWorkflow::SUBMIT_ACTION_BACK) {
            $nextPage = $form->getCurrentPage();
        }

        if ($request->submitAction === SubmissionWorkflow::SUBMIT_ACTION_SUBMIT) {
            $submission->setScenario(Element::SCENARIO_LIVE);
            $submission->validateCurrentPageOnly = true;
        }

        if (empty($nextPage)) {
            if ($defaultStatus = $form->getDefaultStatus()) {
                $submission->setStatus($defaultStatus);
            }

            $submission->isIncomplete = false;
            $submission->validateCurrentPageOnly = false;
            // Last reachable page (conditions may have skipped later pages).
            $context->becameComplete = true;
        } else {
            $submission->isIncomplete = true;
            $context->becameComplete = false;
        }

        $context->nextPage = $nextPage;

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
