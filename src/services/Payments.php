<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\Payment as PaymentIntegration;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\PaymentEvent;
use verbb\formie\events\PaymentSuccessRedirectEvent;
use verbb\formie\helpers\ArrayHelper;
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
    public function prepareAttempt(PaymentIntegration $integration, Submission $submission, ?bool $monetary = null, ?string $operation = null): Payment
    {
        if (!$submission->id || !$integration->id || !$integration->getField()?->id) {
            throw new RuntimeException('Save and authorize the submission before payment.');
        }
        $isSubscription = $integration->getFieldSetting('type') === PaymentIntegration::PAYMENT_TYPE_SUBSCRIPTION;
        $monetary ??= !$isSubscription;
        $operation ??= $isSubscription ? 'subscriptionSetup' : 'payment';
        $currency = strtoupper((string)$integration->getCurrency($submission));
        $amount = PaymentMoney::fromDecimal((string)$integration->getPaymentAmount($submission), $currency);

        if ($amount->minor === '0' || str_starts_with($amount->minor, '-')) {
            throw new RuntimeException('The payment amount must be positive.');
        }
        $accountHash = $integration->getPaymentAccountFingerprint();

        foreach (array_reverse($this->getSubmissionPayments($submission)) as $payment) {
            if ($payment->status === Payment::STATUS_FAILED) {
                continue;
            }

            if ($payment->integrationId === $integration->id && $payment->fieldId === $integration->getField()->id
                && (!$payment->subscriptionId || ($payment->scope['initial'] ?? false))
                && ($payment->scope['operation'] ?? 'payment') === $operation) {
                if ($payment->status !== Payment::STATUS_SUCCEEDED && ($payment->scope['initial'] ?? false)
                    && (!$payment->accountFingerprint || !hash_equals($payment->accountFingerprint, $accountHash))) {
                    throw new \verbb\formie\errors\DeliveryOutcomeUnknownException('Payment account changed; reconcile the original account.');
                }

                if (!PaymentMoney::fromDecimal($payment->amount, (string)$payment->currency)->equals($amount)) {
                    throw new RuntimeException('The existing payment amount changed; reconcile it before retrying.');
                }
                return $payment;
            }
        }
        $payment = new Payment(['integrationId' => $integration->id, 'submissionId' => $submission->id,
            'fieldId' => $integration->getField()->id, 'amountMinor' => $amount->minor, 'currency' => $currency, 'accountFingerprint' => $accountHash,
            'status' => Payment::STATUS_PENDING,
            'scope' => ['initial' => true, 'monetary' => $monetary, 'operation' => $operation,
                'account' => $accountHash, 'submissionId' => $submission->id, 'formId' => $submission->formId,
                'siteId' => $submission->siteId, 'integrationId' => $integration->id, 'fieldId' => $integration->getField()->id],
        ]);

        if (!$this->savePayment($payment)) {
            throw new RuntimeException('Unable to persist payment intent.');
        }
        return $payment;
    }

    public function prepareSubscription(PaymentIntegration $integration, Submission $submission): Subscription
    {
        $payment = $this->prepareAttempt($integration, $submission, false, 'subscriptionSetup');

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
                'accountFingerprint' => $payment->accountFingerprint,
                'terms' => ['amountMinor' => $payment->amountMinor, 'currency' => $payment->currency,
                    'interval' => $integration->getFieldSetting('frequencyType', 'month'),
                    'intervalCount' => max(1, (int)$integration->getFieldSetting('frequencyValue', 1))],
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
        $key = hash('sha256', 'recurring|' . $subscription->integrationId . '|' . $subscription->accountFingerprint . '|' . $reference);
        $lock = 'formie.recurring.' . $key;
        $mutex = Craft::$app->getMutex();

        if (!$mutex->acquire($lock, 10)) {
            throw new RuntimeException('Recurring payment is being recorded.');
        }

        try {
            $row = (new Query())->from(Table::FORMIE_PAYMENTS)->where(['idempotencyKey' => $key])->one();
            $payment = $row ? new Payment($row) : new Payment(['integrationId' => $subscription->integrationId,
                'submissionId' => $subscription->submissionId, 'fieldId' => $subscription->fieldId,
                'subscriptionId' => $subscription->id, 'accountFingerprint' => $subscription->accountFingerprint, 'reference' => $reference, 'amount' => $amount,
                'currency' => strtoupper($currency), 'idempotencyKey' => $key,
                'scope' => ['initial' => false, 'monetary' => true, 'operation' => 'recurringCharge',
                    'subscriptionId' => $subscription->id, 'subscriptionUid' => $subscription->uid,
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
        return $this->refreshIfDue($this->getPaymentById($scope['paymentId']));
    }

    public function refreshIfDue(?Payment $payment, bool $force = false): Payment
    {
        if (!$payment) {
            throw new RuntimeException('Payment not found.');
        }

        $now = time();

        if (!$force && $payment->nextReconcileAt !== null && $payment->nextReconcileAt > $now) {
            return $payment;
        }

        if (in_array($payment->status, [Payment::STATUS_SUCCEEDED, Payment::STATUS_FAILED, Payment::STATUS_CANCELLED], true)) {
            return $payment;
        }

        $lock = 'formie.payment-reconciliation.' . $payment->id;
        $mutex = Craft::$app->getMutex();

        if (!$mutex->acquire($lock, 5)) {
            throw new RuntimeException('Payment reconciliation is busy.');
        }

        try {
            $payment = $this->getPaymentById((int)$payment->id);

            if (!$force && $payment->nextReconcileAt !== null && $payment->nextReconcileAt > time()) {
                return $payment;
            }
            $integration = $payment->getIntegration();

            if (!$integration instanceof PaymentIntegration) {
                throw new RuntimeException('Payment integration not found.');
            }

            if (!$payment->accountFingerprint || !hash_equals($payment->accountFingerprint, $integration->getPaymentAccountFingerprint())) {
                throw new RuntimeException('Restore the original payment account before reconciling it.');
            }

            if (!in_array($payment->status, [Payment::STATUS_SUCCEEDED, Payment::STATUS_CANCELLED], true) && ($payment->scope['providerOutcome']['status'] ?? null) !== Payment::STATUS_SUCCEEDED) {
                $this->observeProvider(fn() => $integration->getTransaction($payment));
            }
            $payment = $this->getPaymentById($payment->id);
            Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PAYMENTS, [
                'lastReconciledAt' => time(),
                'nextReconcileAt' => time() + max(5, $integration->getReconciliationInterval($payment)),
                'reconciliationAttempts' => 0,
            ], ['id' => $payment->id])->execute();
            Formie::$plugin->getSubmissionRequests()->replayPaymentIfSuccessful($payment);
            return $this->getPaymentById($payment->id);
        } catch (Throwable $e) {
            $attempts = $payment->reconciliationAttempts + 1;
            Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PAYMENTS, [
                'lastReconciledAt' => time(),
                'nextReconcileAt' => time() + min(300, 5 * (2 ** min($attempts, 6))),
                'reconciliationAttempts' => $attempts,
            ], ['id' => $payment->id])->execute();
            throw $e;
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

    public function getPaymentByReference(string $reference, ?int $integrationId = null, ?string $accountFingerprint = null): ?Payment
    {
        $reference = trim($reference);

        if ($reference === '') {
            return null;
        }

        if ($integrationId && $accountFingerprint === null) {
            $integration = Formie::$plugin->getIntegrations()->getIntegrationById($integrationId);
            $accountFingerprint = $integration instanceof PaymentIntegration ? $integration->getPaymentAccountFingerprint() : null;
        }
        $rows = $this->_createPaymentsQuery()->where(array_filter(['reference' => $reference, 'integrationId' => $integrationId, 'accountFingerprint' => $accountFingerprint], static fn($value) => $value !== null))->limit(2)->all();
        return count($rows) === 1 ? $this->_hydratePayment($rows[0]) : null;
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

            if ($paymentRecord->getIsNewRecord() && $payment->accountFingerprint === null && $payment->getIntegration() instanceof PaymentIntegration) {
                $payment->accountFingerprint = $payment->getIntegration()->getPaymentAccountFingerprint();
            }

            if (!$paymentRecord->getIsNewRecord() && $paymentRecord->accountFingerprint !== $payment->accountFingerprint) {
                throw new RuntimeException('Payment account ownership cannot change.');
            }
            $payment->idempotencyKey ??= bin2hex(random_bytes(24));
            $money = $payment->getMoney();
            $payment->amountMinor = $money->minor;

            if (!$paymentRecord->getIsNewRecord()) {
                if ($paymentRecord->currency !== $payment->currency || !PaymentMoney::fromMinor((string)$paymentRecord->amountMinor, (string)$paymentRecord->currency)->equals($money)) {
                    throw new RuntimeException('A payment amount snapshot cannot change.');
                }

                if ((Json::decodeIfJson($paymentRecord->scope)['providerOutcome']['status'] ?? null) === Payment::STATUS_SUCCEEDED) {
                    $payment->status = Payment::STATUS_SUCCEEDED;
                }

                if (in_array($paymentRecord->status, [Payment::STATUS_SUCCEEDED, Payment::STATUS_CANCELLED], true)) {
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
            $paymentRecord->amountMinor = $payment->amountMinor;
            $paymentRecord->accountFingerprint = $payment->accountFingerprint;
            $paymentRecord->currency = $payment->currency;
            $paymentRecord->status = $payment->status;

            if (!$this->_committingTransition && $this->_providerObservationDepth > 0
                && ($payment->scope['initial'] ?? false) && $payment->status === Payment::STATUS_SUCCEEDED
                && $paymentRecord->getOldAttribute('status') !== Payment::STATUS_SUCCEEDED) {
                // The observation survives a failed second commit, but cannot publish completion.
                $payment->scope['providerOutcome'] = ['status' => Payment::STATUS_SUCCEEDED, 'reference' => $payment->reference, 'at' => gmdate('c')];
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

        if ($this->_providerObservationDepth > 0 && ($payment->scope['providerOutcome']['status'] ?? null) === Payment::STATUS_SUCCEEDED) {
            $payment->status = Payment::STATUS_SUCCEEDED;
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
            'lastReconciledAt', 'nextReconcileAt', 'reconciliationAttempts',
            'integrationId',
            'submissionId',
            'fieldId',
            'subscriptionId',
            'amount',
            'amountMinor',
            'accountFingerprint',
            'currency',
            'status',
            'reference',
            'code',
            'message',
            'redirectUrl',
            'note',
            'response',
            'dateCreated',
            'dateUpdated',
            'uid',
        ];

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
