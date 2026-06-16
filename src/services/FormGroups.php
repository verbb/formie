<?php
namespace verbb\formie\services;

use verbb\formie\events\FormGroupEvent;
use verbb\formie\Formie;
use verbb\formie\helpers\DbSchema;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\FormGroup;
use verbb\formie\records\FormGroup as FormGroupRecord;

use Craft;
use craft\base\Component;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;

use Throwable;

class FormGroups extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_FORM_GROUP = 'beforeSaveFormGroup';
    public const EVENT_AFTER_SAVE_FORM_GROUP = 'afterSaveFormGroup';
    public const EVENT_BEFORE_DELETE_FORM_GROUP = 'beforeDeleteFormGroup';
    public const EVENT_BEFORE_APPLY_FORM_GROUP_DELETE = 'beforeApplyFormGroupDelete';
    public const EVENT_AFTER_DELETE_FORM_GROUP = 'afterDeleteFormGroup';
    public const CONFIG_GROUPS_KEY = 'formie.formGroups';


    // Properties
    // =========================================================================

    private ?MemoizableArray $_groups = null;


    // Public Methods
    // =========================================================================

    public function getAllGroups(): array
    {
        return $this->_groups()->all();
    }

    public function getGroupById(int $id): ?FormGroup
    {
        return $this->_groups()->firstWhere('id', $id);
    }

    public function getGroupByHandle(string $handle): ?FormGroup
    {
        return $this->_groups()->firstWhere('handle', $handle, true);
    }

    public function getGroupByUid(string $uid): ?FormGroup
    {
        return $this->_groups()->firstWhere('uid', $uid, true);
    }

    public function reorderGroups(array $groupIds): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $uidsByIds = Db::uidsByIds(Table::FORMIE_FORM_GROUPS, $groupIds);

        foreach ($groupIds as $groupOrder => $groupId) {
            if (!empty($uidsByIds[$groupId])) {
                $groupUid = $uidsByIds[$groupId];
                $projectConfig->set(self::CONFIG_GROUPS_KEY . '.' . $groupUid . '.sortOrder', $groupOrder + 1);
            }
        }

        return true;
    }

    public function saveGroup(FormGroup $group, bool $runValidation = true): bool
    {
        $isNewGroup = !(bool)$group->id;

        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_FORM_GROUP)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_FORM_GROUP, new FormGroupEvent([
                'formGroup' => $group,
                'isNew' => $isNewGroup,
            ]));
        }

        if ($runValidation && !$group->validate()) {
            Formie::info('Form group not saved due to validation error.');

            return false;
        }

        if ($isNewGroup) {
            $group->uid = StringHelper::UUID();

            $group->sortOrder = (new Query())
                ->from([Table::FORMIE_FORM_GROUPS])
                ->max('[[sortOrder]]') + 1;
        } elseif (!$group->uid) {
            $group->uid = Db::uidById(Table::FORMIE_FORM_GROUPS, $group->id);
        }

        $existingGroup = $this->getGroupByHandle($group->handle);

        if ($existingGroup && (!$group->id || $group->id != $existingGroup->id)) {
            $group->addError('handle', Craft::t('formie', 'That handle is already in use'));

            return false;
        }

        $configPath = self::CONFIG_GROUPS_KEY . '.' . $group->uid;
        Craft::$app->getProjectConfig()->set($configPath, $group->getConfig(), "Save the “{$group->handle}” form group");

        if ($isNewGroup) {
            $group->id = Db::idByUid(Table::FORMIE_FORM_GROUPS, $group->uid);
        }

        return true;
    }

    public function handleChangedGroup(ConfigEvent $event): void
    {
        $groupUid = $event->tokenMatches[0];
        $data = $event->newValue;

        if (!$data) {
            return;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $groupRecord = $this->_getGroupRecord($groupUid, true);
            $isNewGroup = $groupRecord->getIsNewRecord();

            $groupRecord->name = $data['name'];
            $groupRecord->handle = $data['handle'];
            $groupRecord->sortOrder = $data['sortOrder'];
            $groupRecord->uid = $groupUid;

            if ($groupRecord->dateDeleted) {
                $groupRecord->restore();
            } else {
                $groupRecord->save(false);
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $this->_groups = null;

        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_FORM_GROUP)) {
            $this->trigger(self::EVENT_AFTER_SAVE_FORM_GROUP, new FormGroupEvent([
                'formGroup' => $this->getGroupById($groupRecord->id),
                'isNew' => $isNewGroup,
            ]));
        }
    }

    public function deleteGroupById(int $id): bool
    {
        $group = $this->getGroupById($id);

        if (!$group) {
            return false;
        }

        return $this->deleteGroup($group);
    }

    public function deleteGroup(FormGroup $group): bool
    {
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_FORM_GROUP)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_FORM_GROUP, new FormGroupEvent([
                'formGroup' => $group,
            ]));
        }

        Craft::$app->getProjectConfig()->remove(self::CONFIG_GROUPS_KEY . '.' . $group->uid, "Delete form group “{$group->handle}”");

        return true;
    }

    public function handleDeletedGroup(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $groupRecord = $this->_getGroupRecord($uid);

        if ($groupRecord->getIsNewRecord()) {
            return;
        }

        $group = $this->getGroupById($groupRecord->id);

        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_FORM_GROUP_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_FORM_GROUP_DELETE, new FormGroupEvent([
                'formGroup' => $group,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            Craft::$app->getDb()->createCommand()
                ->update(Table::FORMIE_FORMS, ['groupId' => null], ['groupId' => $groupRecord->id])
                ->execute();

            Craft::$app->getDb()->createCommand()
                ->softDelete(Table::FORMIE_FORM_GROUPS, ['id' => $groupRecord->id])
                ->execute();

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $this->_groups = null;

        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_FORM_GROUP)) {
            $this->trigger(self::EVENT_AFTER_DELETE_FORM_GROUP, new FormGroupEvent([
                'formGroup' => $group,
            ]));
        }
    }


    // Private Methods
    // =========================================================================

    private function _groups(): MemoizableArray
    {
        if (!isset($this->_groups)) {
            if (!DbSchema::tableExists(Table::FORMIE_FORM_GROUPS)) {
                return $this->_groups = new MemoizableArray([]);
            }

            $groups = [];

            foreach ($this->_createGroupsQuery()->all() as $result) {
                $group = new FormGroup($result);
                $groupConfig = Craft::$app->getProjectConfig()->get(self::CONFIG_GROUPS_KEY . '.' . $result['uid']);

                if (is_array($groupConfig['settings'] ?? null)) {
                    $group->settings = $groupConfig['settings'];
                }

                $groups[] = $group;
            }

            $this->_groups = new MemoizableArray($groups);
        }

        return $this->_groups;
    }

    private function _createGroupsQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'sortOrder',
                'dateDeleted',
                'uid',
            ])
            ->from([Table::FORMIE_FORM_GROUPS])
            ->where(['dateDeleted' => null])
            ->orderBy(['sortOrder' => SORT_ASC]);
    }

    private function _getGroupRecord(string $uid, bool $withTrashed = false): FormGroupRecord
    {
        $query = $withTrashed ? FormGroupRecord::findWithTrashed() : FormGroupRecord::find();
        $query->andWhere(['uid' => $uid]);

        return $query->one() ?? new FormGroupRecord();
    }
}
