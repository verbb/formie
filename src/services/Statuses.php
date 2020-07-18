<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\events\StatusEvent;
use verbb\formie\models\Status;
use verbb\formie\records\Status as StatusRecord;

use Craft;
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

    const EVENT_BEFORE_SAVE_STATUS = 'beforeSaveStatus';
    const EVENT_AFTER_SAVE_STATUS = 'afterSaveStatus';
    const EVENT_BEFORE_DELETE_STATUS = 'beforeDeleteStatus';
    const EVENT_BEFORE_APPLY_STATUS_DELETE = 'beforeApplyStatusDelete';
    const EVENT_AFTER_DELETE_STATUS = 'afterDeleteStatus';
    const CONFIG_STATUSES_KEY = 'formie.statuses';


    // Private Properties
    // =========================================================================

    /**
     * @var Status[]
     */
    private $_statuses;


    // Public Methods
    // =========================================================================

    /**
     * Returns all submission statuses.
     *
     * @param bool $withTrashed
     * @return Status[]
     */
    public function getAllStatuses($withTrashed = false): array
    {
        // Get the caches items if we have them cached, and the request is for non-trashed items
        if ($this->_statuses !== null) {
            return $this->_statuses;
        }

        $results = $this->_createStatusesQuery($withTrashed)->all();
        $statuses = [];

        foreach ($results as $row) {
            $statuses[] = new Status($row);
        }

        return $this->_statuses = $statuses;
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
     * Gets a single status by it's ID.
     *
     * @param string|int $id
     * @return Status|null
     */
    public function getStatusById($id)
    {
        return ArrayHelper::firstWhere($this->getAllStatuses(), 'id', $id);
    }

    /**
     * Gets a single status by it's handle.
     *
     * @param string $handle
     * @return Status|null
     */
    public function getStatusByHandle($handle)
    {
        return ArrayHelper::firstWhere($this->getAllStatuses(), 'handle', $handle, false);
    }

    /**
     * Returns a status identified by it's UID.
     *
     * @param string $uid
     * @return Status|null
     */
    public function getStatusByUid(string $uid)
    {
        return ArrayHelper::firstWhere($this->getAllStatuses(), 'uid', $uid, false);
    }

    /**
     * Saves statuses in a new order by the list of status IDs.
     *
     * @param int[] $ids
     * @return bool
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function reorderStatuses(array $ids): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        $uidsByIds = Db::uidsByIds('{{%formie_statuses}}', $ids);

        foreach ($ids as $status => $statusId) {
            if (!empty($uidsByIds[$statusId])) {
                $statusUid = $uidsByIds[$statusId];
                $projectConfig->set(self::CONFIG_STATUSES_KEY . '.' . $statusUid . '.sortOrder', $status + 1);
            }
        }

        return true;
    }

    /**
     * @return array
     */
    public function getSubmissionCountByStatus()
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
                    'orderCount' => 0
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
        $isNewStatus = !$status->id;

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
            $statusUid = StringHelper::UUID();
        } else {
            $statusUid = Db::uidById('{{%formie_statuses}}', $status->id);
        }

        // Make sure no statuses that are not archived share the handle
        $existingStatus = $this->getStatusByHandle($status->handle);

        if ($existingStatus && (!$status->id || $status->id != $existingStatus->id)) {
            $status->addError('handle', Craft::t('formie', 'That handle is already in use'));
            return false;
        }

        $projectConfig = Craft::$app->getProjectConfig();

        if ($status->dateDeleted) {
            $configData = null;
        } else {
            $configData = [
                'name' => $status->name,
                'handle' => $status->handle,
                'color' => $status->color,
                'description' => $status->description,
                'sortOrder' => (int)($status->sortOrder ?? 99),
                'isDefault' => (bool)$status->isDefault,
            ];
        }

        $configPath = self::CONFIG_STATUSES_KEY . '.' . $statusUid;
        $projectConfig->set($configPath, $configData);

        if ($isNewStatus) {
            $status->id = Db::idByUid('{{%formie_statuses}}', $statusUid);
        }

        return true;
    }

    /**
     * Handle status change.
     *
     * @param ConfigEvent $event
     * @throws Throwable
     */
    public function handleChangedStatus(ConfigEvent $event)
    {
        $statusUid = $event->tokenMatches[0];
        $data = $event->newValue;

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $statusRecord = $this->_getStatusRecord($statusUid);
            $isNewStatus = $statusRecord->getIsNewRecord();

            $statusRecord->name = $data['name'];
            $statusRecord->handle = $data['handle'];
            $statusRecord->color = $data['color'];
            $statusRecord->description = $data['description'] ?? null;
            $statusRecord->sortOrder = $data['sortOrder'] ?? 99;
            $statusRecord->isDefault = $data['isDefault'] ?? false;
            $statusRecord->uid = $statusUid;

            // Save the status
            $statusRecord->save(false);
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Fire an 'afterSaveStatus' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_STATUS)) {
            $this->trigger(self::EVENT_AFTER_SAVE_STATUS, new StatusEvent([
                'status' => $this->getStatusById($statusRecord->id),
                'isNew' => $isNewStatus,
            ]));
        }
    }

    /**
     * Delete a status by it's id.
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
        if (!$status || $status->isDefault) {
            return false;
        }

        // Fire a 'beforeDeleteStatus' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_STATUS)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_STATUS, new StatusEvent([
                'status' => $status,
            ]));
        }

        Craft::$app->getProjectConfig()->remove(self::CONFIG_STATUSES_KEY . '.' . $status->uid);
        return true;
    }

    /**
     * Handle status being deleted
     *
     * @param ConfigEvent $event
     * @throws Throwable
     */
    public function handleDeletedStatus(ConfigEvent $event)
    {
        $statusUid = $event->tokenMatches[0];

        $status = $this->getStatusByUid($statusUid);

        // Fire a 'beforeApplyStatusDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_STATUS_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_STATUS_DELETE, new StatusEvent([
                'status' => $status,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $statusRecord = $this->_getStatusRecord($statusUid);

            // Save the status
            $statusRecord->softDelete();

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
     * Returns a Query object prepped for retrieving statuses.
     *
     * @param bool $withTrashed
     * @return Query
     */
    private function _createStatusesQuery($withTrashed = false): Query
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
                'uid'
            ])
            ->orderBy('sortOrder')
            ->from(['{{%formie_statuses}}']);

        if (!$withTrashed) {
            $query->where(['dateDeleted' => null]);
        }

        return $query;
    }

    /**
     * Gets a status record by uid.
     *
     * @param string $uid
     * @return StatusRecord
     */
    private function _getStatusRecord(string $uid): StatusRecord
    {
        /** @var StatusRecord $status */
        if ($status = StatusRecord::findWithTrashed()->where(['uid' => $uid])->one()) {
            return $status;
        }

        return new StatusRecord();
    }
}
