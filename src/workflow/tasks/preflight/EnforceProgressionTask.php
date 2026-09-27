<?php
namespace verbb\formie\workflow\tasks\preflight;

use verbb\formie\enums\NavigationIntent;
use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

use Craft;

class EnforceProgressionTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function execute(WorkflowContext $context): TaskResult
    {
        $command = $context->command;

        if (!in_array($command->navigation, [NavigationIntent::ADVANCE, NavigationIntent::TARGET], true)) {
            return TaskResult::continue();
        }

        $page = $command->form->getCurrentPage();
        if ($command->navigation === NavigationIntent::TARGET) {
            foreach ($command->form->getPages() as $target) {
                if ((int)$target->id === $command->targetPageId && $command->form->getPageIndex($target) <= $command->form->getPageIndex($page)) {
                    return TaskResult::continue();
                }
            }
        }
        $conditions = $page?->getSubmitButtonConditions() ?? [];

        if ($page?->hasSubmitButtonConditions() && $conditions) {
            $evaluation = ConditionsHelper::evaluate($conditions, $command->submission, 'routing');
            $allowed = $evaluation->permits(($conditions['showRule'] ?? 'show') === 'show');

            if (!$allowed) {
                $command->submission->addError('form', Craft::t('formie', 'Complete the requirements on this page before continuing.'));
                return TaskResult::stop($context->result(SubmissionOutcomeType::VALIDATION_FAILED));
            }
        }

        return TaskResult::continue();
    }
}
