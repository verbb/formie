<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\events\PlanEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\Plan;
use verbb\formie\records\Plan as PlanRecord;

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

class Plans extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_PLAN = 'beforeSavePlan';
    public const EVENT_AFTER_SAVE_PLAN = 'afterSavePlan';
    public const EVENT_BEFORE_DELETE_PLAN = 'beforeDeletePlan';
    public const EVENT_AFTER_DELETE_PLAN = 'afterDeletePlan';
    public const EVENT_ARCHIVE_PLAN = 'archivePlan';


    // Properties
    // =========================================================================

    private ?MemoizableArray $_plans = null;


    // Public Methods
    // =========================================================================

    public function getAllPlans(): array
    {
        return $this->_plans()->all();
    }

    public function getPlanById(int $id): ?Plan
    {
        return $this->_plans()->firstWhere('id', $id);
    }

    public function getPlanByReference(string $reference): ?Plan
    {
        return $this->_plans()->firstWhere('reference', $reference);
    }

    public function getPlanByUid(string $uid): ?Plan
    {
        return $this->_plans()->firstWhere('uid', $uid, true);
    }

    public function savePlan(Plan $plan, bool $runValidation = true): bool
    {
        $isNewPlan = !(bool)$plan->id;

        // Fire a 'beforeSavePlan' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_PLAN)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_PLAN, new PlanEvent([
                'plan' => $plan,
                'isNew' => $isNewPlan,
            ]));
        }

        if ($runValidation && !$plan->validate()) {
            Formie::info('Plan not saved due to validation error.');

            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $planRecord = $this->_getPlanRecord($plan->id);
            $planRecord->integrationId = $plan->integrationId;
            $planRecord->name = $plan->name;
            $planRecord->handle = $plan->handle;
            $planRecord->reference = $plan->reference;
            $planRecord->enabled = $plan->enabled;
            $planRecord->planData = $plan->planData;
            $planRecord->isArchived = $plan->isArchived;
            $planRecord->dateArchived = $plan->dateArchived;

            $planRecord->save(false);

            $plan->id = $planRecord->id;

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_plans = null;

        // Fire an 'afterSavePlan' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_PLAN)) {
            $this->trigger(self::EVENT_AFTER_SAVE_PLAN, new PlanEvent([
                'plan' => $this->getPlanById($planRecord->id),
                'isNew' => $isNewPlan,
            ]));
        }

        return true;
    }

    public function deletePlanById(int $id): bool
    {
        $plan = $this->getPlanById($id);

        if (!$plan) {
            return false;
        }

        return $this->deletePlan($plan);
    }

    public function deletePlan(Plan $plan): bool
    {
        // Fire a 'beforeDeletePlan' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_PLAN)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_PLAN, new PlanEvent([
                'plan' => $plan,
            ]));
        }

        Db::delete(Table::FORMIE_PAYMENT_PLANS, [
            'uid' => $plan->uid,
        ]);

        // Clear caches
        $this->_plans = null;

        // Fire an 'afterDeletePlan' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_PLAN)) {
            $this->trigger(self::EVENT_AFTER_DELETE_PLAN, new PlanEvent([
                'plan' => $plan,
            ]));
        }

        return true;
    }

    public function archivePlanById(int $id): bool
    {
        $plan = $this->getPlanById($id);

        if (!$plan) {
            return false;
        }

        // Fire an 'archivePlan' event.
        if ($this->hasEventHandlers(self::EVENT_ARCHIVE_PLAN)) {
            $this->trigger(self::EVENT_ARCHIVE_PLAN, new PlanEvent([
                'plan' => $plan,
            ]));
        }

        $plan->isArchived = true;
        $plan->dateArchived = Db::prepareDateForDb(new DateTime());

        return $this->savePlan($plan);
    }


    // Private Methods
    // =========================================================================
    
    private function _plans(): MemoizableArray
    {
        if (!isset($this->_plans)) {
            $plans = [];

            foreach ($this->_createPlansQuery()->all() as $result) {
                $plans[] = new Plan($result);
            }

            $this->_plans = new MemoizableArray($plans);
        }

        return $this->_plans;
    }

    private function _createPlansQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'integrationId',
                'name',
                'handle',
                'reference',
                'enabled',
                'planData',
                'isArchived',
                'dateArchived',
                'dateCreated',
                'dateUpdated',
                'uid',
            ])
            ->orderBy('dateCreated')
            ->from([Table::FORMIE_PAYMENT_PLANS]);
    }

    private function _getPlanRecord(int|string|null $id): PlanRecord
    {
        /** @var PlanRecord $plan */
        if ($id && $plan = PlanRecord::find()->where(['id' => $id])->one()) {
            return $plan;
        }

        return new PlanRecord();
    }
}
