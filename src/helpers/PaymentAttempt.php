<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Payment as PaymentIntegration;
use verbb\formie\elements\Submission;
use verbb\formie\errors\DeliveryOutcomeUnknownException;
use verbb\formie\models\Payment;
use verbb\formie\models\PaymentDecision;

use Craft;
use craft\helpers\App;

use RuntimeException;
use Throwable;

use Money\Currencies\ISOCurrencies;
use Money\Currency;

class PaymentAttempt
{
    // Static Methods
    // =========================================================================

    public static function run(PaymentIntegration $integration, Submission $submission, float $amount, ?string $currency, array $account, callable $process, bool $allowCreate = true): PaymentDecision
    {
        $mutex = Craft::$app->getMutex();
        $lock = self::lockName((int)$submission->id, (int)$integration->id, (int)$integration->getField()?->id);
        $locked = false;
        $attempt = null;
        $db = Craft::$app->getDb();
        $enableSlaves = $db->enableSlaves;

        try {
            // Ownership and delivery state must reflect the last committed
            // attempt, including when the site uses lagging read replicas.
            $db->enableSlaves = false;

            if (!$submission->id || !$integration->id || !$integration->getField()?->id || !is_finite($amount) || $amount <= 0 || !$currency) {
                throw new RuntimeException('Invalid payment owner, amount or currency.');
            }

            $amount = round($amount, (new ISOCurrencies())->subunitFor(new Currency($currency)));

            if ($amount <= 0) {
                throw new RuntimeException('The payment amount is below the currency’s smallest unit.');
            }

            if (Craft::$app->getDb()->getTransaction()?->getIsActive()) {
                throw new RuntimeException('Commit the submission before starting payment.');
            }

            $locked = $mutex->acquire($lock, 10);

            if (!$locked) {
                throw new DeliveryOutcomeUnknownException('This payment is already being processed. Please wait before retrying.');
            }

            $payments = Formie::$plugin->getPayments();
            $payment = null;

            foreach (array_reverse($payments->getSubmissionPayments($submission)) as $candidate) {
                if ($candidate->integrationId === $integration->id && $candidate->fieldId === $integration->getField()->id && $candidate->status !== Payment::STATUS_FAILED) {
                    $payment = $candidate;
                    break;
                }
            }

            if ($payment && ($payment->currency !== $currency || abs($payment->amount - $amount) > 0.00000001)) {
                throw new RuntimeException('The payment amount changed. Review the existing payment before retrying.');
            }

            if (!$payment && !$allowCreate) {
                throw new RuntimeException('No saved payment matches this completion request.');
            }

            $isNew = !$payment;
            $payment ??= new Payment([
                'submissionId' => $submission->id,
                'integrationId' => $integration->id,
                'fieldId' => $integration->getField()->id,
                'amount' => $amount,
                'currency' => $currency,
                'status' => Payment::STATUS_PENDING,
            ]);

            if ($isNew && !$payments->savePayment($payment)) {
                throw new RuntimeException('Unable to save the pending payment.');
            }

            $attempt = new self($payment);
            $owner = new DeliveryAttempt((int)$submission->id, 'payment-owner', (string)$payment->uid);
            $knownOwner = $owner->getMetadata() !== null;

            if ($payment->status === Payment::STATUS_SUCCESS) {
                return PaymentDecision::succeeded($integration->handle, $payment->reference);
            }

            self::verifyAccount($integration, $payment, $account, requireExisting: false);

            if (!$isNew && !$knownOwner && !$payment->reference && !$payment->response) {
                throw new DeliveryOutcomeUnknownException('This earlier payment has no saved outcome. Check the gateway before retrying.');
            }

            return $process($payment, $attempt);
        } catch (Throwable $e) {
            $unknown = $e instanceof DeliveryOutcomeUnknownException;

            if ($attempt) {
                try {
                    $attempt->recordError($e->getMessage(), $unknown || $attempt->hasSent());
                } catch (Throwable $saveError) {
                    Formie::error('Unable to save payment recovery status: ' . $saveError->getMessage());
                }
            }

            Integration::apiError($integration, $e, false);
            if ($field = $integration->getField()) {
                $submission->addError($field->errorKey(), $e->getMessage());
            }

            return $unknown
                ? PaymentDecision::pending($e->getMessage(), $integration->handle, $attempt?->payment->reference)
                : PaymentDecision::failed($e->getMessage(), $integration->handle, $attempt?->payment->reference);
        } finally {
            $db->enableSlaves = $enableSlaves;

            if ($locked) {
                $mutex->release($lock);
            }
        }
    }

