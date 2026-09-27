<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\Payment as PaymentIntegration;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\enums\PaymentResumeMode;
use verbb\formie\events\PaymentEvent;
use verbb\formie\events\PaymentSuccessRedirectEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\DbSchema;
use verbb\formie\helpers\References;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\Payment;
use verbb\formie\models\PaymentMoney;
use verbb\formie\models\payments\PaymentStatusCommand;
use verbb\formie\models\Subscription;
use verbb\formie\records\Payment as PaymentRecord;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\App;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\models\FieldLayout;

use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\web\ServerErrorHttpException;

use RuntimeException;
use Throwable;

class Payments extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_PAYMENT = 'beforeSavePayment';
    public const EVENT_AFTER_SAVE_PAYMENT = 'afterSavePayment';
    public const EVENT_BEFORE_DELETE_PAYMENT = 'beforeDeletePayment';
    public const EVENT_AFTER_DELETE_PAYMENT = 'afterDeletePayment';
    public const EVENT_DEFINE_PAYMENT_SUCCESS_REDIRECT_URL = 'definePaymentSuccessRedirectUrl';


    // Properties
    // =========================================================================

    private array $_paymentByKey = [];
    private int $_providerObservationDepth = 0;
    private bool $_committingTransition = false;


    // Public Methods
    // =========================================================================

    public function commitTransition(Payment $payment): bool
    {
        if (!Craft::$app->getDb()->getTransaction()?->getIsActive()) {
            throw new RuntimeException('Payment settlement requires the submission transaction.');
        }
        $previous = $this->_committingTransition;
        $this->_committingTransition = true;
        try {
            return $this->savePayment($payment);
        } finally {
            $this->_committingTransition = $previous;
        }
    }

    public function observeProvider(callable $handler): mixed
    {
        $this->_providerObservationDepth++;
        try {
            return $handler();
        } finally {
            $this->_providerObservationDepth--;
        }
    }

    /** Create the local intent before any remote operation, under the execution lock. */
    public function prepareAttempt(PaymentIntegration $integration, Submission $submission): Payment
    {
        if (!$submission->id || !$integration->id || !$integration->getField()?->id) {
            throw new RuntimeException('Save and authorize the submission before payment.');
        }
        $currency = strtoupper((string)$integration->getCurrency($submission));
        $amount = PaymentMoney::fromDecimal((string)$integration->getPaymentAmount($submission), $currency);
        if ($amount->minor === '0' || str_starts_with($amount->minor, '-')) {
            throw new RuntimeException('The payment amount must be positive.');
        }
        $account = [];
        foreach (['secretKey', 'apiKey', 'accessToken', 'merchantId', 'vendorName', 'integrationKey', 'integrationPassword', 'storeId', 'apiToken', 'useSandbox', 'testMode', 'clientId', 'clientSecret', 'apiPassword', 'username', 'password', 'merchantNumber', 'locationId', 'applicationId', 'profileId'] as $property) {
            if (array_key_exists($property, get_object_vars($integration))) {
                $value = $integration->$property;
                $account[$property] = is_string($value) ? App::parseEnv($value) : $value;
            }
        }
        $accountHash = hash_hmac('sha256', Json::encode($account), Formie::$plugin->getSettings()->getSecurityKey());
        foreach (array_reverse($this->getSubmissionPayments($submission)) as $payment) {
            if ($payment->status === Payment::STATUS_FAILED) { continue; }
            if ($payment->integrationId === $integration->id && $payment->fieldId === $integration->getField()->id && (!$payment->subscriptionId || ($payment->scope['initial'] ?? false))) {
                if (isset($payment->scope['account']) && !hash_equals($payment->scope['account'], $accountHash)) { throw new RuntimeException('Payment account changed; reconcile the original account.'); }
                if (!PaymentMoney::fromDecimal($payment->amount, (string)$payment->currency)->equals($amount)) {
                    throw new RuntimeException('The existing payment amount changed; reconcile it before retrying.');
                }
                return $payment;
            }
        }
        $payment = new Payment(['integrationId' => $integration->id, 'submissionId' => $submission->id,
            'fieldId' => $integration->getField()->id, 'amount' => $amount->decimal(), 'currency' => $currency,
            'status' => Payment::STATUS_PENDING,
            'scope' => ['initial' => true, 'account' => $accountHash, 'submissionId' => $submission->id, 'formId' => $submission->formId,
                'siteId' => $submission->siteId, 'integrationId' => $integration->id, 'fieldId' => $integration->getField()->id],
        ]);
        if (!$this->savePayment($payment)) {
            throw new RuntimeException('Unable to persist payment intent.');
        }
        return $payment;
    }

    public function prepareSubscription(PaymentIntegration $integration, Submission $submission): Subscription
    {
        $payment = $this->prepareAttempt($integration, $submission);
        if ($payment->subscriptionId) {
            return Formie::$plugin->getSubscriptions()->getSubscriptionById($payment->subscriptionId);
        }
        $key = hash('sha256', 'initial-subscription|' . $payment->uid);
        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $row = (new Query())->from(Table::FORMIE_SUBSCRIPTIONS)->where(['idempotencyKey' => $key])->one();
            $subscription = $row ? new Subscription($row) : new Subscription([
                'integrationId' => $integration->id, 'submissionId' => $submission->id,
                'fieldId' => $integration->getField()->id, 'trialDays' => 0, 'idempotencyKey' => $key,
                'scope' => $payment->scope + ['amount' => $payment->amount, 'currency' => $payment->currency, 'provider' => get_class($integration)],
            ]);
            if (!$row && !Formie::$plugin->getSubscriptions()->saveSubscription($subscription)) {
                throw new RuntimeException('Unable to persist pending subscription.');
            }
            $payment->subscriptionId = $subscription->id;
            if (!$this->savePayment($payment)) {
                throw new RuntimeException('Unable to link pending subscription.');
            }
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
        return $subscription;
    }

    public function recordRecurring(Subscription $subscription, string $reference, string $amount, string $currency, string $status, array $snapshot): Payment
    {
        $key = hash('sha256', 'recurring|' . $subscription->integrationId . '|' . $reference);
        $lock = 'formie.recurring.' . $key;
        $mutex = Craft::$app->getMutex();
        if (!$mutex->acquire($lock, 10)) {
            throw new RuntimeException('Recurring payment is being recorded.');
        }
        try {
            $row = (new Query())->from(Table::FORMIE_PAYMENTS)->where(['idempotencyKey' => $key])->one();
            $payment = $row ? new Payment($row) : new Payment(['integrationId' => $subscription->integrationId,
                'submissionId' => $subscription->submissionId, 'fieldId' => $subscription->fieldId,
                'subscriptionId' => $subscription->id, 'reference' => $reference, 'amount' => $amount,
                'currency' => strtoupper($currency), 'idempotencyKey' => $key,
                'scope' => ['initial' => false, 'subscriptionId' => $subscription->id, 'subscriptionUid' => $subscription->uid,
                    'subscriptionReference' => $subscription->reference, 'formId' => $subscription->scope['formId'] ?? null,
                    'submissionId' => $subscription->submissionId, 'integrationId' => $subscription->integrationId, 'fieldId' => $subscription->fieldId]]);
            if ($row && ((int)$payment->subscriptionId !== (int)$subscription->id
                || !PaymentMoney::fromDecimal($payment->amount, $payment->currency)->equals(PaymentMoney::fromDecimal($amount, $currency)))) {
                throw new RuntimeException('Recurring payment snapshot mismatch.');
            }
            $payment->status = $status;
            $payment->response = $snapshot;
            if (!$this->savePayment($payment)) {
                throw new RuntimeException('Unable to persist the recurring payment.');
            }
            return $payment;
        } finally {
            $mutex->release($lock);
        }
    }

    public function resolveStatus(PaymentStatusCommand $command): Payment
    {
        $scope = $command->resolve();
        if ($command->mode() === PaymentResumeMode::STATUS) {
            return $this->getPaymentById($scope['paymentId']);
        }
        $lock = 'formie.payment-reconciliation.' . $scope['paymentId'];
        $mutex = Craft::$app->getMutex();
        if (!$mutex->acquire($lock, 5)) { throw new RuntimeException('Payment reconciliation is busy.'); }
        try {
            $scope = $command->resolve();
            $payment = $this->getPaymentById($scope['paymentId']);
            if (!in_array($payment->status, [Payment::STATUS_SUCCESS, Payment::STATUS_CANCELLED], true) && ($payment->scope['providerOutcome']['status'] ?? null) !== Payment::STATUS_SUCCESS) {
                $this->observeProvider(fn() => $payment->getIntegration()?->getTransaction($payment));
            }
            $payment = $this->getPaymentById($payment->id);
            Formie::$plugin->getSubmissionProcessor()->replayPaymentIfSuccessful($payment);
            return $this->getPaymentById($payment->id);
        } finally {
            $mutex->release($lock);
        }
    }

    public function getAllPayments(): array
    {
        return array_map(function(array $result): Payment {
            return $this->_hydratePayment($result);
        }, $this->_createPaymentsQuery()->all());
    }

    public function getPaymentById(int $id): ?Payment
    {
        return $this->_findPayment(['id' => $id]);
    }

    public function getPaymentByReference(string $reference, ?int $integrationId = null): ?Payment
    {
        $reference = trim($reference);

        if ($reference === '') {
            return null;
        }

        return $this->_findPayment(array_filter(['reference' => $reference, 'integrationId' => $integrationId], static fn($value) => $value !== null));
    }

    public function getSubmissionPayments(Submission $submission): array
    {
        if (!$submission->id) {
            return [];
        }

        return array_map(function(array $result): Payment {
            return $this->_hydratePayment($result);
        }, Craft::$app->getDb()->useMaster(fn() => $this->_createPaymentsQuery()->where(['submissionId' => (int)$submission->id])->all()));
    }

    public function getPaymentByUid(string $uid): ?Payment
    {
        $uid = trim($uid);

        if ($uid === '') {
            return null;
        }

        return $this->_findPayment(['uid' => $uid]);
    }

    public function resolvePaymentSuccessRedirectUrl(Payment $payment, Submission $submission, Form $form, ?string $url): string
    {
        $url = (string)($url ?? '');

        if ($url !== '') {
            $url = References::resolveUrl($url, $submission);
        }

        $event = new PaymentSuccessRedirectEvent([
            'payment' => $payment,
            'submission' => $submission,
            'form' => $form,
            'redirectUrl' => $url,
        ]);

        $this->trigger(self::EVENT_DEFINE_PAYMENT_SUCCESS_REDIRECT_URL, $event);

        return \verbb\formie\helpers\CompletionRedirectPolicy::validate($event->redirectUrl);
    }

    public function resolvePaymentFailureRedirectUrl(Payment $payment, Submission $submission, Form $form): string
    {
        $url = StringHelper::sanitizeRedirectUrl((string)($payment->redirectUrl ?? ''));

        if ($url === '') {
            $request = Craft::$app->getRequest();

            if ($request->getIsWebRequest()) {
                $url = StringHelper::sanitizeRedirectUrl((string)$request->getReferrer());
            }
        }

        if ($url === '') {
            $url = StringHelper::sanitizeRedirectUrl($form->getRedirectUrl(false, false));
        }

        return $url;
    }

    public function savePayment(Payment $payment, bool $runValidation = true): bool
    {
        $isNewPayment = !(bool)$payment->id;

        // Fire a 'beforeSavePayment' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_PAYMENT)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_PAYMENT, new PaymentEvent([
                'payment' => $payment,
                'isNew' => $isNewPayment,
            ]));
        }

        if ($runValidation && !$payment->validate()) {
            Formie::info('Payment not saved due to validation error.');

            return false;
        }

        $lock = 'formie.financial-row.payment.' . ($payment->id ?? $payment->idempotencyKey ?? 'new');
        $mutex = Craft::$app->getMutex();
        if (!$mutex->acquire($lock, 10)) {
            throw new RuntimeException('Financial state is being updated.');
        }
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $paymentRecord = $this->_getPaymentRecord($payment->id);
            if (!$paymentRecord->getIsNewRecord() && (int)$paymentRecord->version !== $payment->version) {
                throw new RuntimeException('Financial state changed. Reload before retrying.');
            }
            if (!$paymentRecord->getIsNewRecord()) {
                foreach (['integrationId', 'submissionId', 'fieldId'] as $owner) {
                    if ($paymentRecord->$owner !== $payment->$owner) {
                        throw new RuntimeException('Financial ownership cannot be changed.');
                    }
                }
            }
            $payment->version++;
            $payment->idempotencyKey ??= bin2hex(random_bytes(24));
            $money = PaymentMoney::fromDecimal($payment->amount, (string)$payment->currency);
            $payment->amount = $money->decimal();
            if (!$paymentRecord->getIsNewRecord()) {
                if ($paymentRecord->currency !== $payment->currency || !PaymentMoney::fromDecimal((string)$paymentRecord->amount, (string)$paymentRecord->currency)->equals($money)) {
                    throw new RuntimeException('A payment amount snapshot cannot change.');
                }
                if ((Json::decodeIfJson($paymentRecord->scope)['providerOutcome']['status'] ?? null) === Payment::STATUS_SUCCESS) {
                    $payment->status = Payment::STATUS_SUCCESS;
                }
                if (in_array($paymentRecord->status, [Payment::STATUS_SUCCESS, Payment::STATUS_CANCELLED], true)) {
                    $payment->status = $paymentRecord->status;
                }
            }
            $payment->scope ??= ['submissionId' => $payment->submissionId, 'integrationId' => $payment->integrationId, 'fieldId' => $payment->fieldId];
            $payment->history = array_slice(array_merge($payment->history ?? [], [['status' => $payment->status, 'at' => gmdate('c'), 'version' => $payment->version]]), -100);
            $paymentRecord->version = $payment->version;
            $paymentRecord->idempotencyKey = $payment->idempotencyKey;
            $paymentRecord->scope = $payment->scope;
            $paymentRecord->history = $payment->history;
            $paymentRecord->integrationId = $payment->integrationId;
            $paymentRecord->submissionId = $payment->submissionId;
            $paymentRecord->fieldId = $payment->fieldId;
            $paymentRecord->subscriptionId = $payment->subscriptionId;
            $paymentRecord->amount = $payment->amount;
            $paymentRecord->currency = $payment->currency;
            $paymentRecord->status = $payment->status;
            if (!$this->_committingTransition && $this->_providerObservationDepth > 0
                && ($payment->scope['initial'] ?? false) && $payment->status === Payment::STATUS_SUCCESS
                && $paymentRecord->getOldAttribute('status') !== Payment::STATUS_SUCCESS) {
                // The observation survives a failed second commit, but cannot publish completion.
                $payment->scope['providerOutcome'] = ['status' => Payment::STATUS_SUCCESS, 'reference' => $payment->reference, 'at' => gmdate('c')];
                $paymentRecord->scope = $payment->scope;
                $paymentRecord->status = Payment::STATUS_PROCESSING;
            }
            $paymentRecord->reference = $payment->reference;
            $paymentRecord->code = $payment->code;
            $paymentRecord->message = $payment->message;
            $paymentRecord->redirectUrl = $payment->redirectUrl;
            $paymentRecord->note = $payment->note;
            $paymentRecord->response = $payment->response;

            if (!$paymentRecord->save(false)) {
                throw new RuntimeException('Unable to save payment.');
            }

            $payment->id = $paymentRecord->id;
            $payment->uid = $paymentRecord->uid;

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        } finally {
            $mutex->release($lock);
        }

        // Clear caches
        $this->_paymentByKey = [];

        // Fire an 'afterSavePayment' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_PAYMENT)) {
            $this->trigger(self::EVENT_AFTER_SAVE_PAYMENT, new PaymentEvent([
                'payment' => $this->getPaymentById($paymentRecord->id),
                'isNew' => $isNewPayment,
            ]));
        }

        return true;
    }

    public function deletePaymentById(int $id): bool
    {
        $payment = $this->getPaymentById($id);

        if (!$payment) {
            return false;
        }

        return $this->deletePayment($payment);
    }

    public function deletePayment(Payment $payment): bool
    {
        // Fire a 'beforeDeletePayment' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_PAYMENT)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_PAYMENT, new PaymentEvent([
                'payment' => $payment,
            ]));
        }

        Db::delete(Table::FORMIE_PAYMENTS, [
            'uid' => $payment->uid,
        ]);

        // Clear caches
        $this->_paymentByKey = [];

        // Fire an 'afterDeletePayment' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_PAYMENT)) {
            $this->trigger(self::EVENT_AFTER_DELETE_PAYMENT, new PaymentEvent([
                'payment' => $payment,
            ]));
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _hydratePayment(array $row): Payment
    {
        $payment = new Payment($row);
        if ($this->_providerObservationDepth > 0 && ($payment->scope['providerOutcome']['status'] ?? null) === Payment::STATUS_SUCCESS) {
            $payment->status = Payment::STATUS_SUCCESS;
        }
        return $payment;
    }

    private function _findPayment(array $where): ?Payment
    {
        $result = Craft::$app->getDb()->useMaster(fn() => $this->_createPaymentsQuery()->where($where)->one());
        $payment = $result ? $this->_hydratePayment($result) : null;

        return $payment;
    }

    private function _createPaymentsQuery(): Query
    {
        $select = [
            'id', 'version', 'history', 'scope', 'idempotencyKey',
            'integrationId',
            'submissionId',
            'fieldId',
            'subscriptionId',
            'amount',
            'currency',
            'status',
            'reference',
            'code',
            'message',
            'note',
            'response',
            'dateCreated',
            'dateUpdated',
            'uid',
        ];

        if (DbSchema::columnExists(Table::FORMIE_PAYMENTS, 'redirectUrl')) {
            array_splice($select, 11, 0, ['redirectUrl']);
        }

        return (new Query())
            ->select($select)
            ->orderBy('dateCreated')
            ->from([Table::FORMIE_PAYMENTS]);
    }

    private function _getPaymentRecord(int|string|null $id): PaymentRecord
    {
        /** @var PaymentRecord $payment */
        if ($id && $payment = PaymentRecord::find()->where(['id' => $id])->one()) {
            return $payment;
        }

        return new PaymentRecord();
    }
}
