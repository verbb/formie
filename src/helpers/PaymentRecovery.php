<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\base\Payment as PaymentIntegration;
use verbb\formie\integrations\payments\Bpoint;
use verbb\formie\integrations\payments\Eway;
use verbb\formie\integrations\payments\Mollie;
use verbb\formie\integrations\payments\Moneris;
use verbb\formie\integrations\payments\Opayo;
use verbb\formie\integrations\payments\Paddle;
use verbb\formie\models\Payment;

use Craft;
use craft\db\Query;

use DateTime;
use ReflectionMethod;
use RuntimeException;

class PaymentRecovery
{
    // Static Methods
    // =========================================================================

    public static function inspect(int $paymentId): array
    {
        $payment = self::_load($paymentId);
        $attempt = new DeliveryAttempt((int)$payment->submissionId, 'payment-purchase', (string)$payment->uid);
        $meta = $attempt->getMetadata();

        return [
            'id' => $payment->id,
            'uid' => $payment->uid,
            'submissionId' => $payment->submissionId,
            'integrationId' => $payment->integrationId,
            'fieldId' => $payment->fieldId,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'status' => $payment->status,
            'reference' => $payment->reference,
            'merchantReference' => (new PaymentAttempt($payment))->merchantReference(),
            'deliveryState' => $meta['state'] ?? null,
            'startedAt' => isset($meta['startedAt']) ? gmdate('c', $meta['startedAt']) : null,
        ];
    }

    public static function resolve(int $paymentId, string $outcome, float $amount, string $currency, string $reference, string $note): Payment
    {
        $payment = self::_load($paymentId);
        $mutex = Craft::$app->getMutex();
        $lock = PaymentAttempt::lockName((int)$payment->submissionId, (int)$payment->integrationId, (int)$payment->fieldId);

        if (!$mutex->acquire($lock, 10)) {
            throw new RuntimeException('The payment is being processed. Try again later.');
        }

        try {
            $payment = self::_load($paymentId);

            if (!in_array($payment->status, [Payment::STATUS_PENDING, Payment::STATUS_PROCESSING, Payment::STATUS_REDIRECT], true)) {
                throw new RuntimeException('Only unresolved payments can be resolved.');
            }

            $owner = (new DeliveryAttempt((int)$payment->submissionId, 'payment-owner', (string)$payment->uid))->getMetadata();
            $integration = $payment->getIntegration();

            // Older attempts may predate the ownership ledger. Only the explicit
            // operator flow can resolve them; never invent a trusted account record.
            $supportsRecovery = $integration instanceof Bpoint || $integration instanceof Eway
                || $integration instanceof Mollie || $integration instanceof Moneris
                || $integration instanceof Opayo || $integration instanceof Paddle;

            if ($payment->subscriptionId || (!$owner && !$supportsRecovery)) {
                throw new RuntimeException('This payment uses a different recovery flow. Use its gateway status check.');
            }

            if (!in_array($outcome, [Payment::STATUS_SUCCESS, Payment::STATUS_FAILED], true) || !is_finite($amount)
                || abs($amount - $payment->amount) > 0.00000001 || $currency !== $payment->currency
                || trim($note) === '' || strlen($note) > 2000 || strlen($reference) > 255) {
                throw new RuntimeException('Supply a verified outcome, the exact amount and currency, and a recovery note.');
            }

            $reference = trim($reference);

            if ($outcome === Payment::STATUS_SUCCESS && $reference === '') {
                throw new RuntimeException('A successful payment requires the verified gateway reference.');
            }

            if ($payment->reference && $reference !== $payment->reference) {
                throw new RuntimeException('The reference does not match the saved payment.');
            }

            if ($reference !== '') {
                if ((new Query())->from(Table::FORMIE_PAYMENTS)->where(['integrationId' => $payment->integrationId, 'reference' => $reference])
                    ->andWhere(['not', ['id' => $payment->id]])->exists()) {
                    throw new RuntimeException('That gateway reference already belongs to another payment.');
                }

                DeliveryAttempt::claimResource((int)$payment->submissionId, 'payment-recovery:' . $payment->integrationId, $reference, (int)$payment->fieldId);
            }

            $payment->status = $outcome;
            $payment->reference = $reference ?: null;
            $payment->message = 'Outcome verified by an operator.';
            $payment->note = trim($payment->note . "\n\n" . (new DateTime())->format(DATE_ATOM) . ' — ' . $outcome . ': ' . trim($note));

            if (!Formie::$plugin->getPayments()->savePayment($payment)) {
                throw new RuntimeException('Unable to save the verified payment outcome.');
            }

            return $payment;
        } finally {
            $mutex->release($lock);
        }
    }

    public static function reconcile(int $paymentId): Payment
    {
        $payment = self::_load($paymentId);
        $integration = $payment->getIntegration();

        if (!$integration instanceof PaymentIntegration || (!$payment->reference && !$integration instanceof Eway)
            || (new ReflectionMethod($integration, 'getTransaction'))->getDeclaringClass()->getName() === PaymentIntegration::class) {
            throw new RuntimeException('Automatic lookup is unavailable. Check the merchant reference in the gateway, then record its verified outcome.');
        }

        $integration->setField($payment->getField());
        $integration->getTransaction($payment);

        return self::_load($paymentId);
    }

    public static function resume(int $paymentId): void
    {
        $payment = self::_load($paymentId);

        if ($payment->status !== Payment::STATUS_SUCCESS) {
            throw new RuntimeException('Only a verified successful payment can resume submission processing.');
        }

        if (!$payment->getSubmission()) {
            throw new RuntimeException('The payment’s submission is unavailable.');
        }

        $result = Formie::$plugin->getSubmissionProcessor()->replayPaymentIfSuccessful($payment);

        if ($result && !$result->response?->success) {
            throw new RuntimeException('The payment remains verified, but submission processing failed. Check its errors before resuming again.');
        }
    }

    private static function _load(int $paymentId): Payment
    {
        $row = Craft::$app->getDb()->useMaster(fn() => (new Query())->from(Table::FORMIE_PAYMENTS)->where(['id' => $paymentId])->one());

        if (!$row) {
            throw new RuntimeException('Payment not found.');
        }

        return new Payment($row);
    }
}
