<?php
namespace verbb\formie\workflow\tasks\finalize;

use verbb\formie\Formie;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\helpers\Table;
use verbb\formie\models\Settings;
use verbb\formie\services\CompletionResolver;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

use Craft;
use craft\helpers\Json;

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
                Formie::$plugin->getSubmissionProgress()->upsertProgressState($form, $submission, $context->nextPage->id);
            } elseif ($submission->isIncomplete && $submission->id) {
                $form->setCurrentSubmission($submission);
                Formie::$plugin->getSubmissionProgress()->upsertProgressState($form, $submission, $form->getCurrentPage()?->id);
            } elseif (!$submission->isIncomplete) {
                $form->resetCurrentPage();
                $form->resetCurrentSubmission();
            }
        }

        if (!$submission->isIncomplete && $submission->id) {
            Formie::$plugin->getSubmissionProgress()->complete((int)$submission->id);
        }

        if ($type !== SubmissionOutcomeType::PAYMENT_FAILED && $command->requestToken) {
            Formie::$plugin->getSubmissionGuards()->consumeReplayToken((string)$form->uid, $command->requestToken);
        }

        $data = [
            'fakeSuccess' => $fakeSuccess,
            'quizResultId' => ($context->taskState['questionnaireScoring.result'] ?? null)?->id,
        ];

        if ($type === SubmissionOutcomeType::COMPLETED) {
            $completion = (new CompletionResolver())->resolve($submission->getForm(), $submission);
            $data['completion'] = $completion->toArray();

            if ($submission->id) {
                $submission->mergeMetadata(['completion' => $completion->toArray()]);
                Craft::$app->getDb()->createCommand()->update(
                    Table::FORMIE_SUBMISSIONS,
                    ['metadata' => Json::encode($submission->metadata)],
                    ['id' => $submission->id]
                )->execute();
            }
            $data['redirect'] = $completion->url ? ['url' => $completion->url, 'target' => $completion->target->value] : null;
        }

        return TaskResult::stop($context->result($type, $data));
    }
}
