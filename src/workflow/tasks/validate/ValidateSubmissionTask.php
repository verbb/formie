<?php
namespace verbb\formie\workflow\tasks\validate;

use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
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
        $submission->validateCurrentPageOnly = !$form->isLastPage($currentPage);
        $submission->validate();

        return TaskResult::continue();
    }
}
