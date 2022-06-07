<?php
namespace verbb\formie\services;

use verbb\formie\elements\Form;
use verbb\formie\events\PlanEvent;
use verbb\formie\models\Plan;
use verbb\formie\records\Plan as PlanRecord;

use Craft;
use craft\base\Component;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;
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


    // Private Properties
    // =========================================================================

    private ?MemoizableArray $_plans = null;


    // Public Methods
    // =========================================================================

    /**
     * Returns all plans.
     *
     * @return Plan[]
     */
    public function getAllPlans(): array
    {
        return $this->_plans()->all();
    }

    /**
     * Returns a plan identified by its ID.
     *
     * @param int $id
     * @return Plan|null
     */
    public function getPlanById(int $id): ?Plan
    {
        return $this->_plans()->firstWhere('id', $id);
    }

    /**
     * Returns a plan identified by its reference.
     *
     * @param string $reference
     * @return Plan|null
     */
    public function getPlanByReference(string $reference): ?Plan
    {
        return $this->_plans()->firstWhere('reference', $reference);
    }

    /**
     * Returns a plan identified by its UID.
     *
     * @param string $uid
     * @return Plan|null
     */
    public function getPlanByUid(string $uid): ?Plan
    {
        return $this->_plans()->firstWhere('uid', $uid, true);
    }

    /**
     * Saves the plan.
     *
     * @param Plan $plan
     * @param bool $runValidation
     * @return bool
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
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
            Formie::log('Plan not saved due to validation error.');

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

    /**
     * Delete a plan by its id.
     *
     * @param int $id
     * @return bool
     * @throws Throwable
     */
    public function deletePlanById(int $id): bool
    {
        $plan = $this->getPlanById($id);

        if (!$plan) {
            return false;
        }

        return $this->deletePlan($plan);
    }

    /**
     * Deletes a plan.
     *
     * @param Plan $plan The plan
     * @return bool Whether the plan was deleted successfully
     */
    public function deletePlan(Plan $plan): bool
    {
        // Fire a 'beforeDeletePlan' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_PLAN)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_PLAN, new PlanEvent([
                'plan' => $plan,
            ]));
        }

        Db::delete('{{%formie_payments_plans}}', [
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

    /**
     * Archive a subscription plan by its id.
     *
     * @param int $id The id
     * @throws InvalidConfigException
     */
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
    
    /**
     * Returns a memoizable array of all plans.
     *
     * @return MemoizableArray<Plan>
     */
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

    /**
     * Returns a Query object prepped for retrieving plans.
     *
     * @param bool $withTrashed
     * @return Query
     */
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
            ->from(['{{%formie_payments_plans}}']);
    }

    /**
     * Gets a plan record by its ID, or a new plan record
     * if it wasn't provided or was not found.
     *
     * @param int|string|null $id
     * @return PlanRecord
     */
    private function _getPlanRecord(int|string|null $id): PlanRecord
    {
        /** @var PlanRecord $plan */
        if ($id && $plan = PlanRecord::find()->where(['id' => $id])->one()) {
            return $plan;
        }

        return new PlanRecord();
    }
}
