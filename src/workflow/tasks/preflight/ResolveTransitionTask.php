<?php
namespace verbb\formie\workflow\tasks\preflight;

use verbb\formie\enums\NavigationIntent;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

use Craft;

class ResolveTransitionTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function execute(WorkflowContext $context): TaskResult
    {
        $command = $context->command;
        $form = $command->form;
        $submission = $command->submission;
        $current = $form->getCurrentPage();

        // Page visibility is optimistic in the browser; an invalid route must never become completion.
        if (in_array($command->navigation, [NavigationIntent::ADVANCE, NavigationIntent::TARGET], true)) {
            foreach ($form->getPages() as $page) {
                if ($page->hasConditions() && ConditionsHelper::evaluate($page->getConditions(), $submission, 'routing')->value === null) {
                    $submission->addError('form', Craft::t('formie', 'The requested page is unavailable.'));
                    return TaskResult::stop($context->result(SubmissionOutcomeType::VALIDATION_FAILED));
                }
            }
        }
        $next = match ($command->navigation) {
            NavigationIntent::BACK => $form->getPreviousPage($current, $submission, true) ?? $current,
            NavigationIntent::STAY => $current,
            NavigationIntent::ADVANCE => $form->getNextPage($current, $submission),
            NavigationIntent::TARGET => null,
        };

        if ($command->navigation === NavigationIntent::TARGET) {
            $forward = $form->getNextPage($current, $submission);
            $pages = $form->getPages();
            $currentIndex = array_search($current, $pages, true);

            foreach ($pages as $index => $page) {
                if ((int)$page->id !== $command->targetPageId || $page->isConditionallyHidden($submission)) {
                    continue;
                }

                // A target cannot jump over an unvalidated page. Earlier visible pages remain reachable.
                if ($index <= $currentIndex || $page === $forward) {
                    $next = $page;
                }
            }

            if (!$next) {
                $submission->addError('form', Craft::t('formie', 'The requested page is unavailable.'));
                return TaskResult::stop($context->result(SubmissionOutcomeType::VALIDATION_FAILED));
            }
        }

        $context->taskState['navigation.backward'] = $next && $form->getPageIndex($next) <= $form->getPageIndex($current);
        $context->nextPage = $next;
        $context->attemptedCompletion = $command->operation === SubmissionOperation::SUBMIT
            && $command->navigation === NavigationIntent::ADVANCE && $next === null;
        $submission->validateCurrentPageOnly = !$context->attemptedCompletion;

        // Completion is persistence policy, never a navigation side effect.
        return TaskResult::continue();
    }
}
