<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\SubscriptionEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\payments\CancelSubscriptionCommand;
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
            if ($current->getState()->isTerminal() || $current->status === 'cancelling') {
                $subscription->status = $current->status;
                return true;
            }
            if (!empty($current->scope['cancellationRequested'])) {
                return false;
            }
            if (!$current->reference || !$current->getIntegration()) {
                return false;
            }
            $this->trigger(self::EVENT_BEFORE_CANCEL_SUBSCRIPTION, new SubscriptionEvent(['subscription' => $current]));
            $current->scope = array_merge($current->scope ?? [], ['cancellationRequested' => gmdate('c')]);
            $current->status = 'cancelling';
            $this->saveSubscription($current);
            // No database transaction spans the remote call. Unknown cancellation never retries blindly.
            try {
                $result = $current->getIntegration()->cancelSubscription($current->reference, []);
            } catch (Throwable) {
                $result = null;
            }
            $current = $this->getSubscriptionById($current->id);
            if (!$result) {
                $current->status = 'unknown';
                $this->saveSubscription($current);
                $subscription->status = $current->status;
                return false;
            }
            $subscription->status = $current->status;
            $this->trigger(self::EVENT_AFTER_CANCEL_SUBSCRIPTION, new SubscriptionEvent(['subscription' => $current]));
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

    public function getSubscriptionByReference(string $reference, ?int $integrationId = null): ?Subscription
    {
        $reference = trim($reference);

        if ($reference === '') {
            return null;
        }

        return $this->_findSubscription(array_filter(['reference' => $reference, 'integrationId' => $integrationId], static fn($value) => $value !== null));
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

    public function saveSubscription(Subscription $subscription, bool $runValidation = true): bool
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
            if (!$subscriptionRecord->getIsNewRecord() && in_array($subscriptionRecord->status, ['cancelled', 'expired'], true)) {
                $subscription->status = $subscriptionRecord->status;
            }
            $subscriptionRecord->status = $subscription->status;
            $subscriptionRecord->archivedAt = $subscription->archivedAt;
            $subscriptionRecord->providerUpdatedAt = $subscription->providerUpdatedAt;
            $subscription->scope ??= ['submissionId' => $subscription->submissionId, 'integrationId' => $subscription->integrationId, 'fieldId' => $subscription->fieldId];
            $subscription->history = array_slice(array_merge($subscription->history ?? [], [['status' => $subscription->status, 'at' => gmdate('c'), 'version' => $subscription->version, 'providerStatus' => $subscription->subscriptionData['status'] ?? null]]), -100);
            $subscriptionRecord->version = $subscription->version;
            $subscriptionRecord->idempotencyKey = $subscription->idempotencyKey;
            $subscriptionRecord->scope = $subscription->scope;
            $subscriptionRecord->history = $subscription->history;
            $subscriptionRecord->integrationId = $subscription->integrationId;
            $subscriptionRecord->submissionId = $subscription->submissionId;
            $subscriptionRecord->fieldId = $subscription->fieldId;
            $subscriptionRecord->planId = $subscription->planId;
            $subscriptionRecord->reference = $subscription->reference;
            $subscriptionRecord->subscriptionData = $subscription->subscriptionData;
            $subscriptionRecord->trialDays = $subscription->trialDays;
            $subscriptionRecord->nextPaymentDate = $subscription->nextPaymentDate;
            $subscriptionRecord->dateSuspended = $subscription->dateSuspended;
            $subscriptionRecord->dateCanceled = $subscription->dateCanceled;
            $subscriptionRecord->dateExpired = $subscription->dateExpired;

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
        $subscription->status = 'expired';
        $subscription->dateExpired = $dateTime;

        if (!$subscription->dateExpired) {
            $subscription->dateExpired = new DateTime();
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

    public function receivePayment(Subscription $subscription, DateTime $paidUntil): bool
    {
        if ($subscription->nextPaymentDate && $subscription->nextPaymentDate >= $paidUntil) {
            return true;
        }
        if ($this->hasEventHandlers(self::EVENT_RECEIVE_SUBSCRIPTION_PAYMENT)) {
            $this->trigger(self::EVENT_RECEIVE_SUBSCRIPTION_PAYMENT, new SubscriptionEvent([
                'subscription' => $subscription,
            ]));
        }

        $subscription->nextPaymentDate = $paidUntil;

        return $this->saveSubscription($subscription);
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
                'integrationId',
                'submissionId',
                'fieldId',
                'planId',
                'reference',
                'subscriptionData',
                'trialDays',
                'nextPaymentDate',
                'dateSuspended',
                'dateCanceled',
                'dateExpired',
                'dateCreated',
                'dateUpdated',
                'uid',
            ])
            ->orderBy('dateCreated')
            ->from([Table::FORMIE_SUBSCRIPTIONS]);
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
