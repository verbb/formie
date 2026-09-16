<?php
namespace verbb\formie\workflow\tasks\save;

use verbb\formie\base\Payment as PaymentIntegration;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\models\PaymentDecision;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\fields as formiefields;

use Craft;

use Throwable;

use Money\Currencies\ISOCurrencies;
use Money\Currency;

class ProcessPaymentsTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::SAVE->value;
    }

    public function getName(): string
    {
        return Task::SAVE_PROCESS_PAYMENTS->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        $paymentDecision = $context->request->processMode === SubmissionWorkflow::PROCESS_MODE_PAYMENT_REPLAY
            ? $this->_replayStoredPayments($context)
            : $this->_processPayments($context);
        $context->taskState['payment.decision'] = $paymentDecision;
        $submission = $context->request->submission;

        if ($context->request->processMode === SubmissionWorkflow::PROCESS_MODE_PAYMENT_REPLAY) {
            if (in_array($paymentDecision->status, [PaymentDecision::STATUS_SUCCEEDED, PaymentDecision::STATUS_NOT_REQUIRED], true)) {
                if ($defaultStatus = $context->request->form->getDefaultStatus()) {
                    $submission->setStatus($defaultStatus);
                }

                $submission->isIncomplete = false;
                $submission->validateCurrentPageOnly = false;
                $context->becameComplete = true;
            } else {
                $submission->isIncomplete = true;
                $context->becameComplete = false;
            }

            return TaskResult::continue();
        }

        if (!in_array($paymentDecision->status, [PaymentDecision::STATUS_SUCCEEDED, PaymentDecision::STATUS_NOT_REQUIRED], true)) {
            $submission->isIncomplete = true;
            $context->becameComplete = false;
            Craft::$app->getElements()->saveElement($submission, false);
        }

        return TaskResult::continue();
    }


    // Private Methods
    // =========================================================================

    private function _processPayments(WorkflowContext $context): PaymentDecision
    {
        // Always defer payment processing until the final submit step.
        if ($context->request->submitAction === SubmissionWorkflow::SUBMIT_ACTION_SUBMIT && $context->nextPage) {
            return PaymentDecision::notRequired();
        }

        if (($context->taskState['payment.required'] ?? null) === false) {
            return PaymentDecision::notRequired();
        }

        $submission = $context->request->submission;
        $currentPageId = (int)($context->request->form->getCurrentPage()?->id ?? 0);
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

            if (in_array($fieldDecision->status, [PaymentDecision::STATUS_FAILED, PaymentDecision::STATUS_ACTION_REQUIRED, PaymentDecision::STATUS_PENDING], true)) {
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

        $submission = $context->request->submission;
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

            // Gateway success verifies the original purchase. Completion also
            // requires that purchase to cover the submission as it exists now.
            if ($storedPayment->status === PaymentModel::STATUS_SUCCESS
                && (!$paymentIntegration instanceof PaymentIntegration || !$this->_matchesPaymentRequirement($paymentIntegration, $submission, $storedPayment))) {
                $message = Craft::t('formie', 'The saved payment does not match the current amount and currency. Review the payment before completing this submission.');
                $submission->addError($field->errorKey(), $message);

                return PaymentDecision::failed($message, $paymentIntegration->handle ?? null, $storedPayment->reference);
            }

            $decision = $decision->merge($this->_decisionFromStoredPayment($storedPayment, $paymentIntegration->handle ?? null));

            if (in_array($decision->status, [PaymentDecision::STATUS_FAILED, PaymentDecision::STATUS_PENDING, PaymentDecision::STATUS_ACTION_REQUIRED], true)) {
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

            $precision = (new ISOCurrencies())->subunitFor(new Currency($currency));
            $amount = $integration->getPaymentAmount($submission);

            return is_finite($amount) && is_finite($payment->amount) && $amount > 0
                && abs(round($amount, $precision) - round($payment->amount, $precision)) < 0.00000001;
        } catch (Throwable) {
            // Missing or invalid provider settings cannot establish a paid total.
            return false;
        }
    }

    private function _resolveLatestStoredPayment(array $payments, int $fieldId, int $integrationId): ?PaymentModel
    {
        foreach (array_reverse($payments) as $payment) {
            if (!$payment instanceof PaymentModel) {
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
            PaymentModel::STATUS_SUCCESS => PaymentDecision::succeeded($provider, $payment->reference),
            PaymentModel::STATUS_FAILED => PaymentDecision::failed($payment->message, $provider, $payment->reference),
            PaymentModel::STATUS_REDIRECT,
            PaymentModel::STATUS_PENDING,
            PaymentModel::STATUS_PROCESSING => PaymentDecision::pending($payment->message, $provider, $payment->reference),
            default => PaymentDecision::notRequired(),
        };
    }
}