    public static function verifyAccount(PaymentIntegration $integration, Payment $payment, array $account, bool $requireExisting = true): void
    {
        $owner = new DeliveryAttempt((int)$payment->submissionId, 'payment-owner', (string)$payment->uid);

        if ($requireExisting && !$owner->getMetadata()) {
            throw new DeliveryOutcomeUnknownException('The original payment account could not be verified.');
        }

        // Resolve environment settings before hashing. Changing credentials must
        // never redirect an unresolved attempt to another account.
        $owner->execute([
            'integration' => get_class($integration),
            'account' => array_map(static fn($value) => is_string($value) ? App::parseEnv($value) : $value, $account),
            'amount' => $payment->amount,
            'currency' => $payment->currency,
        ], static fn() => true);
    }

    public static function lockName(int $submissionId, int $integrationId, int $fieldId): string
    {
        return 'formie.payment-attempt.' . $submissionId . '.' . $integrationId . '.' . $fieldId;
    }


    // Properties
    // =========================================================================

    public Payment $payment;

    private bool $_rejected = false;


    // Public Methods
    // =========================================================================

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    public function merchantReference(): string
    {
        return 'fm' . str_replace('-', '', (string)$this->payment->uid);
    }

    public function request(array $payload, callable $send, callable $reference): array
    {
        $delivery = $this->_delivery();

        // A durable gateway receipt can be processed again even if the visitor's
        // card token has expired or changed. No new charge is sent in this path.
        if ($this->hasReceipt()) {
            return $this->payment->response;
        }

        if ($this->payment->reference) {
            throw new DeliveryOutcomeUnknownException('The saved payment needs gateway verification before it can be retried.');
        }

        $result = $delivery->execute($payload, function(string $key) use ($send, $reference): array {
            $response = $send($key);

            if (!is_array($response) || !$response) {
                throw new RuntimeException('The gateway did not return a payment receipt.');
            }

            $id = $reference($response);
            $this->payment->reference = is_scalar($id) && (string)$id !== '' ? (string)$id : null;
            $this->payment->response = $response;
            $this->save();

            return $response;
        });

        if (!is_array($result)) {
            throw new DeliveryOutcomeUnknownException('The payment was sent, but its receipt is unavailable. Check the gateway before retrying.');
        }

        return $result;
    }

    public function reject(string $message): never
    {
        $this->_rejected = true;
        $this->payment->status = Payment::STATUS_FAILED;
        $this->payment->message = $message;
        $this->save();
        throw new RuntimeException($message);
    }

    public function hasReceipt(): bool
    {
        return (bool)$this->payment->response && $this->_delivery()->getMetadata() !== null;
    }

    public function hasSent(): bool
    {
        return !$this->_rejected && $this->_delivery()->getMetadata() !== null;
    }

    public function save(): void
    {
        if (!Formie::$plugin->getPayments()->savePayment($this->payment)) {
            throw new RuntimeException('Unable to save the payment outcome.');
        }
    }

    public function recordError(string $message, bool $unknown): void
    {
        // Leave a saved receipt/reference intact for the next verification.
        if ($this->_rejected || $this->payment->status === Payment::STATUS_SUCCESS) {
            return;
        }

        $this->payment->status = $unknown ? Payment::STATUS_PENDING : Payment::STATUS_FAILED;
        $this->payment->message = $message;
        $this->save();
    }


    // Private Methods
    // =========================================================================

    private function _delivery(): DeliveryAttempt
    {
        return new DeliveryAttempt((int)$this->payment->submissionId, 'payment-purchase', (string)$this->payment->uid);
    }
}
