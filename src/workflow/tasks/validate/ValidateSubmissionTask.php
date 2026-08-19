<?php
namespace verbb\formie\workflow\tasks\validate;

use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\helpers\SubmissionEditBehaviour;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

use craft\base\Element;

class ValidateSubmissionTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::VALIDATE->value;
    }

    public function getName(): string
    {
        return Task::VALIDATE_SUBMISSION->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        if ($context->request->submitAction === SubmissionWorkflow::SUBMIT_ACTION_BACK) {
            return TaskResult::continue();
        }

        $submission = $context->request->submission;
        $form = $context->request->form;
        $currentPage = $form->getCurrentPage();
        $submission->setScenario(Element::SCENARIO_LIVE);

        $editBehaviour = SubmissionEditBehaviour::resolve($context->request);

        if (SubmissionEditBehaviour::isRevision($editBehaviour)) {
            // Revision edits (CP and completed front-end) validate the whole submission,
            // not a single page tab from visitor progression semantics.
            $submission->validateCurrentPageOnly = false;
        } else {
            // Final page = no next *visible* page for this submission (same contract as ResolvePageFlowTask).
            // Structural isLastPage() without $submission would keep validating only the current page when
            // a later page is conditionally hidden, while still marking the submission complete (#2927).
            $submission->validateCurrentPageOnly = !$form->isLastPage($currentPage, $submission);
        }

        $submission->validate();

        return TaskResult::continue();
    }
}
