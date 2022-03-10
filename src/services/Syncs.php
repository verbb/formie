<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\events\SyncedFieldEvent;
use verbb\formie\models\Sync as SyncModel;
use verbb\formie\models\SyncField as SyncFieldModel;
use verbb\formie\records\Sync as SyncRecord;
use verbb\formie\records\SyncField as SyncFieldRecord;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\helpers\StringHelper;

use Throwable;
use yii\db\StaleObjectException;

class Syncs extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_SYNCED_FIELD = 'beforeSaveSyncedField';
    public const EVENT_AFTER_SAVE_SYNCED_FIELD = 'afterSaveSyncedField';

    // Public Methods
    // =========================================================================

    /**
     * Parses a reference ID and returns the referenced field.
     *
     * @param string $refId
     * @return FormFieldInterface|null
     */
    public function parseSyncId(string $refId): ?FormFieldInterface
    {
        $parts = StringHelper::explode($refId, ':');
        if (count($parts) !== 2 || $parts[0] !== 'sync') {
            return null;
        }

        $fieldId = $parts[1];

        /* @var FormFieldInterface $field */
        return Craft::$app->getFields()->getFieldById($fieldId);
    }

    /**
     * Returns all syncs.
     *
     * @return SyncModel[]
     */
    public function getAllSyncs(): array
    {
        $rows = $this->_createSyncsQuery()->all();

        $syncs = [];
        foreach ($rows as $row) {
            $syncs[] = new SyncModel($row);
        }

        return $syncs;
    }

    /**
     * Gets a field's sync.
     *
     * @param FormFieldInterface $field
     * @return SyncModel|null
     */
    public function getFieldSync(FormFieldInterface $field): ?SyncModel
    {
        /* @var FormField $field */
        $row = $this->_createSyncsQuery()
            ->innerJoin('{{%formie_syncfields}} sf', '[[s.id]] = [[sf.syncId]]')
            ->where(['sf.fieldId' => $field->id])
            ->one();

        if ($row) {
            return new SyncModel($row);
        }

        return null;
    }

    /**
     * Returns a sync by its ID.
     *
     * @param int $id
     * @return SyncModel|null
     */
    public function getSyncById(int $id): ?SyncModel
    {
        $row = $this->_createSyncsQuery()
            ->where(['id' => $id])
            ->one();

        if ($row) {
            return new SyncModel($row);
        }

        return null;
    }

    /**
     * Returns all sync fields for a sync.
     *
     * @param SyncModel $sync
     * @return SyncFieldModel[]
     */
    public function getSyncFieldsBySync(SyncModel $sync): array
    {
        $rows = $this->_createSyncFieldsQuery()
            ->where(['syncId' => $sync->id])
            ->all();

        $syncFields = [];
        foreach ($rows as $row) {
            $syncFields[] = new SyncFieldModel($row);
        }

        return $syncFields;
    }

    /**
     * Checks whether a field has an existing sync.
     *
     * @param FormFieldInterface $field
     * @return bool
     */
    public function isSynced(FormFieldInterface $field): bool
    {
        $sync = $this->getFieldSync($field);
        return $sync && $sync->hasFields();
    }

    /**
     * Deletes a sync by its ID.
     *
     * @param int $id
     * @throws StaleObjectException
     */
    public function deleteSyncById(int $id): void
    {
        $syncRecord = $this->_getSyncRecord($id);
        $syncRecord->delete();
    }

    /**
     * Syncs a field's settings to all its synced fields.
     *
     * @param FormFieldInterface $field
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function syncField(FormFieldInterface $field): void
    {
        /* @var FormField $field */
        $sync = $this->getFieldSync($field);
        if (!$sync) {
            return;
        }

        foreach ($sync->getCustomFields() as $fieldSync) {
            $otherField = $fieldSync->getField();

            /* @var FormField $otherField */

            if ($otherField->id == $field->id) {
                continue;
            }

            $settings = $field->getSettings();
            Craft::configure($otherField, $settings);

            $attributes = $field->getAttributes([
                'name',
                'handle',
                'instructions',
            ]);
            Craft::configure($otherField, $attributes);

            // Fire an 'beforeSaveSyncedField' event
            if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_SYNCED_FIELD)) {
                $this->trigger(self::EVENT_BEFORE_SAVE_SYNCED_FIELD, new SyncedFieldEvent([
                    'field' => $otherField,
                ]));
            }

            Craft::$app->getFields()->saveField($otherField);

            // Fire an 'afterSaveSyncedField' event
            if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_SYNCED_FIELD)) {
                $this->trigger(self::EVENT_AFTER_SAVE_SYNCED_FIELD, new SyncedFieldEvent([
                    'field' => $otherField,
                ]));
            }
        }
    }

    /**
     * Creates a sync from one field to another.
     *
     * @param FormFieldInterface $from
     * @param FormFieldInterface $to
     * @return SyncModel|null
     */
    public function createSync(FormFieldInterface $from, FormFieldInterface $to): ?SyncModel
    {
        /* @var FormField $from */
        /* @var FormField $to */

        if (!$from->id || !$to->id) {
            return null;
        }

        if ($from->id == $to->id) {
            // It's the same field.
            return null;
        }

        $sync = $this->getFieldSync($to);
        if (!$sync) {
            $sync = new SyncModel();
            $sync->addField($to);
        }

        $sync->addField($from);

        return $sync;
    }

    /**
     * Saves a sync model.
     *
     * @param SyncModel $sync
     * @param bool $runValidation
     * @return bool
     * @throws Throwable
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function saveSync(SyncModel $sync, bool $runValidation = true): bool
    {
        if ($runValidation && !$sync->validate()) {
            Formie::log('Sync not saved due to validation error.');

            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $syncRecord = $this->_getSyncRecord($sync->id);
            $syncRecord->save(false);

            $sync->id = $syncRecord->id;

            foreach ($sync->getCustomFields() as $syncField) {
                $syncField->setSync($sync);

                $syncFieldRecord = $this->_getSyncFieldRecord($syncField->id);
                $syncFieldRecord->syncId = $syncField->syncId;
                $syncFieldRecord->fieldId = $syncField->fieldId;
                $syncFieldRecord->save(false);

                $syncField->id = $syncFieldRecord->id;
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return true;
    }

    /**
     * Deletes empty syncs.
     *
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function pruneSyncs($consoleInstance = null): void
    {
        foreach ($this->getAllSyncs() as $sync) {
            if (!$sync->hasFields()) {
                $this->deleteSyncById($sync->id);
            }
        }
    }


    // Private Methods
    // =========================================================================

    /**
     * Returns a query prepped for querying syncs.
     *
     * @return Query
     */
    private function _createSyncsQuery(): Query
    {
        return (new Query())
            ->select([
                's.id',
            ])
            ->from(['{{%formie_syncs}} s']);
    }

    /**
     * Returns a query prepped for querying sync fields.
     *
     * @return Query
     */
    private function _createSyncFieldsQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'syncId',
                'fieldId',
            ])
            ->from(['{{%formie_syncfields}}']);
    }

    /**
     * Gets a sync record by its ID, or a new sync record
     * if it wasn't provided or was not found.
     *
     * @param int|string|null $id
     * @return SyncRecord
     */
    private function _getSyncRecord(int|string|null $id): SyncRecord
    {
        /** @var SyncRecord $sync */
        if ($id && $sync = SyncRecord::find()->where(['id' => $id])->one()) {
            return $sync;
        }

        return new SyncRecord();
    }

    /**
     * Gets a sync field record by its ID, or a new sync field record
     * if it wasn't provided or was not found.
     *
     * @param int|string|null $id
     * @return SyncFieldRecord
     */
    private function _getSyncFieldRecord(int|string|null $id): SyncFieldRecord
    {
        /** @var SyncFieldRecord $syncField */
        if ($id && $syncField = SyncFieldRecord::find()->where(['id' => $id])->one()) {
            return $syncField;
        }

        return new SyncFieldRecord();
    }
}
