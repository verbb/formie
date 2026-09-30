<?php
namespace verbb\formie\workflow\tasks\persist;

use verbb\formie\Formie;
use verbb\formie\base\Payment as PaymentIntegration;
use verbb\formie\elements\Submission;
use verbb\formie\enums\NavigationIntent;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\fields as formiefields;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\models\PaymentDecision;
use verbb\formie\models\PaymentMoney;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

use Craft;

use RuntimeException;
use Throwable;

use Money\Currencies\ISOCurrencies;
use Money\Currency;

class ProcessPaymentTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function execute(WorkflowContext $context): TaskResult
    {
        if ($context->taskState['persist.discardSpam'] ?? false) {
            return TaskResult::continue();
        }

        $decision = $context->command->operation === SubmissionOperation::PAYMENT_REPLAY
            ? $this->_replayStoredPayments($context)
            : Formie::$plugin->getPayments()->observeProvider(fn() => $this->_processPayments($context));
        $context->paymentDecision = $decision;
        $submission = $context->command->submission;
        $requiresPayment = $context->taskState['payment.required'] ?? false;

        if ($requiresPayment || $context->command->operation === SubmissionOperation::PAYMENT_REPLAY) {
            $completed = in_array($decision->status, [PaymentDecision::STATUS_SUCCEEDED, PaymentDecision::STATUS_NOT_REQUIRED], true);
            $uploads = Formie::$plugin->getFileUploads();
            $uploads->withUploadLocks($submission, function () use ($context, $completed, $uploads): void {
                // PaymentReplay skips the content-persistence task, so it must also
                // finish any durable promotion intent before permitting completion.
                if ($completed) {
                    $uploads->promoteAccepted($context->command->submission);
                }
                $this->_persistDecision($context, $completed);
            });
            $context->processingSuccess = true;
            $context->taskState['save.success'] = true;
        }

        $type = match ($decision->status) {
            PaymentDecision::STATUS_CANCELLED, PaymentDecision::STATUS_FAILED => SubmissionOutcomeType::PAYMENT_FAILED,
            PaymentDecision::STATUS_ACTION_REQUIRED => SubmissionOutcomeType::PAYMENT_ACTION_REQUIRED,
            PaymentDecision::STATUS_UNKNOWN, PaymentDecision::STATUS_PENDING => SubmissionOutcomeType::PAYMENT_PENDING,
            default => null,
        };

        if ($type !== null) {
            if ($type !== SubmissionOutcomeType::PAYMENT_FAILED) {
                $submission->clearErrors();
            }
            return TaskResult::stop($context->result($type));
        }

        return TaskResult::continue();
    }


    // Private Methods
    // =========================================================================

    private function _persistDecision(WorkflowContext $context, bool $completed): void
    {
        $submission = $context->command->submission;
        $wasIncomplete = $submission->isIncomplete;
        $context->becameComplete = $completed && $wasIncomplete;
        $submission->isIncomplete = !$completed;

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Provider requests and evidence recording have finished outside this transaction.
            // Commit payment state, submission completion and upload finalization together.
            foreach (Formie::$plugin->getPayments()->getSubmissionPayments($submission) as $payment) {
                if ($payment->scope['initial'] ?? false) {
                    if (($payment->scope['providerOutcome']['status'] ?? null) === PaymentModel::STATUS_SUCCEEDED) {
                        $payment->status = PaymentModel::STATUS_SUCCEEDED;
                    }
                    $payment->scope['submissionTransition'] = ['complete' => $completed, 'decision' => $context->paymentDecision->status->value,
                        'operationId' => $context->command->operationId, 'expectedVersion' => $context->command->expectedVersion];
                    if (!Formie::$plugin->getPayments()->commitTransition($payment)) {
                        throw new RuntimeException('Unable to persist the payment transition.');
                    }
                }
            }
            if (!Craft::$app->getElements()->saveElement($submission, false)) {
                throw new RuntimeException('Unable to persist payment/submission state.');
            }
            if ($completed) {
                Formie::$plugin->getFileUploads()->finalizeSubmissionUploads((int)$submission->id);
                Formie::$plugin->getSubmissionDispatches()->recordIntent($context);
            }
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            $submission->isIncomplete = $wasIncomplete;
            $context->becameComplete = false;
            throw $e;
        }
    }

    private function _processPayments(WorkflowContext $context): PaymentDecision
    {
        // Always defer payment processing until the final submit step.
        if ($context->command->navigation === NavigationIntent::ADVANCE && $context->nextPage) {
            return PaymentDecision::notRequired();
        }

        if (($context->taskState['payment.required'] ?? null) === false) {
            return PaymentDecision::notRequired();
        }

        $submission = $context->command->submission;
        $currentPageId = (int)($context->command->form->getCurrentPage()?->id ?? 0);
        $decision = PaymentDecision::notRequired();
        $paymentFields = [];

        foreach ($submission->getFields() as $field) {
            if (!$field instanceof formiefields\Payment) {
                continue;
            }

            // No need to proceed further if field is conditionally hidden or disabled.
            if ($field->isConditionallyHidden($submission) || $field->getIsDisabled()) {
                continue;
            }

            $paymentIntegration = $field->getPaymentIntegration();

            if (!$paymentIntegration) {
                continue;
            }

            // Validate the whole payment layout before any provider can charge.
            // Payment is deferred on earlier pages, so skipping them here would
            // silently accept an unpaid requirement on the final submit.
            if ($currentPageId > 0 && (int)($field->pageId ?? 0) > 0 && (int)$field->pageId !== $currentPageId) {
                $message = Craft::t('formie', 'Payment field must be placed on the final page to process payment.');
                $submission->addError($field->errorKey(), $message);

                return PaymentDecision::failed($message);
            }

            $paymentFields[] = [$field, $paymentIntegration];
        }

        foreach ($paymentFields as [$field, $paymentIntegration]) {
            $paymentIntegration->setField($field);
            $fieldDecision = $paymentIntegration instanceof PaymentIntegration
                ? $paymentIntegration->resolvePaymentDecision($submission)
                : PaymentDecision::failed(null, $paymentIntegration->handle ?? null);
            $decision = $decision->merge($fieldDecision);

            if (in_array($fieldDecision->status, [PaymentDecision::STATUS_UNKNOWN, PaymentDecision::STATUS_CANCELLED, PaymentDecision::STATUS_FAILED, PaymentDecision::STATUS_ACTION_REQUIRED, PaymentDecision::STATUS_PENDING], true)) {
                break;
            }
        }

        return $decision;
    }

    private function _replayStoredPayments(WorkflowContext $context): PaymentDecision
    {
        if (($context->taskState['payment.required'] ?? null) === false) {
            return PaymentDecision::notRequired();
        }

        $submission = $context->command->submission;
        $payments = Formie::$plugin->getPayments()->getSubmissionPayments($submission);
        $decision = PaymentDecision::notRequired();

        foreach ($submission->getFields() as $field) {
            if (!$field instanceof formiefields\Payment) {
                continue;
            }

            if ($field->isConditionallyHidden($submission) || $field->getIsDisabled()) {
                continue;
            }

            $paymentIntegration = $field->getPaymentIntegration();

            if (!$paymentIntegration) {
                continue;
            }

            $storedPayment = $this->_resolveLatestStoredPayment($payments, (int)$field->id, (int)($paymentIntegration->id ?? 0));

            if (!$storedPayment) {
                return PaymentDecision::failed(
                    Craft::t('formie', 'Unable to resolve stored payment state for replay.'),
                    $paymentIntegration->handle ?? null,
                );
            }

            $paymentIntegration->setField($field);

            if (($storedPayment->scope['providerOutcome']['status'] ?? null) === PaymentModel::STATUS_SUCCEEDED) {
                $storedPayment->status = PaymentModel::STATUS_SUCCEEDED;
            }

            // Gateway success verifies the original purchase. Completion also
            // requires that purchase to cover the submission as it exists now.
            if ($storedPayment->status === PaymentModel::STATUS_SUCCEEDED
                && (!$paymentIntegration instanceof PaymentIntegration || !$this->_matchesPaymentRequirement($paymentIntegration, $submission, $storedPayment))) {
                $message = Craft::t('formie', 'The saved payment does not match the current amount and currency. Review the payment before completing this submission.');
                $submission->addError($field->errorKey(), $message);

                return PaymentDecision::failed($message, $paymentIntegration->handle ?? null, $storedPayment->reference);
            }

            $decision = $decision->merge($this->_decisionFromStoredPayment($storedPayment, $paymentIntegration->handle ?? null));

            if (in_array($decision->status, [PaymentDecision::STATUS_UNKNOWN, PaymentDecision::STATUS_CANCELLED, PaymentDecision::STATUS_FAILED, PaymentDecision::STATUS_PENDING, PaymentDecision::STATUS_ACTION_REQUIRED], true)) {
                break;
            }
        }

        return $decision;
    }

    private function _matchesPaymentRequirement(PaymentIntegration $integration, Submission $submission, PaymentModel $payment): bool
    {
        try {
            $currency = strtoupper((string)$integration->getCurrency($submission));

            if ($currency === '' || $currency !== strtoupper((string)$payment->currency)) {
                return false;
            }

            $amount = PaymentMoney::fromDecimal((string)$integration->getPaymentAmount($submission), $currency);
            return $amount->minor !== '0' && !str_starts_with($amount->minor, '-')
                && $amount->equals(PaymentMoney::fromDecimal($payment->amount, $currency));
        } catch (Throwable) {
            // Missing or invalid provider settings cannot establish a paid total.
            return false;
        }
    }

    private function _resolveLatestStoredPayment(array $payments, int $fieldId, int $integrationId): ?PaymentModel
    {
        foreach (array_reverse($payments) as $payment) {
            if (!$payment instanceof PaymentModel || ($payment->subscriptionId && !($payment->scope['initial'] ?? false))) {
                continue;
            }

            if ((int)$payment->fieldId !== $fieldId) {
                continue;
            }

            if ($integrationId > 0 && (int)$payment->integrationId !== $integrationId) {
                continue;
            }

            return $payment;
        }

        return null;
    }

    private function _decisionFromStoredPayment(PaymentModel $payment, ?string $provider = null): PaymentDecision
    {
        $provider ??= $payment->getIntegration()?->handle ?? null;

        return match ($payment->status) {
            PaymentModel::STATUS_SUCCEEDED => PaymentDecision::succeeded($provider, $payment->reference),
            PaymentModel::STATUS_FAILED => PaymentDecision::failed($payment->message, $provider, $payment->reference),
            PaymentModel::STATUS_REQUIRES_ACTION,
            PaymentModel::STATUS_PENDING,
            PaymentModel::STATUS_PROCESSING => PaymentDecision::pending($payment->message, $provider, $payment->reference),
            PaymentModel::STATUS_CANCELLED => PaymentDecision::cancelled($payment->message, $provider, $payment->reference),
            default => PaymentDecision::unknown($payment->message, $provider, $payment->reference),
        };
    }
}
