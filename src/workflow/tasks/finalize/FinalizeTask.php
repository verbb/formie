<?php
namespace verbb\formie\workflow\tasks\finalize;

use verbb\formie\Formie;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\models\Settings;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

use Craft;

class FinalizeTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function execute(WorkflowContext $context): TaskResult
    {
        $command = $context->command;
        $submission = $command->submission;
        $form = $command->form;
        $settings = Formie::$plugin->getSettings();
        $type = $context->outcome?->type;

        if ($type === null) {
            $type = match (true) {
                $submission->isSpam => SubmissionOutcomeType::REJECTED,
                $command->operation === SubmissionOperation::SAVE_DRAFT => SubmissionOutcomeType::DRAFT_SAVED,
                $command->operation === SubmissionOperation::REVISE => SubmissionOutcomeType::REVISED,
                $context->nextPage !== null => SubmissionOutcomeType::PAGE_CHANGED,
                default => SubmissionOutcomeType::COMPLETED,
            };
        }

        $fakeSuccess = $submission->isSpam && $settings->spamBehaviour === Settings::SPAM_BEHAVIOUR_SUCCESS;
        if ($submission->isSpam && !$fakeSuccess) {
            $submission->addError('form', $settings->spamBehaviourMessage ?: Craft::t('formie', 'Your submission could not be accepted.'));
        }

        if ($command->usesVisitorProgression()) {
            if ($context->nextPage) {
                $form->setCurrentPage($context->nextPage);
                $form->setCurrentSubmission($submission);
                Formie::$plugin->getSubmissionDrafts()->upsertProgressState($form, $submission, $context->nextPage->id);
            } elseif ($submission->isIncomplete && $submission->id) {
                $form->setCurrentSubmission($submission);
                Formie::$plugin->getSubmissionDrafts()->upsertProgressState($form, $submission, $form->getCurrentPage()?->id);
            } elseif (!$submission->isIncomplete) {
                Formie::$plugin->getSubmissionDrafts()->clearProgressState($form);
                $form->resetCurrentPage();
                $form->resetCurrentSubmission();
            }
        }

        if (!$submission->isIncomplete && $submission->id) {
            Formie::$plugin->getFileUploads()->finalizeSubmissionUploads((int)$submission->id);
        }

        if ($type !== SubmissionOutcomeType::PAYMENT_FAILED && $command->requestToken) {
            Formie::$plugin->getSubmissionGuards()->consumeReplayToken((string)$form->uid, $command->requestToken);
        }

        return TaskResult::stop($context->result($type, [
            'fakeSuccess' => $fakeSuccess,
            'quizResultId' => ($context->taskState['questionnaireScoring.result'] ?? null)?->id,
        ]));
    }
}
