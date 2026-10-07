<?php
namespace verbb\formie\workflow\tasks\persist;

use verbb\formie\Formie;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\fields\Payment;
use verbb\formie\helpers\Table;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

use craft\db\Query;

class PlanTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function execute(WorkflowContext $context): TaskResult
    {
        $command = $context->command;
        $submission = $command->submission;
        $context->attemptedCompletion = $context->attemptedCompletion
            || $command->operation === SubmissionOperation::PAYMENT_REPLAY;
        $requiresPayment = false;

        if ($context->attemptedCompletion && !$submission->isSpam) {
            foreach ($submission->getFields() as $field) {
                if ($field instanceof Payment && !$field->getIsDisabled() && !$field->isConditionallyHidden($submission) && $field->getPaymentIntegration()) {
                    $requiresPayment = true;
                    break;
                }
            }
        }

        $context->taskState['payment.required'] = $requiresPayment;
        $context->taskState['persist.discardSpam'] = $submission->isSpam && in_array($command->operation, [SubmissionOperation::SUBMIT, SubmissionOperation::PAYMENT_REPLAY], true)
            && !Formie::$plugin->getSettings()->shouldSaveSpam($submission);

        $wasIncomplete = !$submission->id || $submission->isIncomplete;

        if ($command->operation === SubmissionOperation::SAVE_DRAFT) {
            $submission->isIncomplete = true;
        } elseif ($command->usesVisitorProgression()) {
            $submission->isIncomplete = !$context->attemptedCompletion || $requiresPayment;
            $context->becameComplete = $context->attemptedCompletion && !$requiresPayment && $wasIncomplete;
        }

        if ($command->operation === SubmissionOperation::REVISE && !$submission->isIncomplete) {
            $context->becameComplete = (bool)(new Query())->select('isIncomplete')
                ->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $submission->id])->scalar();
        }

        return TaskResult::continue();
    }
}
