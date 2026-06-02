<?php
namespace verbb\formie\workflow\tasks\save;

use verbb\formie\Formie;
use verbb\formie\models\PaymentDecision;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

use craft\helpers\Json;

class ApplyCompletionFromPaymentStateTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::SAVE->value;
    }

    public function getName(): string
    {
        return Task::SAVE_APPLY_COMPLETION_FROM_PAYMENT_STATE->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        $paymentDecision = $context->taskState['payment.decision'] ?? PaymentDecision::notRequired();

        if (!$paymentDecision instanceof PaymentDecision) {
            $paymentDecision = PaymentDecision::notRequired();
        }

        $context->response->paymentStatus = $paymentDecision->status;
        $context->response->paymentMessage = $paymentDecision->message;
        $context->response->paymentRedirectUrl = $paymentDecision->redirectUrl;
        $context->response->paymentAction = $paymentDecision->action;
        $context->response->paymentDecision = $paymentDecision->toArray();

        if ($paymentDecision->status === PaymentDecision::STATUS_FAILED) {
            return TaskResult::halt(false, ['reason' => 'paymentFailed', 'payment' => $paymentDecision->toArray()]);
        }

        if (in_array($paymentDecision->status, [PaymentDecision::STATUS_ACTION_REQUIRED, PaymentDecision::STATUS_PENDING], true)) {
            $errorsBeforeClear = $context->request->submission->getErrors();
            Formie::info('Payment follow-up required in save stage (status: "{status}", message: "{message}", action: {action}, errorsBeforeClear: {errors}).', [
                'status' => $paymentDecision->status,
                'message' => (string)($paymentDecision->message ?? ''),
                'action' => Json::encode($paymentDecision->action ?? []),
                'errors' => Json::encode($errorsBeforeClear),
            ]);

            // Integrations historically used field errors to short-circuit redirect/3DS flows.
            // Clear those so this path is treated as a non-error action-required response.
            $context->request->submission->clearErrors();

            return TaskResult::halt(false, ['reason' => 'paymentActionRequired', 'payment' => $paymentDecision->toArray()]);
        }

        return TaskResult::continue();
    }
}
