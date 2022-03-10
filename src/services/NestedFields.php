<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\base\NestedFieldTrait;
use verbb\formie\elements\NestedFieldRow;
use verbb\formie\elements\db\NestedFieldRowQuery;
use verbb\formie\migrations\CreateFormContentTable;
use verbb\formie\models\FieldLayout;
use verbb\formie\records\Nested as NestedRecord;

use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\Table;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\helpers\ArrayHelper;

use Exception;
use Throwable;

class NestedFields extends Component
{
    // Properties
    // =========================================================================

    /**
     * @var FieldLayout[]
     */
    private array $_fieldLayoutsByFieldId = [];


    // Public Methods
    // =========================================================================

    /**
     * Returns a field layout for a nested field.
     *
     * @param NestedFieldInterface $field
     * @return FieldLayout|null
     */
    public function getFieldLayout(NestedFieldInterface $field): ?FieldLayout
    {
        /* @var NestedFieldInterface|NestedFieldTrait|FormField $field */

        if (isset($this->_fieldLayoutsByFieldId[$field->id])) {
            return $this->_fieldLayoutsByFieldId[$field->id];
        }

        $record = NestedRecord::findOne(['fieldId' => $field->id]);
        if ($record && $fieldLayout = Formie::$plugin->getFields()->getLayoutById($record->fieldLayoutId)) {
            return $this->_fieldLayoutsByFieldId[$field->id] = $fieldLayout;
        }

        return null;
    }

    /**
     * Sets a
     *
     * @param NestedFieldInterface $field
     * @param FieldLayout $fieldLayout
     * @return FieldLayout
     */
    public function setFieldLayout(NestedFieldInterface $field, FieldLayout $fieldLayout): FieldLayout
    {
        /* @var NestedFieldInterface|NestedFieldTrait|FormField $field */

        $record = NestedRecord::findOne(['fieldId' => $field->id]);
        if (!$record) {
            $record = new NestedRecord([
                'fieldId' => $field->id,
            ]);
        }

        $record->fieldLayoutId = $fieldLayout->id;
        $record->save();

        $field->setFieldLayout($fieldLayout);

        return $this->_fieldLayoutsByFieldId[$field->id] = $fieldLayout;
    }

    /**
     * Defines a unique content table name for a nested field.
     *
     * @param NestedFieldInterface $field
     * @return string
     */
    public function defineContentTableName(NestedFieldInterface $field): string
    {
        /* @var NestedFieldInterface|NestedFieldTrait|FormField $field */

        $db = Craft::$app->getDb();
        $i = -1;

        $formContext = explode(':', $field->context);
        $formId = Db::idByUid('{{%formie_forms}}', $formContext[1]);
        $baseName = 'fmc_' . $formId . '_' . StringHelper::toLowerCase($field->handle);

        // Trim the table just in case to prevent column limit of 64.
        // Factor in increments (for the nested field ID), formId, and table prefix for some wiggle room.
        $baseName = substr($baseName, 0, (60 - strlen($db->tablePrefix)));

        do {
            $i++;
            $name = '{{%' . $baseName . ($i !== 0 ? '_' . $i : '') . '}}';
        } while ($name !== $field->contentTable && $db->tableExists($name));

        return $name;
    }

    public function saveField(NestedFieldInterface $nestedField): bool
    {
        /* @var NestedFieldInterface|NestedFieldTrait|FormField $nestedField */

        if (!$nestedField->contentTable) {
            // Silently fail if this is a migration or console request
            $request = Craft::$app->getRequest();

            if ($request->getIsConsoleRequest() || $request->getUrl() == '/actions/update/updateDatabase') {
                return true;
            }

            throw new Exception('Unable to save a nested field’s settings without knowing its content table: ' . $nestedField->contentTable);
        }

        $fieldsService = Craft::$app->getFields();
        $contentService = Craft::$app->getContent();

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            // Do we need to create/rename the content table?
            if (!$db->tableExists($nestedField->contentTable)) {
                $oldContentTable = $nestedField->oldSettings['contentTable'] ?? null;

                if ($oldContentTable && $db->tableExists($oldContentTable)) {
                    Db::renameTable($oldContentTable, $nestedField->contentTable);
                } else {
                    $this->_createContentTable($nestedField->contentTable);
                }
            }

            $originalContentTable = $contentService->contentTable;
            $originalFieldContext = $contentService->fieldContext;

            $contentService->contentTable = $nestedField->contentTable;
            $contentService->fieldContext = $nestedField->getFormFieldContext();

            // Save fields.
            if ($fieldLayout = $nestedField->getFieldLayout()) {
                $allFields = $fieldsService->getAllFields($nestedField->getFormFieldContext());
                $allFieldIds = ArrayHelper::getColumn($fieldLayout->getCustomFields(), 'id');

                // Delete deleted fields.
                foreach ($allFields as $field) {
                    /* @var FormField $field */
                    if (!in_array($field->id, $allFieldIds)) {
                        $fieldsService->deleteField($field);
                    }
                }

                foreach ($fieldLayout->getCustomFields() as $field) {
                    // Ensure fields retain a formId
                    $field->formId = $nestedField->formId;

                    $fieldsService->saveField($field);
                }
            }

            $fieldsService->saveLayout($fieldLayout);

            $contentService->contentTable = $originalContentTable;
            $contentService->fieldContext = $originalFieldContext;

            $this->setFieldLayout($nestedField, $fieldLayout);

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return true;
    }

