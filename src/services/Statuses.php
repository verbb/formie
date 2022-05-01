<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\events\StatusEvent;
use verbb\formie\models\Status;
use verbb\formie\records\Status as StatusRecord;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\events\ConfigEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;

use yii\base\Component;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\web\ServerErrorHttpException;

use Throwable;

class Statuses extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_STATUS = 'beforeSaveStatus';
    public const EVENT_AFTER_SAVE_STATUS = 'afterSaveStatus';
    public const EVENT_BEFORE_DELETE_STATUS = 'beforeDeleteStatus';
    public const EVENT_BEFORE_APPLY_STATUS_DELETE = 'beforeApplyStatusDelete';
    public const EVENT_AFTER_DELETE_STATUS = 'afterDeleteStatus';
    public const CONFIG_STATUSES_KEY = 'formie.statuses';


    // Private Properties
    // =========================================================================

    private ?MemoizableArray $_statuses = null;


    // Public Methods
    // =========================================================================

    /**
     * Returns all submission statuses.
     *
     * @return Status[]
     */
    public function getAllStatuses(): array
    {
        return $this->_statuses()->all();
    }

    /**
     * Returns all submission statuses as an array for use in
     * element `statuses`.
     *
     * @return array
     */
    public function getStatusesArray(): array
    {
        $statuses = [];
        foreach ($this->getAllStatuses() as $status) {
            $statuses[$status->handle] = [
                'label' => $status->name,
                'color' => $status->color,
            ];
        }

        return $statuses;
    }

    /**
     * Gets a single status by its ID.
     *
     * @param int $id
     * @return Status|null
     */
    public function getStatusById(int $id): ?Status
    {
        return $this->_statuses()->firstWhere('id', $id);
    }

    /**
     * Gets a single status by its handle.
     *
     * @param string $handle
     * @return Status|null
     */
    public function getStatusByHandle(string $handle): ?Status
    {
        return $this->_statuses()->firstWhere('handle', $handle, true);
    }

    /**
     * Returns a status identified by its UID.
     *
     * @param string $uid
     * @return Status|null
     */
    public function getStatusByUid(string $uid): ?Status
    {
        return $this->_statuses()->firstWhere('uid', $uid, true);
    }

    /**
     * Gets the default status.
     *
     * @return Status|null
     */
    public function getDefaultStatus(): ?Status
    {
        return $this->_statuses()->firstWhere('isDefault', true);
    }

    /**
     * Saves statuses in a new order by the list of status IDs.
     *
     * @param int[] $statusIds
     * @return bool
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function reorderStatuses(array $statusIds): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        $uidsByIds = Db::uidsByIds('{{%formie_statuses}}', $statusIds);

        foreach ($statusIds as $statusOrder => $statusId) {
            if (!empty($uidsByIds[$statusId])) {
                $statusUid = $uidsByIds[$statusId];
                $projectConfig->set(self::CONFIG_STATUSES_KEY . '.' . $statusUid . '.sortOrder', $statusOrder + 1, 'Reorder statuses');
            }
        }

        return true;
    }

    /**
     * @return array
     */
    public function getSubmissionCountByStatus(): array
    {
        $countGroupedByStatusId = (new Query())
            ->select(['[[s.statusId]]', 'count(s.id) as submissionCount'])
            ->where(['[[e.dateDeleted]]' => null])
            ->from(['{{%formie_submissions}} s'])
            ->leftJoin([CraftTable::ELEMENTS . ' e'], '[[s.id]] = [[e.id]]')
            ->groupBy(['[[s.statusId]]'])
            ->indexBy('statusId')
            ->all();

        // For those not in the groupBy
        $allStatuses = $this->getAllStatuses();
        foreach ($allStatuses as $status) {
            if (!isset($countGroupedByStatusId[$status->id])) {
                $countGroupedByStatusId[$status->id] = [
                    'orderStatusId' => $status->id,
                    'handle' => $status->handle,
                    'orderCount' => 0,
                ];
            }

            // Make sure all have their handle
            $countGroupedByStatusId[$status->id]['handle'] = $status->handle;
        }

        return $countGroupedByStatusId;
    }

    /**
     * Saves the status.
     *
     * @param Status $status
     * @param bool $runValidation
     * @return bool
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function saveStatus(Status $status, bool $runValidation = true): bool
    {
        $isNewStatus = !(bool)$status->id;

        // Fire a 'beforeSaveStatus' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_STATUS)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_STATUS, new StatusEvent([
                'status' => $status,
                'isNew' => $isNewStatus,
            ]));
        }

        if ($runValidation && !$status->validate()) {
            Formie::log('Status not saved due to validation error.');

            return false;
        }

        if ($isNewStatus) {
            $status->uid = StringHelper::UUID();

            $status->sortOrder = (new Query())
                ->from(['{{%formie_statuses}}'])
                ->max('[[sortOrder]]') + 1;
        } else if (!$status->uid) {
            $status->uid = Db::uidById('{{%formie_statuses}}', $status->id);
        }

        // Make sure no statuses that are not archived share the handle
        $existingStatus = $this->getStatusByHandle($status->handle);

        if ($existingStatus && (!$status->id || $status->id != $existingStatus->id)) {
            $status->addError('handle', Craft::t('formie', 'That handle is already in use'));
            return false;
        }

        $configPath = self::CONFIG_STATUSES_KEY . '.' . $status->uid;
        Craft::$app->getProjectConfig()->set($configPath, $status->getConfig(), "Save the “{$status->handle}” status");

        if ($isNewStatus) {
            $status->id = Db::idByUid('{{%formie_statuses}}', $status->uid);
        }

        return true;
    }

    /**
     * Handle status change.
     *
     * @param ConfigEvent $event
     * @throws Throwable
     */
    public function handleChangedStatus(ConfigEvent $event): void
    {
        $statusUid = $event->tokenMatches[0];
        $data = $event->newValue;

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $statusRecord = $this->_getStatusRecord($statusUid, true);
            $isNewStatus = $statusRecord->getIsNewRecord();

            $statusRecord->name = $data['name'];
            $statusRecord->handle = $data['handle'];
            $statusRecord->color = $data['color'];
            $statusRecord->description = $data['description'] ?? null;
            $statusRecord->sortOrder = $data['sortOrder'];
            $statusRecord->isDefault = $data['isDefault'] ?? false;
            $statusRecord->uid = $statusUid;

            if ($wasTrashed = (bool)$statusRecord->dateDeleted) {
                $statusRecord->restore();
            } else {
                $statusRecord->save(false);
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_statuses = null;

        // Fire an 'afterSaveStatus' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_STATUS)) {
            $this->trigger(self::EVENT_AFTER_SAVE_STATUS, new StatusEvent([
                'status' => $this->getStatusById($statusRecord->id),
                'isNew' => $isNewStatus,
            ]));
        }
    }

    /**
     * Delete a status by its id.
     *
     * @param int $id
     * @return bool
     * @throws Throwable
     */
    public function deleteStatusById(int $id): bool
    {
        $status = $this->getStatusById($id);

        if (!$status) {
            return false;
        }

        return $this->deleteStatus($status);
    }

    /**
     * Deletes a status.
     *
     * @param Status $status The status
     * @return bool Whether the status was deleted successfully
     */
    public function deleteStatus(Status $status): bool
    {
        // Can't delete the default status
        if ($status->isDefault) {
            return false;
        }

        // Fire a 'beforeDeleteStatus' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_STATUS)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_STATUS, new StatusEvent([
                'status' => $status,
            ]));
        }

        Craft::$app->getProjectConfig()->remove(self::CONFIG_STATUSES_KEY . '.' . $status->uid, "Delete status “{$status->handle}”");

        return true;
    }

    /**
     * Handle status being deleted
     *
     * @param ConfigEvent $event
     * @throws Throwable
     */
    public function handleDeletedStatus(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $statusRecord = $this->_getStatusRecord($uid);

        if ($statusRecord->getIsNewRecord()) {
            return;
        }

        $status = $this->getStatusById($statusRecord->id);

        // Fire a 'beforeApplyStatusDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_STATUS_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_STATUS_DELETE, new StatusEvent([
                'status' => $status,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            Craft::$app->getDb()->createCommand()
                ->softDelete('{{%formie_statuses}}', ['id' => $statusRecord->id])
                ->execute();

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Fire an 'afterDeleteStatus' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_STATUS)) {
            $this->trigger(self::EVENT_AFTER_DELETE_STATUS, new StatusEvent([
                'status' => $status,
            ]));
        }
    }


    // Private Methods
    // =========================================================================

    /**
     * Returns a memoizable array of all statuses.
     *
     * @return MemoizableArray<Status>
     */
    private function _statuses(): MemoizableArray
    {
        if (!isset($this->_statuses)) {
            $statuses = [];

            foreach ($this->_createStatusesQuery()->all() as $result) {
                $statuses[] = new Status($result);
            }

            $this->_statuses = new MemoizableArray($statuses);
        }

        return $this->_statuses;
    }

    /**
     * Returns a Query object prepped for retrieving statuses.
     *
     * @return Query
     */
    private function _createStatusesQuery(): Query
    {
        $query = (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'color',
                'description',
                'sortOrder',
                'isDefault',
                'dateDeleted',
                'uid',
            ])
            ->from(['{{%formie_statuses}}'])
            ->where(['dateDeleted' => null])
            ->orderBy(['sortOrder' => SORT_ASC]);

        return $query;
    }

    /**
     * Gets a status record by uid.
     *
     * @param string $uid
     * @param bool $withTrashed Whether to include trashed statuses in search
     * @return StatusRecord
     */
    private function _getStatusRecord(string $uid, bool $withTrashed = false): StatusRecord
    {
        $query = $withTrashed ? StatusRecord::findWithTrashed() : StatusRecord::find();
        $query->andWhere(['uid' => $uid]);

        return $query->one() ?? new StatusRecord();
    }
}
