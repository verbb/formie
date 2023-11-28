<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\events\SyncedFieldEvent;
use verbb\formie\helpers\StringHelper;

use Craft;
use craft\base\Component;
use craft\db\Query;

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

    // public function parseSyncId(string $refId): ?FormFieldInterface
    // {
    //     $parts = StringHelper::explode($refId, ':');
    //     if (count($parts) !== 2 || $parts[0] !== 'sync') {
    //         return null;
    //     }

    //     $fieldId = $parts[1];

    //     /* @var FormFieldInterface $field */
    //     return Craft::$app->getFields()->getFieldById($fieldId);
    // }

    // public function getAllSyncs(): array
    // {
    //     $rows = $this->_createSyncsQuery()->all();

    //     $syncs = [];
    //     foreach ($rows as $row) {
    //         $syncs[] = new SyncModel($row);
    //     }

    //     return $syncs;
    // }

    // public function getFieldSync(FormFieldInterface $field): ?SyncModel
    // {
    //     /* @var FormField $field */
    //     $row = $this->_createSyncsQuery()
    //         ->innerJoin('{{%formie_syncfields}} sf', '[[s.id]] = [[sf.syncId]]')
    //         ->where(['sf.fieldId' => $field->id])
    //         ->one();

    //     if ($row) {
    //         return new SyncModel($row);
    //     }

    //     return null;
    // }

    // public function getSyncById(int $id): ?SyncModel
    // {
    //     $row = $this->_createSyncsQuery()
    //         ->where(['id' => $id])
    //         ->one();

    //     if ($row) {
    //         return new SyncModel($row);
    //     }

    //     return null;
    // }

    // public function getSyncFieldsBySync(SyncModel $sync): array
    // {
    //     $rows = $this->_createSyncFieldsQuery()
    //         ->where(['syncId' => $sync->id])
    //         ->all();

    //     $syncFields = [];
    //     foreach ($rows as $row) {
    //         $syncFields[] = new SyncFieldModel($row);
    //     }

    //     return $syncFields;
    // }

    // public function isSynced(FormFieldInterface $field): bool
    // {
    //     $sync = $this->getFieldSync($field);
    //     return $sync && $sync->hasFields();
    // }

    // public function deleteSyncById(int $id): void
    // {
    //     $syncRecord = $this->_getSyncRecord($id);
    //     $syncRecord->delete();
    // }

    // public function syncField(FormFieldInterface $field): void
    // {
    //     /* @var FormField $field */
    //     $sync = $this->getFieldSync($field);
    //     if (!$sync) {
    //         return;
    //     }

    //     $fieldsService = Craft::$app->getFields();

    //     foreach ($sync->getCustomFields() as $fieldSync) {
    //         $otherField = $fieldSync->getField();

    //         /* @var FormField $otherField */
    //         if ($otherField->id == $field->id) {
    //             continue;
    //         }

    //         // We need to get the field's form in order to get the correct content table context
    //         // because when saving a field, the current context should be correct, and as we're
    //         // dealing with other form fields, we need to switch each time.
    //         $form = $otherField->getForm();

    //         if (!$form) {
    //             continue;
    //         }

    //         // Save the current content table
    //         $originalFieldContext = $fieldsService->fieldContext;
    //         $originalContentTable = $fieldsService->contentTable;

    //         // Set the field context.
    //         $fieldsService->fieldContext = $form->getFormFieldContext();
    //         $fieldsService->contentTable = $form->fieldContentTable;

    //         $settings = $field->getSettings();

    //         // Don't overwrite some values
    //         unset($settings['formId']);

    //         Craft::configure($otherField, $settings);

    //         $attributes = $field->getAttributes([
    //             'name',
    //             'handle',
    //             'instructions',
    //         ]);
    //         Craft::configure($otherField, $attributes);

    //         // Fire an 'beforeSaveSyncedField' event
    //         if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_SYNCED_FIELD)) {
    //             $this->trigger(self::EVENT_BEFORE_SAVE_SYNCED_FIELD, new SyncedFieldEvent([
    //                 'field' => $otherField,
    //             ]));
    //         }

    //         Craft::$app->getFields()->saveField($otherField);

    //         // Fire an 'afterSaveSyncedField' event
    //         if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_SYNCED_FIELD)) {
    //             $this->trigger(self::EVENT_AFTER_SAVE_SYNCED_FIELD, new SyncedFieldEvent([
    //                 'field' => $otherField,
    //             ]));
    //         }

    //         // Set content table back to original value.
    //         $fieldsService->fieldContext = $originalFieldContext;
    //         $fieldsService->contentTable = $originalContentTable;
    //     }
    // }

    // public function createSync(FormFieldInterface $from, FormFieldInterface $to): ?SyncModel
    // {
    //     /* @var FormField $from */
    //     /* @var FormField $to */

    //     if (!$from->id || !$to->id) {
    //         return null;
    //     }

    //     if ($from->id == $to->id) {
    //         // It's the same field.
    //         return null;
    //     }

    //     $sync = $this->getFieldSync($to);
    //     if (!$sync) {
    //         $sync = new SyncModel();
    //         $sync->addField($to);
    //     }

    //     $sync->addField($from);

    //     return $sync;
    // }

    // public function saveSync(SyncModel $sync, bool $runValidation = true): bool
    // {
    //     if ($runValidation && !$sync->validate()) {
    //         Formie::info('Sync not saved due to validation error.');

    //         return false;
    //     }

    //     $transaction = Craft::$app->getDb()->beginTransaction();

    //     try {
    //         $syncRecord = $this->_getSyncRecord($sync->id);
    //         $syncRecord->save(false);

    //         $sync->id = $syncRecord->id;

    //         foreach ($sync->getCustomFields() as $syncField) {
    //             $syncField->setSync($sync);

    //             $syncFieldRecord = $this->_getSyncFieldRecord($syncField->id);
    //             $syncFieldRecord->syncId = $syncField->syncId;
    //             $syncFieldRecord->fieldId = $syncField->fieldId;
    //             $syncFieldRecord->save(false);

    //             $syncField->id = $syncFieldRecord->id;
    //         }

    //         $transaction->commit();
    //     } catch (Throwable $e) {
    //         $transaction->rollBack();
    //         throw $e;
    //     }

    //     return true;
    // }

    // public function pruneSyncs($consoleInstance = null): void
    // {
    //     foreach ($this->getAllSyncs() as $sync) {
    //         if (!$sync->hasFields()) {
    //             $this->deleteSyncById($sync->id);
    //         }
    //     }
    // }


    // // Private Methods
    // // =========================================================================

    // private function _createSyncsQuery(): Query
    // {
    //     return (new Query())
    //         ->select([
    //             's.id',
    //         ])
    //         ->from(['{{%formie_syncs}} s']);
    // }

    // private function _createSyncFieldsQuery(): Query
    // {
    //     return (new Query())
    //         ->select([
    //             'id',
    //             'syncId',
    //             'fieldId',
    //         ])
    //         ->from(['{{%formie_syncfields}}']);
    // }

    // private function _getSyncRecord(int|string|null $id): SyncRecord
    // {
    //     /** @var SyncRecord $sync */
    //     if ($id && $sync = SyncRecord::find()->where(['id' => $id])->one()) {
    //         return $sync;
    //     }

    //     return new SyncRecord();
    // }

    // private function _getSyncFieldRecord(int|string|null $id): SyncFieldRecord
    // {
    //     /** @var SyncFieldRecord $syncField */
    //     if ($id && $syncField = SyncFieldRecord::find()->where(['id' => $id])->one()) {
    //         return $syncField;
    //     }

    //     return new SyncFieldRecord();
    // }
}
