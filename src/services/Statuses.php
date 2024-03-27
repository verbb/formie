<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\events\StatusEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\Status;
use verbb\formie\records\Status as StatusRecord;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;

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


    // Properties
    // =========================================================================

    private ?MemoizableArray $_statuses = null;


    // Public Methods
    // =========================================================================

    public function getAllStatuses(): array
    {
        return $this->_statuses()->all();
    }

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

    public function getStatusById(int $id): ?Status
    {
        return $this->_statuses()->firstWhere('id', $id);
    }

    public function getStatusByHandle(string $handle): ?Status
    {
        return $this->_statuses()->firstWhere('handle', $handle, true);
    }

    public function getStatusByUid(string $uid): ?Status
    {
        return $this->_statuses()->firstWhere('uid', $uid, true);
    }

    public function getDefaultStatus(): ?Status
    {
        return $this->_statuses()->firstWhere('isDefault', true);
    }

    public function reorderStatuses(array $statusIds): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        $uidsByIds = Db::uidsByIds(Table::FORMIE_STATUSES, $statusIds);

        foreach ($statusIds as $statusOrder => $statusId) {
            if (!empty($uidsByIds[$statusId])) {
                $statusUid = $uidsByIds[$statusId];
                $projectConfig->set(self::CONFIG_STATUSES_KEY . '.' . $statusUid . '.sortOrder', $statusOrder + 1, 'Reorder statuses');
            }
        }

        return true;
    }

    public function getSubmissionCountByStatus(): array
    {
        $countGroupedByStatusId = (new Query())
            ->select(['[[s.statusId]]', 'count(s.id) as submissionCount'])
            ->where(['[[e.dateDeleted]]' => null])
            ->from(['s' => Table::FORMIE_SUBMISSIONS])
            ->leftJoin(['e' => Table::ELEMENTS], '[[s.id]] = [[e.id]]')
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
            Formie::info('Status not saved due to validation error.');

            return false;
        }

        if ($isNewStatus) {
            $status->uid = StringHelper::UUID();

            $status->sortOrder = (new Query())
                ->from([Table::FORMIE_STATUSES])
                ->max('[[sortOrder]]') + 1;
        } else if (!$status->uid) {
            $status->uid = Db::uidById(Table::FORMIE_STATUSES, $status->id);
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
            $status->id = Db::idByUid(Table::FORMIE_STATUSES, $status->uid);
        }

        return true;
    }

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

    public function deleteStatusById(int $id): bool
    {
        $status = $this->getStatusById($id);

        if (!$status) {
            return false;
        }

        return $this->deleteStatus($status);
    }

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
                ->softDelete(Table::FORMIE_STATUSES, ['id' => $statusRecord->id])
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
            ->from([Table::FORMIE_STATUSES])
            ->where(['dateDeleted' => null])
            ->orderBy(['sortOrder' => SORT_ASC]);

        return $query;
    }

    private function _getStatusRecord(string $uid, bool $withTrashed = false): StatusRecord
    {
        $query = $withTrashed ? StatusRecord::findWithTrashed() : StatusRecord::find();
        $query->andWhere(['uid' => $uid]);

        return $query->one() ?? new StatusRecord();
    }
}
