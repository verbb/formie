<?php
namespace verbb\formie\services;

use verbb\formie\deprecations\FormStatusesDeprecations;
use verbb\formie\Formie;
use verbb\formie\events\FormStatusEvent;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\StatusColorHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\FormStatus;
use verbb\formie\records\FormStatus as FormStatusRecord;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;

use yii\base\Component;

use Throwable;

class FormStatuses extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_STATUS = 'beforeSaveFormStatus';
    public const EVENT_AFTER_SAVE_STATUS = 'afterSaveFormStatus';
    public const EVENT_BEFORE_DELETE_STATUS = 'beforeDeleteFormStatus';
    public const EVENT_BEFORE_APPLY_STATUS_DELETE = 'beforeApplyFormStatusDelete';
    public const EVENT_AFTER_DELETE_STATUS = 'afterDeleteFormStatus';
    public const CONFIG_FORM_STATUSES_KEY = 'formie.formStatuses';

    
    // Traits
    // =========================================================================

    use FormStatusesDeprecations;


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
                'color' => StatusColorHelper::resolveColor($status->color, $status->handle),
            ];
        }

        return $statuses;
    }

    public function getFormStatusSelectOptions(): array
    {
        return array_map(function(FormStatus $status) {
            return [
                'value' => (int)$status->id,
                'label' => $status->name,
                'status' => $status->color,
            ];
        }, $this->getAllStatuses());
    }

    public function getStatusById(int $id): ?FormStatus
    {
        return $this->_statuses()->firstWhere('id', $id);
    }

    public function getStatusByHandle(string $handle): ?FormStatus
    {
        return $this->_statuses()->firstWhere('handle', $handle, true);
    }

    public function getStatusByUid(string $uid): ?FormStatus
    {
        return $this->_statuses()->firstWhere('uid', $uid, true);
    }

    public function getDefaultStatus(): ?FormStatus
    {
        return $this->_statuses()->firstWhere('isDefault', true);
    }

    public function hasConfiguredStatuses(): bool
    {
        return (bool)$this->getAllStatuses();
    }

    public function resolveStatus(?int $formStatusId): ?FormStatus
    {
        $status = null;

        if ($formStatusId) {
            $status = $this->getStatusById($formStatusId);
        }

        if (!$status) {
            $status = $this->getDefaultStatus();
        }

        if (!$status) {
            $status = $this->getAllStatuses()[0] ?? null;
        }

        return $status;
    }

    public function resolveStatusId(string $value): ?int
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if ($status = $this->getStatusByHandle($value)) {
            return (int)$status->id;
        }

        $normalized = strtolower($value);

        foreach ($this->getAllStatuses() as $status) {
            if (strtolower((string)$status->handle) === $normalized) {
                return (int)$status->id;
            }

            if (strtolower((string)$status->name) === $normalized) {
                return (int)$status->id;
            }
        }

        return null;
    }

    public function resolveStatusIdParam(array|string $value): mixed
    {
        if (is_array($value)) {
            $resolvedIds = [];

            foreach ($value as $candidate) {
                $resolvedId = $this->resolveStatusId((string)$candidate);

                if ($resolvedId !== null) {
                    $resolvedIds[] = $resolvedId;
                }
            }

            if ($resolvedIds) {
                return $resolvedIds;
            }
        } else {
            $trimmedValue = trim($value);
            $normalizedValue = strtolower($trimmedValue);
            $resolvedId = $this->resolveStatusId($trimmedValue);

            if ($resolvedId !== null) {
                return $resolvedId;
            }

            if (str_starts_with($normalizedValue, 'not ')) {
                $candidate = trim(substr($trimmedValue, 4));
                $resolvedId = $this->resolveStatusId($candidate);

                if ($resolvedId !== null) {
                    return "not {$resolvedId}";
                }
            }
        }

        return (new Query())
            ->select(['id'])
            ->from([Table::FORMIE_FORM_STATUSES])
            ->where(Db::parseParam('handle', $value))
            ->scalar();
    }

    public function reorderStatuses(array $statusIds): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        $uidsByIds = Db::uidsByIds(Table::FORMIE_FORM_STATUSES, $statusIds);

        foreach ($statusIds as $statusOrder => $statusId) {
            if (!empty($uidsByIds[$statusId])) {
                $statusUid = $uidsByIds[$statusId];
                $projectConfig->set(self::CONFIG_FORM_STATUSES_KEY . '.' . $statusUid . '.sortOrder', $statusOrder + 1, 'Reorder form statuses');
            }
        }

        return true;
    }

    public function getFormCountByStatus(): array
    {
        $countGroupedByStatusId = (new Query())
            ->select(['[[f.formStatusId]]', 'count(f.id) as formCount'])
            ->where(['[[e.dateDeleted]]' => null])
            ->from(['f' => Table::FORMIE_FORMS])
            ->leftJoin(['e' => Table::ELEMENTS], '[[f.id]] = [[e.id]]')
            ->groupBy(['[[f.formStatusId]]'])
            ->indexBy('formStatusId')
            ->all();

        $allStatuses = $this->getAllStatuses();
        foreach ($allStatuses as $status) {
            if (!isset($countGroupedByStatusId[$status->id])) {
                $countGroupedByStatusId[$status->id] = [
                    'formStatusId' => $status->id,
                    'handle' => $status->handle,
                    'formCount' => 0,
                ];
            }

            $countGroupedByStatusId[$status->id]['handle'] = $status->handle;
        }

        return $countGroupedByStatusId;
    }

    public function saveStatus(FormStatus $status, bool $runValidation = true): bool
    {
        $isNewStatus = !(bool)$status->id;

        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_STATUS)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_STATUS, new FormStatusEvent([
                'status' => $status,
                'isNew' => $isNewStatus,
            ]));
        }

        if ($runValidation && !$status->validate()) {
            Formie::info('Form status not saved due to validation error.');

            return false;
        }

        if ($isNewStatus) {
            $status->uid = StringHelper::UUID();

            $status->sortOrder = (new Query())
                ->from([Table::FORMIE_FORM_STATUSES])
                ->max('[[sortOrder]]') + 1;
        } else if (!$status->uid) {
            $status->uid = Db::uidById(Table::FORMIE_FORM_STATUSES, $status->id);
        }

        $existingStatus = $this->getStatusByHandle($status->handle);

        if ($existingStatus && (!$status->id || $status->id != $existingStatus->id)) {
            $status->addError('handle', Craft::t('formie', 'That handle is already in use'));
            return false;
        }

        $configPath = self::CONFIG_FORM_STATUSES_KEY . '.' . $status->uid;
        Craft::$app->getProjectConfig()->set($configPath, $status->getConfig(), "Save the “{$status->handle}” form status");

        if ($isNewStatus) {
            $status->id = Db::idByUid(Table::FORMIE_FORM_STATUSES, $status->uid);
        }

        return true;
    }

    public function handleChangedFormStatus(ConfigEvent $event): void
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

            if ((bool)$statusRecord->dateDeleted) {
                $statusRecord->restore();
            } else {
                $statusRecord->save(false);
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $this->_statuses = null;

        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_STATUS)) {
            $this->trigger(self::EVENT_AFTER_SAVE_STATUS, new FormStatusEvent([
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

    public function deleteStatus(FormStatus $status): bool
    {
        if ($status->isDefault) {
            return false;
        }

        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_STATUS)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_STATUS, new FormStatusEvent([
                'status' => $status,
            ]));
        }

        Craft::$app->getProjectConfig()->remove(self::CONFIG_FORM_STATUSES_KEY . '.' . $status->uid, "Delete form status “{$status->handle}”");

        return true;
    }

    public function handleDeletedFormStatus(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $statusRecord = $this->_getStatusRecord($uid);

        if ($statusRecord->getIsNewRecord()) {
            return;
        }

        $status = $this->getStatusById($statusRecord->id);

        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_STATUS_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_STATUS_DELETE, new FormStatusEvent([
                'status' => $status,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            Craft::$app->getDb()->createCommand()
                ->softDelete(Table::FORMIE_FORM_STATUSES, ['id' => $statusRecord->id])
                ->execute();

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_STATUS)) {
            $this->trigger(self::EVENT_AFTER_DELETE_STATUS, new FormStatusEvent([
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
                $statuses[] = new FormStatus($result);
            }

            $this->_statuses = new MemoizableArray($statuses);
        }

        return $this->_statuses;
    }

    private function _createStatusesQuery(): Query
    {
        return (new Query())
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
            ->from([Table::FORMIE_FORM_STATUSES])
            ->where(['dateDeleted' => null])
            ->orderBy(['sortOrder' => SORT_ASC]);
    }

    private function _getStatusRecord(string $uid, bool $withTrashed = false): FormStatusRecord
    {
        $query = $withTrashed ? FormStatusRecord::findWithTrashed() : FormStatusRecord::find();
        $query->andWhere(['uid' => $uid]);

        return $query->one() ?? new FormStatusRecord();
    }
}
