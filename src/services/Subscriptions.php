<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\Payment as PaymentIntegration;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubscriptionCancellationMode;
use verbb\formie\enums\SubscriptionStatus;
use verbb\formie\events\SubscriptionEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\payments\CancelSubscriptionCommand;
use verbb\formie\models\payments\SubscriptionSnapshot;
use verbb\formie\models\Subscription;
use verbb\formie\records\Subscription as SubscriptionRecord;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\models\FieldLayout;

use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\web\ServerErrorHttpException;

use DateTime;
use DateTimeInterface;
use RuntimeException;
use Throwable;

class Subscriptions extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_SUBSCRIPTION = 'beforeSaveSubscription';
    public const EVENT_AFTER_SAVE_SUBSCRIPTION = 'afterSaveSubscription';
    public const EVENT_BEFORE_DELETE_SUBSCRIPTION = 'beforeDeleteSubscription';
    public const EVENT_AFTER_DELETE_SUBSCRIPTION = 'afterDeleteSubscription';
    public const EVENT_AFTER_EXPIRE_SUBSCRIPTION = 'afterExpireSubscription';
    public const EVENT_BEFORE_CANCEL_SUBSCRIPTION = 'beforeCancelSubscription';
    public const EVENT_AFTER_CANCEL_SUBSCRIPTION = 'afterCancelSubscription';
    public const EVENT_BEFORE_UPDATE_SUBSCRIPTION = 'beforeUpdateSubscription';
    public const EVENT_RECEIVE_SUBSCRIPTION_PAYMENT = 'receiveSubscriptionPayment';
    public const EVENT_AFTER_APPLY_SUBSCRIPTION_SNAPSHOT = 'afterApplySubscriptionSnapshot';


    // Properties
    // =========================================================================

    private array $_subscriptionByKey = [];


    // Public Methods
    // =========================================================================

    /** The transport must verify a cancellation-only capability or trusted administrative authority. */
    public function cancelAuthorized(Subscription $subscription, CancelSubscriptionCommand $command): bool
    {
        $lock = 'formie.subscription-cancel.' . $subscription->id;
        $mutex = Craft::$app->getMutex();
        if (!$mutex->acquire($lock, 10)) {
            throw new RuntimeException('Cancellation is already being processed.');
        }
        try {
            $current = $this->getSubscriptionById($subscription->id);
            $command->authorize($current);
            if ($current->getState()->isTerminal()) {
                $subscription->status = $current->status;
                return true;
            }
            $integration = $current->getIntegration();
            if (!$current->reference || !$integration instanceof PaymentIntegration) {
                return false;
            }
            if (!$current->accountFingerprint || !hash_equals($current->accountFingerprint, $integration->getPaymentAccountFingerprint())) {
                throw new RuntimeException('Restore the original subscription account before managing it.');
            }
            $mode = $command->resolveMode($current);
            if ((!empty($current->scope['cancellationPending']) || $current->cancelAt !== null)
                && $current->cancellationMode === $mode) {
                return false;
            }
            if (!in_array($mode, $integration->getSubscriptionCancellationModes(), true)) {
                throw new RuntimeException('The payment provider does not support this cancellation mode.');
            }
            $previousStatus = $current->getState();
            $this->trigger(self::EVENT_BEFORE_CANCEL_SUBSCRIPTION, new SubscriptionEvent([
                'subscription' => $current,
                'previousStatus' => $previousStatus,
                'currentStatus' => $previousStatus,
                'source' => 'cancellation',
            ]));
            $current->scope = array_merge($current->scope ?? [], [
                'cancellationRequested' => gmdate('c'),
                'cancellationMode' => $mode->value,
                'cancellationPending' => true,
            ]);
            $current->cancellationMode = $mode;
            $this->saveSubscription($current);
            // No database transaction spans the remote call. Unknown cancellation never retries blindly.
            try {
                $snapshot = $integration->cancelSubscriptionSnapshot($current, $mode);
            } catch (Throwable) {
                $snapshot = null;
            }
            $current = $this->getSubscriptionById($current->id);
            if (!$snapshot) {
                $snapshot = new SubscriptionSnapshot(
                    SubscriptionStatus::UNKNOWN,
                    'unknown',
                    reference: $current->reference,
                    cancellationMode: $mode,
                    providerData: $current->providerData,
                );
                $current = $this->applySnapshot($current, $snapshot, 'cancellation');
                $subscription->status = $current->status;
                return false;
            }
            $current = $this->applySnapshot($current, $snapshot, 'cancellation');
            $subscription->status = $current->status;
            $this->trigger(self::EVENT_AFTER_CANCEL_SUBSCRIPTION, new SubscriptionEvent([
                'subscription' => $current,
                'previousStatus' => $previousStatus,
                'currentStatus' => $current->getState(),
                'snapshot' => $snapshot,
                'source' => 'cancellation',
            ]));
            return true;
        } finally {
            $mutex->release($lock);
        }
    }

    public function getAllSubscriptions(): array
    {
        return array_map(static function(array $result): Subscription {
            return new Subscription($result);
        }, $this->_createSubscriptionsQuery()->all());
    }

    public function getSubscriptionById(int $id): ?Subscription
    {
        return $this->_findSubscription(['id' => $id]);
    }

    public function getSubscriptionByReference(string $reference, ?int $integrationId = null, ?string $accountFingerprint = null): ?Subscription
    {
        $reference = trim($reference);

        if ($reference === '') {
            return null;
        }

        if ($integrationId && $accountFingerprint === null) {
            $integration = Formie::$plugin->getIntegrations()->getIntegrationById($integrationId);
            $accountFingerprint = $integration instanceof PaymentIntegration ? $integration->getPaymentAccountFingerprint() : null;
        }
        $rows = $this->_createSubscriptionsQuery()->where(array_filter(['reference' => $reference, 'integrationId' => $integrationId, 'accountFingerprint' => $accountFingerprint], static fn($value) => $value !== null))->limit(2)->all();
        return count($rows) === 1 ? new Subscription($rows[0]) : null;
    }

    public function getSubmissionSubscriptions(Submission $submission): array
    {
        if (!$submission->id) {
            return [];
        }

        return array_map(static function(array $result): Subscription {
            return new Subscription($result);
        }, $this->_createSubscriptionsQuery()->where(['submissionId' => (int)$submission->id])->all());
    }

    public function getSubscriptionByUid(string $uid): ?Subscription
    {
        $uid = trim($uid);

        if ($uid === '') {
            return null;
        }

        return $this->_findSubscription(['uid' => $uid]);
    }

    public function applySnapshot(Subscription $subscription, SubscriptionSnapshot $snapshot, string $source = 'provider'): Subscription
    {
        $current = $subscription->id ? $this->getSubscriptionById($subscription->id) : $subscription;

        if (!$current) {
            throw new RuntimeException('Subscription not found.');
        }

        if ($snapshot->providerEventId && $this->_historyContainsEvent($current->history ?? [], $snapshot->providerEventId)) {
            return $current;
        }

        if ($snapshot->providerUpdatedAt !== null && $current->providerUpdatedAt !== null && $snapshot->providerUpdatedAt < $current->providerUpdatedAt) {
            return $current;
        }

        $previousStatus = $current->getState();
        if ($previousStatus->isTerminal() && $snapshot->status !== $previousStatus) {
            return $current;
        }

        if ($current->reference && $snapshot->reference && $current->reference !== $snapshot->reference) {
            throw new RuntimeException('Subscription provider identity cannot change.');
        }

        $current->reference ??= $snapshot->reference;
        $current->status = $snapshot->status;
        $current->providerStatus = $snapshot->providerStatus;
        $current->providerUpdatedAt = $snapshot->providerUpdatedAt ?? $current->providerUpdatedAt;
        $current->providerData = \verbb\formie\helpers\SubscriptionProviderData::project($snapshot->providerData ?: $snapshot->rawData);
        $current->lastSyncedAt = new \DateTimeImmutable();
        $current->startedAt ??= $snapshot->startedAt;
        $current->trialStartsAt ??= $snapshot->trialStartsAt;
        $current->trialEndsAt ??= $snapshot->trialEndsAt;
        $current->currentPeriodStartsAt = $snapshot->currentPeriodStartsAt;
        $current->currentPeriodEndsAt = $snapshot->currentPeriodEndsAt;
        $current->nextPaymentAt = $snapshot->nextPaymentAt;
        $current->pausedAt ??= $snapshot->pausedAt;
        $current->cancelAt = $snapshot->cancelAt;
        $current->cancelledAt ??= $snapshot->cancelledAt;
        $current->endedAt ??= $snapshot->endedAt;
        $current->cancellationMode = $snapshot->cancellationMode;

        $scope = $current->scope ?? [];
        if ($snapshot->status !== SubscriptionStatus::UNKNOWN
            && ($snapshot->cancellationMode !== null || $snapshot->cancelAt !== null || $snapshot->status === SubscriptionStatus::CANCELLED)) {
            if (!empty($scope['cancellationPending'])) {
                $scope['cancellationConfirmed'] = gmdate('c');
            }
            $scope['cancellationPending'] = false;
        } elseif (!empty($scope['cancellationPending'])
            && $source !== 'cancellation'
            && $snapshot->status !== SubscriptionStatus::UNKNOWN) {
            // A later authoritative provider observation proves the uncertain request
            // was not applied. Preserve the audit trail while allowing an explicit retry.
            $scope['cancellationPending'] = false;
            $scope['cancellationNotApplied'] = gmdate('c');
        }
        $current->scope = $scope;

        $historyEntry = [
            'status' => $snapshot->status->value,
            'providerStatus' => $snapshot->providerStatus,
            'providerUpdatedAt' => $snapshot->providerUpdatedAt,
            'providerEventId' => $snapshot->providerEventId,
            'source' => $source,
            'at' => gmdate('c'),
        ];
        $this->saveSubscription($current, historyEntry: $historyEntry);
        $saved = $this->getSubscriptionById($current->id);
        $this->retainProviderEvidence($saved->uid, $snapshot->status->value, $snapshot->rawData);

        $this->trigger(self::EVENT_AFTER_APPLY_SUBSCRIPTION_SNAPSHOT, new SubscriptionEvent([
            'subscription' => $saved,
            'previousStatus' => $previousStatus,
            'currentStatus' => $saved->getState(),
            'snapshot' => $snapshot,
            'source' => $source,
        ]));

        return $saved;
    }

    public function hasManageableSubscriptionsForIntegration(int $integrationId): bool
    {
        return (new Query())
            ->from(Table::FORMIE_SUBSCRIPTIONS)
            ->where(['integrationId' => $integrationId])
            ->andWhere(['not in', 'status', [
                SubscriptionStatus::CANCELLED->value,
                SubscriptionStatus::FAILED->value,
                SubscriptionStatus::COMPLETED->value,
                'expired',
            ]])
            ->exists();
    }

    public function saveSubscription(Subscription $subscription, bool $runValidation = true, ?array $historyEntry = null): bool
    {
        $isNewSubscription = !(bool)$subscription->id;

        // Fire a 'beforeSaveSubscription' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_SUBSCRIPTION)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_SUBSCRIPTION, new SubscriptionEvent([
                'subscription' => $subscription,
                'isNew' => $isNewSubscription,
            ]));
        }

        if ($runValidation && !$subscription->validate()) {
            Formie::info('Subscription not saved due to validation error.');

            return false;
        }

        $lock = 'formie.financial-row.subscription.' . ($subscription->id ?? $subscription->idempotencyKey ?? 'new');
        $mutex = Craft::$app->getMutex();
        if (!$mutex->acquire($lock, 10)) {
            throw new RuntimeException('Financial state is being updated.');
        }
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $subscriptionRecord = $this->_getSubscriptionRecord($subscription->id);
            if (!$subscriptionRecord->getIsNewRecord() && (int)$subscriptionRecord->version !== $subscription->version) {
                throw new RuntimeException('Financial state changed. Reload before retrying.');
            }
            if (!$subscriptionRecord->getIsNewRecord()) {
                foreach (['integrationId', 'submissionId', 'fieldId'] as $owner) {
                    if ($subscriptionRecord->$owner !== $subscription->$owner) {
                        throw new RuntimeException('Financial ownership cannot be changed.');
                    }
                }
            }
            $subscription->version++;
            $subscription->idempotencyKey ??= bin2hex(random_bytes(24));
            if (!$subscriptionRecord->getIsNewRecord()
                && SubscriptionStatus::fromStored((string)$subscriptionRecord->status, $subscriptionRecord->providerStatus)->isTerminal()) {
                $subscription->status = SubscriptionStatus::fromStored((string)$subscriptionRecord->status, $subscriptionRecord->providerStatus);
            }
            $previousStatus = (string)$subscriptionRecord->status;
            $subscriptionRecord->status = $subscription->status;
            $subscriptionRecord->archivedAt = $subscription->archivedAt;
            $subscriptionRecord->providerUpdatedAt = $subscription->providerUpdatedAt;
            $subscriptionRecord->providerStatus = $subscription->providerStatus;
            $subscription->scope ??= ['submissionId' => $subscription->submissionId, 'integrationId' => $subscription->integrationId, 'fieldId' => $subscription->fieldId];
            if ($historyEntry !== null || $subscriptionRecord->getIsNewRecord() || $previousStatus !== $subscription->status) {
                $historyEntry ??= [
                    'status' => $subscription->status,
                    'providerStatus' => $subscription->providerStatus,
                    'at' => gmdate('c'),
                ];
                $historyEntry['version'] = $subscription->version;
                $subscription->history = array_slice(array_merge($subscription->history ?? [], [$historyEntry]), -100);
            }
            $subscriptionRecord->version = $subscription->version;
            $subscriptionRecord->idempotencyKey = $subscription->idempotencyKey;
            $subscriptionRecord->scope = $subscription->scope;
            $subscriptionRecord->history = $subscription->history;
            $subscriptionRecord->integrationId = $subscription->integrationId;
            $subscriptionRecord->submissionId = $subscription->submissionId;
            $subscriptionRecord->fieldId = $subscription->fieldId;
            $subscriptionRecord->planId = $subscription->planId;
            $subscriptionRecord->reference = $subscription->reference;
            $subscription->accountFingerprint ??= $subscriptionRecord->getIsNewRecord() ? $subscription->getIntegration()?->getPaymentAccountFingerprint() : null;
            if (!$subscriptionRecord->getIsNewRecord() && $subscriptionRecord->accountFingerprint !== $subscription->accountFingerprint) {
                throw new RuntimeException('Subscription account ownership cannot change.');
            }
            $subscription->terms ??= $subscription->getPlan() ? array_intersect_key($subscription->getPlan()->getAttributes(), array_flip(['amountMinor', 'currency', 'interval', 'intervalCount'])) : null;
            if (!$subscriptionRecord->getIsNewRecord() && $subscriptionRecord->terms !== null) {
                $subscription->terms = Json::decodeIfJson($subscriptionRecord->terms);
            }
            $subscriptionRecord->providerData = $subscription->providerData;
            $subscriptionRecord->accountFingerprint = $subscription->accountFingerprint;
            $subscriptionRecord->terms = $subscription->terms;
            $subscriptionRecord->lastSyncedAt = $subscription->lastSyncedAt;
            $subscriptionRecord->trialDays = $subscription->trialDays;
            $subscriptionRecord->startedAt = $subscription->startedAt;
            $subscriptionRecord->trialStartsAt = $subscription->trialStartsAt;
            $subscriptionRecord->trialEndsAt = $subscription->trialEndsAt;
            $subscriptionRecord->currentPeriodStartsAt = $subscription->currentPeriodStartsAt;
            $subscriptionRecord->currentPeriodEndsAt = $subscription->currentPeriodEndsAt;
            $subscriptionRecord->nextPaymentAt = $subscription->nextPaymentAt;
            $subscriptionRecord->pausedAt = $subscription->pausedAt;
            $subscriptionRecord->cancelAt = $subscription->cancelAt;
            $subscriptionRecord->cancelledAt = $subscription->cancelledAt;
            $subscriptionRecord->endedAt = $subscription->endedAt;
            $subscriptionRecord->cancellationMode = $subscription->cancellationMode?->value;

            if (!$subscriptionRecord->save(false)) {
                throw new RuntimeException('Unable to save subscription.');
            }

            $subscription->id = $subscriptionRecord->id;
            $subscription->uid = $subscriptionRecord->uid;

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        } finally {
            $mutex->release($lock);
        }

        // Clear caches
        $this->_subscriptionByKey = [];

        // Fire an 'afterSaveSubscription' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_SUBSCRIPTION)) {
            $this->trigger(self::EVENT_AFTER_SAVE_SUBSCRIPTION, new SubscriptionEvent([
                'subscription' => $this->getSubscriptionById($subscriptionRecord->id),
                'isNew' => $isNewSubscription,
            ]));
        }

        return true;
    }

    public function deleteSubscriptionById(int $id): bool
    {
        $subscription = $this->getSubscriptionById($id);

        if (!$subscription) {
            return false;
        }

        return $this->deleteSubscription($subscription);
    }

    public function deleteSubscription(Subscription $subscription): bool
    {
        // Fire a 'beforeDeleteSubscription' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_SUBSCRIPTION)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_SUBSCRIPTION, new SubscriptionEvent([
                'subscription' => $subscription,
            ]));
        }

        $subscription->archivedAt = new DateTime();
        $this->saveSubscription($subscription, false);

        // Clear caches
        $this->_subscriptionByKey = [];

        // Fire an 'afterDeleteSubscription' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_SUBSCRIPTION)) {
            $this->trigger(self::EVENT_AFTER_DELETE_SUBSCRIPTION, new SubscriptionEvent([
                'subscription' => $subscription,
            ]));
        }

        return true;
    }

    public function expireSubscription(Subscription $subscription, DateTime $dateTime = null): bool
    {
        $subscription->status = SubscriptionStatus::COMPLETED;
        $subscription->endedAt = $dateTime;

        if (!$subscription->endedAt) {
            $subscription->endedAt = new DateTime();
        }

        $this->saveSubscription($subscription, false);

        // fire an 'expireSubscription' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_EXPIRE_SUBSCRIPTION)) {
            $this->trigger(self::EVENT_AFTER_EXPIRE_SUBSCRIPTION, new SubscriptionEvent([
                'subscription' => $subscription,
            ]));
        }

        return true;
    }

    public function updateSubscription(Subscription $subscription): bool
    {
        if ($this->hasEventHandlers(self::EVENT_BEFORE_UPDATE_SUBSCRIPTION)) {
            $this->trigger(self::EVENT_BEFORE_UPDATE_SUBSCRIPTION, new SubscriptionEvent([
                'subscription' => $subscription,
            ]));
        }

        return $this->saveSubscription($subscription);
    }

    public function receivePayment(Subscription $subscription, DateTimeInterface $paidUntil, ?DateTimeInterface $previousPaidUntil = null): bool
    {
        $comparisonDate = $previousPaidUntil ?? $subscription->currentPeriodEndsAt;

        if ($comparisonDate && $comparisonDate >= $paidUntil) {
            return true;
        }
        if ($this->hasEventHandlers(self::EVENT_RECEIVE_SUBSCRIPTION_PAYMENT)) {
            $this->trigger(self::EVENT_RECEIVE_SUBSCRIPTION_PAYMENT, new SubscriptionEvent([
                'subscription' => $subscription,
            ]));
        }

        if ($subscription->currentPeriodEndsAt && $subscription->currentPeriodEndsAt >= $paidUntil) {
            return true;
        }

        $subscription->currentPeriodEndsAt = $paidUntil;

        return $this->saveSubscription($subscription);
    }


    /** Full provider observations are encrypted outside the public aggregate. */
    public function retainProviderEvidence(string $subscriptionUid, string $status, array $data): void
    {
        if (!$data) {
            return;
        }
        $encoded = Json::encode($data);
        if (strlen($encoded) > 2097152) {
            $encoded = Json::encode(['truncated' => true, 'originalBytes' => strlen($encoded), 'preview' => mb_strcut($encoded, 0, 65536)]);
        }
        $cipher = Craft::$app->getSecurity()->encryptByKey($encoded, Formie::$plugin->getSettings()->getSecurityKey());
        if ($cipher === false) {
            throw new RuntimeException('Unable to retain subscription provider evidence.');
        }
        Craft::$app->getDb()->createCommand()->insert('{{%formie_subscription_diagnostics}}', [
            'subscriptionUid' => $subscriptionUid, 'status' => $status,
            'evidence' => base64_encode($cipher), 'observedAt' => Db::prepareDateForDb(new \DateTimeImmutable()),
        ])->execute();
    }


    // Private Methods
    // =========================================================================

    private function _findSubscription(array $where): ?Subscription
    {
        $result = Craft::$app->getDb()->useMaster(fn() => $this->_createSubscriptionsQuery()->where($where)->one());
        $subscription = $result ? new Subscription($result) : null;

        return $subscription;
    }

    private function _createSubscriptionsQuery(): Query
    {
        return (new Query())
            ->select([
                'id', 'version', 'status', 'history', 'scope', 'idempotencyKey', 'archivedAt', 'providerUpdatedAt',
                'providerStatus',
                'integrationId',
                'submissionId',
                'fieldId',
                'planId',
                'reference',
                'providerData', 'accountFingerprint', 'terms', 'lastSyncedAt',
                'trialDays',
                'startedAt',
                'trialStartsAt',
                'trialEndsAt',
                'currentPeriodStartsAt',
                'currentPeriodEndsAt',
                'nextPaymentAt',
                'pausedAt',
                'cancelAt',
                'cancelledAt',
                'endedAt',
                'cancellationMode',
                'dateCreated',
                'dateUpdated',
                'uid',
            ])
            ->orderBy('dateCreated')
            ->from([Table::FORMIE_SUBSCRIPTIONS]);
    }

    private function _historyContainsEvent(array $history, string $eventId): bool
    {
        foreach ($history as $entry) {
            if (($entry['providerEventId'] ?? null) === $eventId) {
                return true;
            }
        }

        return false;
    }

    private function _getSubscriptionRecord(int|string|null $id): SubscriptionRecord
    {
        /** @var SubscriptionRecord $subscription */
        if ($id && $subscription = SubscriptionRecord::find()->where(['id' => $id])->one()) {
            return $subscription;
        }

        return new SubscriptionRecord();
    }
}
