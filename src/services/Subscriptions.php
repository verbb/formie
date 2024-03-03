<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\SubscriptionEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\Subscription;
use verbb\formie\records\Subscription as SubscriptionRecord;

use Craft;
use craft\base\Component;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\models\FieldLayout;

use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\web\ServerErrorHttpException;

use Throwable;

use DateTime;

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

    private ?MemoizableArray $_subscriptions = null;


    // Public Methods
    // =========================================================================

    public function getAllSubscriptions(): array
    {
        return $this->_subscriptions()->all();
    }

    public function getSubscriptionById(int $id): ?Subscription
    {
        return $this->_subscriptions()->firstWhere('id', $id);
    }

    public function getSubscriptionByReference(string $reference): ?Subscription
    {
        return $this->_subscriptions()->firstWhere('reference', $reference);
    }

    public function getSubmissionSubscriptions(Submission $submission): array
    {
        return $this->_subscriptions()->where('submissionId', $submission->id)->all();
    }

    public function getSubscriptionByUid(string $uid): ?Subscription
    {
        return $this->_subscriptions()->firstWhere('uid', $uid, true);
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

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $subscriptionRecord = $this->_getSubscriptionRecord($subscription->id);
            $subscriptionRecord->integrationId = $subscription->integrationId;
            $subscriptionRecord->submissionId = $subscription->submissionId;
            $subscriptionRecord->fieldId = $subscription->fieldId;
            $subscriptionRecord->planId = $subscription->planId;
            $subscriptionRecord->reference = $subscription->reference;
            $subscriptionRecord->subscriptionData = $subscription->subscriptionData;
            $subscriptionRecord->trialDays = $subscription->trialDays;
            $subscriptionRecord->nextPaymentDate = $subscription->nextPaymentDate;
            $subscriptionRecord->hasStarted = $subscription->hasStarted;
            $subscriptionRecord->isSuspended = $subscription->isSuspended;
            $subscriptionRecord->dateSuspended = $subscription->dateSuspended;
            $subscriptionRecord->isCanceled = $subscription->isCanceled;
            $subscriptionRecord->dateCanceled = $subscription->dateCanceled;
            $subscriptionRecord->isExpired = $subscription->isExpired;
            $subscriptionRecord->dateExpired = $subscription->dateExpired;

            $subscriptionRecord->save(false);

            $subscription->id = $subscriptionRecord->id;

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_subscriptions = null;

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

        Db::delete(Table::FORMIE_SUBSCRIPTIONS, [
            'uid' => $subscription->uid,
        ]);

        // Clear caches
        $this->_subscriptions = null;

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
        $subscription->isExpired = true;
        $subscription->dateExpired = $dateTime;

        if (!$subscription->dateExpired) {
            $subscription->dateExpired = Db::prepareDateForDb(new DateTime());
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
    
    private function _subscriptions(): MemoizableArray
    {
        if (!isset($this->_subscriptions)) {
            $subscriptions = [];

            foreach ($this->_createSubscriptionsQuery()->all() as $result) {
                $subscriptions[] = new Subscription($result);
            }

            $this->_subscriptions = new MemoizableArray($subscriptions);
        }

        return $this->_subscriptions;
    }

    private function _createSubscriptionsQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'integrationId',
                'submissionId',
                'fieldId',
                'planId',
                'reference',
                'subscriptionData',
                'trialDays',
                'nextPaymentDate',
                'hasStarted',
                'isSuspended',
                'dateSuspended',
                'isCanceled',
                'dateCanceled',
                'isExpired',
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