    /**
     * Deletes a nested field.
     *
     * @param NestedFieldInterface $nestedField
     * @return bool
     * @throws Throwable
     */
    public function deleteNestedField(NestedFieldInterface $nestedField): bool
    {
        /* @var NestedFieldInterface|NestedFieldTrait|FormField $nestedField */

        // Clear the schema cache
        $db = Craft::$app->getDb();
        $db->getSchema()->refresh();

        $transaction = $db->beginTransaction();
        try {
            $originalContentTable = Craft::$app->getContent()->contentTable;
            Craft::$app->getContent()->contentTable = $nestedField->contentTable;

            // Delete the field layout.
            if ($fieldLayout = $nestedField->getFieldLayout()) {
                Craft::$app->getDb()->createCommand()
                    ->delete(Table::FIELDLAYOUTS, ['id' => $fieldLayout->id])
                    ->execute();
            }

            // Delete the sub fields
            $fields = Craft::$app->getFields()->getAllFields($nestedField->getFormFieldContext());
            foreach ($fields as $field) {
                Craft::$app->getFields()->deleteField($field);
            }

            // Drop the content table
            $db->createCommand()
                ->dropTable($nestedField->contentTable)
                ->execute();

            Craft::$app->getContent()->contentTable = $originalContentTable;

            $transaction->commit();

            return true;
        } catch (Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }
    }

    /**
     * Saves a nested field elements.
     *
     * @param NestedFieldInterface $field
     * @param ElementInterface $owner
     * @throws Throwable
     */
    public function saveElements(NestedFieldInterface $field, ElementInterface $owner): void
    {
        $elementsService = Craft::$app->getElements();

        /** @var Element $owner */
        /** @var FormField $field */
        /** @var NestedFieldRowQuery $query */
        $query = $owner->getFieldValue($field->handle);

        /** @var NestedFieldRow[] $rows */
        if (($rows = $query->getCachedResult()) !== null) {
            $saveAll = false;
        } else {
            $rowsQuery = clone $query;
            $rows = $rowsQuery->status(null)->all();
            $saveAll = true;
        }
        $rowIds = [];
        $sortOrder = 0;
        $db = Craft::$app->getDb();

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            foreach ($rows as $row) {
                $sortOrder++;
                if ($saveAll || !$row->id || $row->dirty) {
                    $row->siteId = $owner->siteId;
                    $row->ownerId = $owner->id;
                    $row->sortOrder = $sortOrder;
                    $elementsService->saveElement($row, false);
                } else if ((int)$row->sortOrder !== $sortOrder) {
                    // Just update its sortOrder
                    $row->sortOrder = $sortOrder;
                    $db->createCommand()->update('{{%formie_nestedfieldrows}}',
                        ['sortOrder' => $sortOrder],
                        ['id' => $row->id], [], false)
                        ->execute();
                }

                $rowIds[] = $row->id;
            }

            // Delete any rows that shouldn't be there anymore
            $this->_deleteOtherRows($field, $owner, $rowIds);

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Duplicates nested field rows from one owner element to another.
     *
     * @param NestedFieldInterface $field
     * @param ElementInterface $source
     * @param ElementInterface $target
     * @param bool $checkOtherSites
     * @throws Throwable
     */
    public function duplicateBlocks(NestedFieldInterface $field, ElementInterface $source, ElementInterface $target, bool $checkOtherSites = false): void
    {
        $elementsService = Craft::$app->getElements();

        /** @var Element $source */
        /** @var Element $target */
        /** @var NestedFieldRowQuery $query */
        $query = $source->getFieldValue($field->handle);

        /** @var NestedFieldRow[] $rows */
        if (($rows = $query->getCachedResult()) === null) {
            $rowsQuery = clone $query;
            $rows = $rowsQuery->status(null)->all();
        }
        $newRowIds = [];

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            foreach ($rows as $row) {
                /** @var NestedFieldRow $newRow */
                $newRow = $elementsService->duplicateElement($row, [
                    'ownerId' => $target->id,
                    'owner' => $target,
                    'siteId' => $target->siteId,
                    'propagating' => false,
                ]);
                $newRowIds[] = $newRow->id;
            }

            // Delete any rows that shouldn't be there anymore
            $this->_deleteOtherRows($field, $target, $newRowIds);

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }


    // Private Methods
    // =========================================================================

    /**
     * Creates the content table for a nested field.
     *
     * @param string $tableName
     * @throws Throwable
     */
    private function _createContentTable(string $tableName): void
    {
        $migration = new CreateFormContentTable([
            'tableName' => $tableName,
        ]);

        ob_start();
        $migration->up();
        ob_end_clean();
    }

    /**
     * Deletes rows from an owner element
     *
     * @param NestedFieldInterface $field
     * @param ElementInterface $owner
     * @param int[] $except
     * @throws Throwable
     */
    private function _deleteOtherRows(NestedFieldInterface $field, ElementInterface $owner, array $except): void
    {
        /** @var Element $owner */
        $deleteBlocks = NestedFieldRow::find()
            ->status(null)
            ->ownerId($owner->id)
            ->fieldId($field->id)
            ->siteId($owner->siteId)
            ->andWhere(['not', ['elements.id' => $except]])
            ->all();

        $elementsService = Craft::$app->getElements();

        foreach ($deleteBlocks as $deleteBlock) {
            $elementsService->deleteElement($deleteBlock);
        }
    }
}
