<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\events\ModifyMigrationAddressConfigEvent;
use verbb\formie\fields;
use verbb\formie\fields\Group;
use verbb\formie\fields\Repeater;
use verbb\formie\fields\subfields;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\FieldLayout;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\MigrationHelper;
use craft\migrations\BaseContentRefactorMigration;

use Exception;
use Throwable;

class m231125_000000_craft5 extends BaseContentRefactorMigration
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_MIGRATION_ADDRESS_CONFIG = 'modifyMigrationAddressConfig';

    // Properties
    // =========================================================================

    protected bool $preserveOldData = true;


    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        App::maxPowerCaptain();

        // Create the new layout/page/row/fields tables
        $this->_addNewLayoutTables();

        // We aren't using field layouts, so update title's manually
        $this->_updateFormTitles();
        $this->_updateSubmissionTitles();

        // Populate and create new field layouts for forms, based on the prep work in Formie 2.x
        if (!$this->_addPopulateLayouts()) {
            return false;
        }

        // Update the field layout and form elements (not the form field layout fields)
        $this->_migrateTemplateFieldLayout();

        // Move all content from custom content tables to `formie_submissions`
        $this->_migrateSubmissionContent();

        // Do the same for Group/Repeater fields which are stored separately
        $this->_migrateNestedContent();

        // Migrate any relations via element field to store their content in the content table, not in `relations`
        $this->_migrateRelationFields();

        // Migrate any payment data referring to old fields before they're deleted
        $this->_migratePaymentFields();

        // Update synced fields to new format
        $this->_updateSyncFields();

        // Perform the final destructive tasks
        $this->_cleanupOldTables();

        return true;
    }

    public function safeDown(): bool
    {
        echo "m231125_000000_craft5 cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _addNewLayoutTables(): void
    {
        if (!$this->db->tableExists(Table::FORMIE_FIELD_LAYOUTS)) {
            $this->createTable(Table::FORMIE_FIELD_LAYOUTS, [
                'id' => $this->primaryKey(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists(Table::FORMIE_FIELD_LAYOUT_PAGES)) {
            $this->createTable(Table::FORMIE_FIELD_LAYOUT_PAGES, [
                'id' => $this->primaryKey(),
                'layoutId' => $this->integer()->notNull(),
                'label' => $this->text()->notNull(),
                'sortOrder' => $this->smallInteger()->unsigned(),
                'settings' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists(Table::FORMIE_FIELD_LAYOUT_ROWS)) {
            $this->createTable(Table::FORMIE_FIELD_LAYOUT_ROWS, [
                'id' => $this->primaryKey(),
                'layoutId' => $this->integer()->notNull(),
                'pageId' => $this->integer(),
                'sortOrder' => $this->smallInteger()->unsigned(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists(Table::FORMIE_FIELDS)) {
            $this->createTable(Table::FORMIE_FIELDS, [
                'id' => $this->primaryKey(),
                'layoutId' => $this->integer()->notNull(),
                'pageId' => $this->integer(),
                'rowId' => $this->integer()->notNull(),
                'syncId' => $this->integer(),
                'label' => $this->text()->notNull(),
                'handle' => $this->string(64)->notNull(),
                'type' => $this->string()->notNull(),
                'sortOrder' => $this->smallInteger()->unsigned(),
                'settings' => $this->mediumText(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->columnExists(Table::FORMIE_FORMS, 'layoutId')) {
            $this->addColumn(Table::FORMIE_FORMS, 'layoutId', $this->integer()->after('settings'));
        }

        if (!$this->db->columnExists(Table::FORMIE_SUBMISSIONS, 'content')) {
            $this->addColumn(Table::FORMIE_SUBMISSIONS, 'content', $this->json()->after('id'));
        }
        
        // In case the migration is run again
        MigrationHelper::dropAllForeignKeysOnTable(Table::FORMIE_FIELD_LAYOUT_PAGES, $this);
        MigrationHelper::dropAllForeignKeysOnTable(Table::FORMIE_FIELD_LAYOUT_ROWS, $this);
        MigrationHelper::dropAllForeignKeysOnTable(Table::FORMIE_FIELDS, $this);
        MigrationHelper::dropAllIndexesOnTable(Table::FORMIE_FIELD_LAYOUT_PAGES, $this);
        MigrationHelper::dropAllIndexesOnTable(Table::FORMIE_FIELD_LAYOUT_ROWS, $this);
        MigrationHelper::dropAllIndexesOnTable(Table::FORMIE_FIELDS, $this);

        $this->dropForeignKeyIfExists(Table::FORMIE_FORMS, ['layoutId']);
        $this->dropIndexIfExists(Table::FORMIE_FORMS, ['layoutId']);

        $this->createIndex(null, Table::FORMIE_FIELD_LAYOUT_PAGES, 'layoutId', false);
        $this->createIndex(null, Table::FORMIE_FIELD_LAYOUT_ROWS, 'layoutId', false);
        $this->createIndex(null, Table::FORMIE_FIELD_LAYOUT_ROWS, 'pageId', false);
        $this->createIndex(null, Table::FORMIE_FIELDS, 'layoutId', false);
        $this->createIndex(null, Table::FORMIE_FIELDS, 'pageId', false);
        $this->createIndex(null, Table::FORMIE_FIELDS, 'rowId', false);
        $this->createIndex(null, Table::FORMIE_FIELDS, 'handle', false);
        $this->createIndex(null, Table::FORMIE_FIELDS, 'syncId', false);

        $this->addForeignKey(null, Table::FORMIE_FIELD_LAYOUT_PAGES, ['layoutId'], Table::FORMIE_FIELD_LAYOUTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FIELD_LAYOUT_ROWS, ['layoutId'], Table::FORMIE_FIELD_LAYOUTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FIELD_LAYOUT_ROWS, ['pageId'], Table::FORMIE_FIELD_LAYOUT_PAGES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FIELDS, ['layoutId'], Table::FORMIE_FIELD_LAYOUTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FIELDS, ['pageId'], Table::FORMIE_FIELD_LAYOUT_PAGES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FIELDS, ['rowId'], Table::FORMIE_FIELD_LAYOUT_ROWS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FIELDS, ['syncId'], Table::FORMIE_FIELDS, ['id'], 'SET NULL', null);

        // Alter old field layout specs
        $this->createIndex(null, Table::FORMIE_FORMS, 'layoutId', false);
        $this->addForeignKey(null, Table::FORMIE_FORMS, ['layoutId'], Table::FORMIE_FIELD_LAYOUTS, ['id'], 'SET NULL', null);

        // Remove old field layouts
        $this->dropForeignKeyIfExists(Table::FORMIE_FORMS, ['fieldLayoutId']);
        $this->dropIndexIfExists(Table::FORMIE_FORMS, ['fieldLayoutId']);
    }

    private function _addPopulateLayouts(): bool
    {
        $forms = (new Query())->from(Table::FORMIE_FORMS)->all();

        echo '    > Updating all forms with new field layout.' . PHP_EOL;

        foreach ($forms as $form) {
            $layoutConfig = (new Query())->select('layoutConfig')->from('{{%formie_newlayout}}')->where(['formId' => $form['id']])->scalar();

            if (!$layoutConfig) {
                echo '    > ' . $form['handle'] . ': Unable to find field layout data.' . PHP_EOL;

                continue;
            }

            $layoutConfig = Json::decode($layoutConfig);

            // Check for legacy field layout format, introduced before the beta
            $layoutConfig = $this->_processLegacyLayout($layoutConfig);

            // Sub-fields also need to be processed here, before their settings are removed as being invalid
            $layoutConfig = $this->_processLayoutSubFields($layoutConfig);

            // Any extra processing (for some fields)
            $layoutConfig = $this->_processLayoutFields($layoutConfig);

            $formLayout = new FieldLayout($layoutConfig);

            if (!Formie::$plugin->getFields()->saveLayout($formLayout)) {
                echo '    > ' . $form['handle'] . ': Unable to save field layout - ' . Json::encode($formLayout->getErrorsTree()) . PHP_EOL;
                // echo '    > ' . Json::encode($layoutConfig);

                return false;
            }

            Db::update(Table::FORMIE_FORMS, ['layoutId' => $formLayout->id], ['id' => $form['id']]);

            echo '    > Updated Form ' . $form['handle'] . ' field layout.' . PHP_EOL;
        }

        return true;
    }

    private function _migrateTemplateFieldLayout(): void
    {
        $forms = (new Query())->from(Table::FORMIE_FORMS)->all();

        echo '    > Updating all forms with new template field layout.' . PHP_EOL;

        foreach ($forms as $form) {
            $fieldLayout = null;
            $templateId = $form['templateId'] ?? null;

            if ($templateId) {
                $template = (new Query())->from(Table::FORMIE_FORM_TEMPLATES)->where(['id' => $templateId])->one();
                $fieldLayoutId = $template['fieldLayoutId'] ?? null;

                if ($template && $fieldLayoutId) {
                    $fieldLayout = Craft::$app->getFields()->getLayoutById($fieldLayoutId);

                    $this->updateElements([$form['id']], $fieldLayout);
                }
            }
        }
    }

    private function _migrateSubmissionContent(): void
    {
        // Fetch again with new content populated
        $forms = (new Query())->from(Table::FORMIE_FORMS)->all();

        echo '    > Updating Submission content to JSON.' . PHP_EOL;

        foreach ($forms as $form) {
            if (!$form['fieldContentTable']) {
                echo '    > ' . $form['handle'] . ': missing `fieldContentTable`.' . PHP_EOL;
                
                continue;
            }

            if (!$form['layoutId']) {
                echo '    > ' . $form['handle'] . ': missing `layoutId`.' . PHP_EOL;
                
                continue;
            }

            $submissions = (new Query())->from(Table::FORMIE_SUBMISSIONS)->where(['formId' => $form['id']])->all();
            $layout = Formie::$plugin->getFields()->getLayoutById($form['layoutId']);

            foreach ($submissions as $submission) {
                // Create the content from a custom table to JSON
                $content = $this->_createContentJson($form['fieldContentTable'], $submission['id'], 'formie:' . $form['uid'], $layout);

                Db::update(Table::FORMIE_SUBMISSIONS, ['content' => $content], ['id' => $submission['id']]);

                echo '    > Updated Submission #' . $submission['id'] . ' content.' . PHP_EOL;
            }
        }
    }

    private function _migrateNestedContent(): void
    {
        $applyNestedFieldContentMigration = function($fields) {
            foreach ($fields as $field) {
                $oldFieldSettings = Json::decode($field['settings']);
                $contentTable = $this->_getNestedContentTableName($field);

                if (!$contentTable) {
                    echo '    > ' . $field['handle'] . ': missing `contentTable`.' . PHP_EOL;

                    continue;
                }

                // Find the new field
                $formUid = str_replace('formie:', '', $field['context']);
                $form = (new Query())->from(Table::FORMIE_FORMS)->where(['uid' => $formUid])->one();

                if (!$form) {
                    echo '    > ' . $field['handle'] . ': unable to find owner form.' . PHP_EOL;

                    continue;
                }

                $newField = (new Query())->from(Table::FORMIE_FIELDS)->where(['layoutId' => $form['layoutId'], 'handle' => $field['handle']])->one();

                if (!$newField) {
                    echo '    > ' . $field['handle'] . ': unable to find new field.' . PHP_EOL;

                    continue;
                }

                $newFieldSettings = Json::decode($newField['settings']);
                $nestedLayoutId = $newFieldSettings['nestedLayoutId'] ?? null;

                if (!$nestedLayoutId) {
                    echo '    > ' . $field['handle'] . ': missing `nestedLayoutId`.' . PHP_EOL;

                    continue;
                }

                $nestedRows = (new Query())->from('{{%formie_nestedfieldrows}}')->where(['fieldId' => $field['id']])->all();
                $layout = Formie::$plugin->getFields()->getLayoutById($nestedLayoutId);

                $submissionContent = [];

                foreach ($nestedRows as $nestedRowKey => $nestedRow) {
                    $rowId = $nestedRow['id'];
                    $submissionId = $nestedRow['ownerId'];

                    $content = $this->_createContentJson($contentTable, $rowId, 'formieField:' . $field['uid'], $layout);

                    if ($content) {
                        if (strstr($field['type'], 'Repeater')) {
                            $submissionContent[$submissionId][$newField['uid']][($nestedRow['sortOrder'] - 1)] = $content;
                        } else {
                            $submissionContent[$submissionId][$newField['uid']] = $content;
                        }
                    }
                }

                foreach ($submissionContent as $submissionId => $fieldContent) {
                    $newContent = (new Query())->select('content')->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $submissionId])->scalar();

                    if (Json::isJsonObject($newContent)) {
                        $newContent = Json::decode($newContent);

                        $newContent = array_merge($newContent, $fieldContent);

                        Db::update(Table::FORMIE_SUBMISSIONS, ['content' => $newContent], ['id' => $submissionId]);

                        echo '    > Updated Submission ' . $submissionId . ' content for nested content.' . PHP_EOL;
                    } else {
                        echo '    > Skipping Submission ' . $submissionId . ' content for nested content, invalid data ' . Json::encode($newContent) . PHP_EOL;
                    }
                }
            }
        };

        // Migrate Group/Repeater field settings - ensure we start at the old fields so we can fetch old block content
        $groupFields = (new Query())->from('{{%fields}}')->where(['type' => 'verbb\formie\fields\formfields\Group'])->all();
        $repeaterFields = (new Query())->from('{{%fields}}')->where(['type' => 'verbb\formie\fields\formfields\Repeater'])->all();

        echo '    > Updating Group fields content to JSON.' . PHP_EOL;

        $applyNestedFieldContentMigration($groupFields);

        echo '    > Updating Repeater fields content to JSON.' . PHP_EOL;

        $applyNestedFieldContentMigration($repeaterFields);
    }

    private function _createContentJson(string $contentTable, int $id, string $context, FieldLayout $layout): array
    {
        $newContent = [];

        if (!Craft::$app->getDb()->tableExists($contentTable)) {
            echo '    > Unable to find missing content table ' . $contentTable . '.' . PHP_EOL;

            return [];
        }

        $contentRow = (new Query())->from($contentTable)->where(['elementId' => $id])->one();

        if ($contentRow) {
            // Create a map to swap field handles with their UIDs
            $uidMap = [];

            foreach ($layout->getFields() as $field) {
                $uidMap[$field->handle] = $field->uid;
            }

            foreach ($contentRow as $column => $value) {
                if (!str_starts_with($column, 'field_')) {
                    continue;
                }

                // We don't store null values for fields, assume it's empty content
                if ($value === null) {
                    continue;
                }

                $handle = str_replace('field_', '', $column);

                // Tricky business to handle the suffix, as older installs didn't require it, and some users use underscores in field names
                $field = (new Query())->from('{{%fields}}')->where(['context' => $context])->one();

                if (!$field) {
                    echo '    > Unable to find field for content matching UID for field ' . $handle . ' in ' . $contentTable . '.' . PHP_EOL;

                    continue;
                }

                if ($field['columnSuffix']) {
                    $handle = str_replace('_' . $field['columnSuffix'], '', $handle);
                }

                // Some older installs don't record the `columnSuffix` in the field settings, but their content able uses them. Risk the check!
                if (str_contains($handle, '_')) {
                    // Does this end with what might be a suffix (8 chars)?
                    $handle = preg_match('/^(.+)_([a-z]{8})$/', $handle, $matches) ? $matches[1] : $handle;
                }

                $uid = $uidMap[$handle] ?? null;

                if (!$uid) {
                    echo '    > Unable to find matching UID for field ' . $handle . ' in ' . $contentTable . '.' . PHP_EOL;

                    continue;
                }

                if ($value && Json::isJsonObject($value)) {
                    // Watch out for variables in values. Not JSON, but a starting `{` will make it think it is.
                    try {
                        $newContent[$uid] = Json::decode($value);
                    } catch (Throwable $e) {
                        $newContent[$uid] = $value;
                    }
                } else {
                    $newContent[$uid] = $value;
                }
            }
        } else {
            echo '    > Unable to find content for element #' . $id . ' in ' . $contentTable . '.' . PHP_EOL;
        }

        return $newContent;
    }

    private function _processLegacyLayout(array $layoutConfig): array
    {
        foreach (($layoutConfig['pages'] ?? []) as $pageKey => $page) {
            foreach (($page['rows'] ?? []) as $rowKey => $row) {
                foreach (($row['fields'] ?? []) as $fieldKey => $field) {
                    $fieldUid = $field['fieldUid'] ?? null;
                    $required = $field['required'] ?? null;

                    if (!$fieldUid) {
                        continue;
                    }

                    // Find the old field by its UID
                    $oldField = (new Query())->from('{{%fields}}')->where(['uid' => $fieldUid])->one();

                    if (!$oldField) {
                        throw new Exception('Unable to find legacy field for UID: ' . $fieldUid);
                    }

                    // Serialize the field again.
                    $newFieldConfig = Json::decode($oldField['settings']);
                    $newFieldConfig['type'] = $oldField['type'];
                    $newFieldConfig['label'] = $oldField['name'];
                    $newFieldConfig['handle'] = $oldField['handle'];
                    $newFieldConfig['required'] = $required;
                    $newFieldConfig['instructions'] = $oldField['instructions'];

                    $layoutConfig['pages'][$pageKey]['rows'][$rowKey]['fields'][$fieldKey] = $newFieldConfig;
                }
            }
        }

        return $layoutConfig;
    }

    private function _processLayoutSubFields(array $layoutConfig): array
    {
        foreach (($layoutConfig['pages'] ?? []) as $pageKey => $page) {
            foreach (($page['rows'] ?? []) as $rowKey => $row) {
                foreach (($row['fields'] ?? []) as $fieldKey => $field) {
                    $processed = $this->_processLayoutSubFieldsField($field);

                    $layoutConfig['pages'][$pageKey]['rows'][$rowKey]['fields'][$fieldKey] = $processed;
                }
            }
        }

        return $layoutConfig;
    }

    private function _processLayoutSubFieldsField(array $field): array
    {
        $type = $field['type'] ?? null;

        if (!$type) {
            return $field;
        }

        if ($rows = $this->_processLayoutSubFieldsRows($field)) {
            $field['rows'] = $rows;
        }

        if ($field['type'] === 'verbb\formie\fields\formfields\Group') {
            $field['rows'] = $this->_processLayoutSubFieldsFieldRows($field['rows'] ?? []);
        }

        if ($field['type'] === 'verbb\formie\fields\formfields\Repeater') {
            $field['rows'] = $this->_processLayoutSubFieldsFieldRows($field['rows'] ?? []);
        }

        return $field;
    }

    private function _processLayoutSubFieldsFieldRows(array $rows): array
    {
        foreach ($rows as $rowKey => $row) {
            foreach (($row['fields'] ?? []) as $fieldKey => $nestedField) {
                $rows[$rowKey]['fields'][$fieldKey] = $this->_processLayoutSubFieldsField($nestedField);
            }
        }

        return $rows;
    }

    private function _processLayoutSubFieldsRows(array &$field): ?array
    {
        $type = $field['type'] ?? null;

        if (!$type) {
            return null;
        }

        if ($field['type'] === 'verbb\formie\fields\formfields\Address') {
            return $this->_getAddressConfig($field);
        }

        if ($field['type'] === 'verbb\formie\fields\formfields\Date') {
            $displayType = $field['displayType'] ?? 'calendar';

            // Remove the old date picker flag, as we now determine type with `displayType`
            unset($field['useDatePicker']);

            if ($displayType == 'calendar' || $displayType == 'datePicker') {
                return $this->_getDateCalendarConfig($field);
            }

            if ($displayType == 'dropdowns') {
                return $this->_getDateDropdownsConfig($field);
            }

            if ($displayType == 'inputs') {
                return $this->_getDateInputsConfig($field);
            }
        }

        if ($field['type'] === 'verbb\formie\fields\formfields\Name') {
            $useMultipleFields = $field['useMultipleFields'] ?? false;

            if ($useMultipleFields) {
                return $this->_getNameConfig($field);
            }
        }

        return null;
    }

    private function _processLayoutFields(array $layoutConfig): array
    {
        foreach (($layoutConfig['pages'] ?? []) as $pageKey => $page) {
            foreach (($page['rows'] ?? []) as $rowKey => $row) {
                foreach (($row['fields'] ?? []) as $fieldKey => $field) {
                    $updatedConfig = false;
                    $type = $field['type'] ?? null;

                    if (!$type) {
                        continue;
                    }

                    if ($field['type'] === 'verbb\formie\fields\formfields\Section') {
                        // Protect against label/handle being empty in some scenarios
                        $field['label'] = $field['label'] ?? StringHelper::appendUniqueIdentifier(Craft::t('formie', 'Section Label '));
                        $field['handle'] = $field['handle'] ?? StringHelper::appendUniqueIdentifier('sectionHandle');

                        $updatedConfig = true;
                    }

                    if ($field['type'] === 'verbb\formie\fields\formfields\Summary') {
                        // Protect against label/handle being empty in some scenarios
                        $field['label'] = $field['label'] ?? StringHelper::appendUniqueIdentifier(Craft::t('formie', 'Summary '));
                        $field['handle'] = $field['handle'] ?? StringHelper::appendUniqueIdentifier('summaryHandle');

                        $updatedConfig = true;
                    }

                    if ($updatedConfig) {
                        $layoutConfig['pages'][$pageKey]['rows'][$rowKey]['fields'][$fieldKey] = $field;
                    }
                }
            }
        }

        return $layoutConfig;
    }

    private function _getNestedContentTableName(array $field): string
    {
        $settings = Json::decode($field['settings']);
        $contentTable = $settings['contentTable'] ?? null;

        if ($contentTable) {
            return $contentTable;
        }

        $suffix = strtolower(substr($field['handle'], 0, 51));

        // In some cases, the content table will be missing, so try and guess it
        foreach (Craft::$app->getDb()->schema->getTableNames() as $tableName) {
            $guessedTable = preg_match('/^fmc_\d+_(' . preg_quote($suffix) . ')$/', $tableName, $matches) ? $matches[1] : false;

            if ($guessedTable) {
                return $guessedTable;
            }
        }

        return '';
    }

    private function _updateFormTitles(): void
    {
        $forms = (new Query())->select('id')->from(Table::FORMIE_FORMS)->all();

        foreach ($forms as $form) {
            $elementId = $form['id'];
            $title = (new Query())->select('title')->from('{{%content}}')->where(['elementId' => $elementId])->scalar();

            if ($title) {
                $this->update(Table::ELEMENTS_SITES, ['title' => $title], ['elementId' => $elementId]);

                // Don't remove the original content here, instead do at the very end
                // $this->update('{{%content}}', ['title' => null], ['elementId' => $elementId]);
            
                echo '    > Updated form #' . $elementId . ' title to ' . $title . '.' . PHP_EOL;
            }
        }
    }

    private function _updateSubmissionTitles(): void
    {
        $submissions = (new Query())->select(['id', 'title'])->from(Table::FORMIE_SUBMISSIONS)->all();

        foreach ($submissions as $submission) {
            $elementId = $submission['id'];
            $title = $submission['title'];

            if ($title) {
                $this->update(Table::ELEMENTS_SITES, ['title' => $title], ['elementId' => $elementId]);
            
                echo '    > Updated submission #' . $elementId . ' title to ' . $title . '.' . PHP_EOL;
            }
        }
    }

    private function _updateSyncFields(): void
    {
        $syncs = (new Query())->from('{{%formie_syncs}}')->all();

        foreach ($syncs as $sync) {
            $syncIds = [];
            $syncFields = (new Query())->from('{{%formie_syncfields}}')->where(['syncId' => $sync['id']])->all();

            foreach ($syncFields as $syncField) {
                $oldField = (new Query())->from('{{%fields}}')->where(['id' => $syncField['fieldId']])->one();

                // Find the new field
                if ($oldField) {
                    $formUid = str_replace('formie:', '', $oldField['context']);
                    $form = (new Query())->from(Table::FORMIE_FORMS)->where(['uid' => $formUid])->one();

                    if ($form) {
                        $newField = (new Query())->from(Table::FORMIE_FIELDS)->where(['layoutId' => $form['layoutId'], 'handle' => $oldField['handle']])->one();

                        if ($newField) {
                            $syncIds[] = $newField['id'];
                        }
                    }
                }
            }

            if ($syncIds) {
                $primarySyncId = $syncIds[0];

                foreach ($syncIds as $syncId) {
                    $this->update(Table::FORMIE_FIELDS, ['syncId' => $primarySyncId], ['id' => $syncId]);
                }
            }
        }
    }

    private function _migrateRelationFields(): void
    {
        $fields = (new Query())->from('{{%fields}}')->all();

        $elementRelations = [];

        foreach ($fields as $field) {
            if (str_contains($field['context'], 'formie:')) {
                $relations = (new Query())->from('{{%relations}}')->where(['fieldId' => $field['id']])->orderBy('sortOrder')->all();

                foreach ($relations as $relation) {
                    $formId = $field['context'];
                    $submissionId = $relation['sourceId'];
                    $fieldId = $field['handle'];
                    $elementId = $relation['targetId'];

                    $elementRelations[$formId][$submissionId][$fieldId][] = $elementId;
                }
            }

            if (str_contains($field['context'], 'formieField:')) {
                $nestedFieldUid = str_replace('formieField:', '', $field['context']);
                $nestedField = (new Query())->from('{{%fields}}')->where(['uid' => $nestedFieldUid])->one();

                if ($nestedField) {
                    $nestedRows = (new Query())->from('{{%formie_nestedfieldrows}}')->where(['fieldId' => $nestedField['id']])->orderBy('sortOrder')->all();

                    foreach ($nestedRows as $key => $nestedRow) {
                        $relations = (new Query())->from('{{%relations}}')->where(['sourceId' => $nestedRow['id'], 'fieldId' => $field['id']])->orderBy('sortOrder')->all();

                        foreach ($relations as $relation) {
                            $formId = $nestedField['context'];
                            $nestedFieldId = $nestedField['handle'];
                            $submissionId = $nestedRow['ownerId'];
                            $fieldId = $field['handle'];
                            $elementId = $relation['targetId'];
                            $rowId = (string)($nestedRow['sortOrder'] - 1);

                            if (str_contains($nestedField['type'], 'Repeater')) {
                                $elementRelations[$formId][$submissionId][$nestedFieldId][$rowId][$fieldId][] = $elementId;
                            } else {
                                $elementRelations[$formId][$submissionId][$nestedFieldId][$fieldId][] = $elementId;
                            }
                        }
                    }
                }
            }
        }

        foreach ($elementRelations as $formContext => $elementRelation) {
            $formUid = str_replace('formie:', '', $formContext);

            $form = (new Query())->from(Table::FORMIE_FORMS)->where(['uid' => $formUid])->one();

            if ($form) {
                $fieldUidMap = [];

                // Get a field UID map for all fields in this form
                $fields = (new Query())->from(Table::FORMIE_FIELDS)->where(['layoutId' => $form['layoutId']])->all();

                foreach ($fields as $field) {
                    $fieldUidMap[$field['handle']] = $field['uid'];

                    if (str_contains($field['type'], 'Repeater') || str_contains($field['type'], 'Group')) {
                        $nestedFieldSettings = Json::decode($field['settings']);
                        $nestedFieldLayoutId = $nestedFieldSettings['nestedLayoutId'] ?? null;

                        if ($nestedFieldLayoutId) {
                            $nestedFields = (new Query())->from(Table::FORMIE_FIELDS)->where(['layoutId' => $nestedFieldLayoutId])->all();

                            foreach ($nestedFields as $nestedField) {
                                $fieldUidMap[$field['handle'] . ':' . $nestedField['handle']] = $nestedField['uid'];
                            }
                        }
                    }
                }

                foreach ($elementRelation as $submissionId => $fieldsContent) {
                    $submission = (new Query())->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $submissionId])->one();

                    if ($submission) {
                        $content = Json::decode($submission['content']) ?? [];

                        // Prep the old field content to use UIDs
                        $preppedContent = [];

                        foreach ($fieldsContent as $fieldHandle => $fieldValues) {
                            $uid = $fieldUidMap[$fieldHandle] ?? null;

                            if ($uid) {
                                $preppedContent[$uid] = $fieldValues;
                            }
                        }

                        $submissionContent = array_replace_recursive($content, $preppedContent);

                        Db::update(Table::FORMIE_SUBMISSIONS, ['content' => $submissionContent], ['id' => $submissionId]);

                        echo '    > Updated Submission ' . $submissionId . ' content for related content.' . PHP_EOL;
                    }
                }
            }
        }
    }

    private function _migratePaymentFields(): void
    {
        $this->dropForeignKeyIfExists(Table::FORMIE_PAYMENTS, ['fieldId']);
        $this->dropForeignKeyIfExists(Table::FORMIE_SUBSCRIPTIONS, ['fieldId']);

        $oldFields = (new Query())->from('{{%fields}}')->where(['type' => 'verbb\\formie\\fields\\formfields\\Payment'])->all();

        $fieldMap = [];

        // Get a map of old to new fields first
        foreach ($oldFields as $oldField) {
            // Handle top-level fields
            if (str_contains($oldField['context'], 'formie:')) {
                $formUid = str_replace('formie:', '', $oldField['context']);
                $form = (new Query())->from(Table::FORMIE_FORMS)->where(['uid' => $formUid])->one();

                if ($form) {
                    $newField = (new Query())->from(Table::FORMIE_FIELDS)->where(['layoutId' => $form['layoutId'], 'handle' => $oldField['handle']])->one();

                    if ($newField) {
                        $fieldMap[$oldField['id']] = $newField['id'];
                    }
                }
            }

            // Handle nested fields
            if (str_contains($oldField['context'], 'formieField:')) {
                $oldNestedFieldUid = str_replace('formieField:', '', $oldField['context']);
                $oldNestedField = (new Query())->from('{{%fields}}')->where(['uid' => $oldNestedFieldUid])->one();

                if ($oldNestedField) {
                    $formUid = str_replace('formie:', '', $oldNestedField['context']);
                    $form = (new Query())->from(Table::FORMIE_FORMS)->where(['uid' => $formUid])->one();

                    if ($form) {
                        $newNestedField = (new Query())->from(Table::FORMIE_FIELDS)->where(['layoutId' => $form['layoutId'], 'handle' => $oldNestedField['handle']])->one();

                        if ($newNestedField) {
                            $nestedFieldSettings = Json::decode($newNestedField['settings']);
                            $nestedLayoutId = $nestedFieldSettings['nestedLayoutId'] ?? null;

                            if ($nestedLayoutId) {
                                $newField = (new Query())->from(Table::FORMIE_FIELDS)->where(['layoutId' => $nestedLayoutId, 'handle' => $oldField['handle']])->one();

                                if ($newField) {
                                    $fieldMap[$oldField['id']] = $newField['id'];
                                }
                            }
                        }
                    }
                }
            }
        }

        // Migrate old `fieldId` to new `fieldId`.
        $payments = (new Query())->from(Table::FORMIE_PAYMENTS)->all();

        foreach ($payments as $payment) {
            $oldFieldId = $payment['fieldId'];
            $newFieldId = $fieldMap[$oldFieldId] ?? null;

            // Update the row even if null
            $this->update(Table::FORMIE_PAYMENTS, ['fieldId' => $newFieldId], ['id' => $payment['id']]);
        }

        // Migrate old `fieldId` to new `fieldId`.
        $subscriptions = (new Query())->from(Table::FORMIE_SUBSCRIPTIONS)->all();

        foreach ($subscriptions as $subscription) {
            $oldFieldId = $subscription['fieldId'];
            $newFieldId = $fieldMap[$oldFieldId] ?? null;

            // Update the row even if null
            $this->update(Table::FORMIE_SUBSCRIPTIONS, ['fieldId' => $newFieldId], ['id' => $subscription['id']]);
        }

        $this->addForeignKey(null, Table::FORMIE_PAYMENTS, ['fieldId'], Table::FORMIE_FIELDS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_SUBSCRIPTIONS, ['fieldId'], Table::FORMIE_FIELDS, ['id'], 'RESTRICT', null);
    }

    private function _getAddressConfig(array $settings): array
    {

        $addressConfig = [
            [
                'fields' => [
                    [
                        'type' => subfields\Address1::class,
                        'label' => trim($settings['address1Label'] ?? '') ?: Craft::t('formie', 'Address 1'),
                        'handle' => 'address1',
                        'enabled' => $settings['address1Enabled'] ?? true,
                        'required' => $settings['address1Required'] ?? false,
                        'errorMessage' => $settings['address1ErrorMessage'] ?? null,
                        'placeholder' => $settings['address1Placeholder'] ?? null,
                        'defaultValue' => $settings['address1DefaultValue'] ?? null,
                        'prePopulate' => $settings['address1PrePopulate'] ?? null,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'address-line1',
                            ],
                            [
                                'label' => 'data-address1',
                                'value' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'fields' => [
                    [
                        'type' => subfields\Address2::class,
                        'label' => trim($settings['address2Label'] ?? '') ?: Craft::t('formie', 'Address 2'),
                        'handle' => 'address2',
                        'enabled' => $settings['address2Enabled'] ?? false,
                        'required' => $settings['address2Required'] ?? false,
                        'errorMessage' => $settings['address2ErrorMessage'] ?? null,
                        'placeholder' => $settings['address2Placeholder'] ?? null,
                        'defaultValue' => $settings['address2DefaultValue'] ?? null,
                        'prePopulate' => $settings['address2PrePopulate'] ?? null,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'address-line2',
                            ],
                            [
                                'label' => 'data-address2',
                                'value' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'fields' => [
                    [
                        'type' => subfields\Address3::class,
                        'label' => trim($settings['address3Label'] ?? '') ?: Craft::t('formie', 'Address 3'),
                        'handle' => 'address3',
                        'enabled' => $settings['address3Enabled'] ?? false,
                        'required' => $settings['address3Required'] ?? false,
                        'errorMessage' => $settings['address3ErrorMessage'] ?? null,
                        'placeholder' => $settings['address3Placeholder'] ?? null,
                        'defaultValue' => $settings['address3DefaultValue'] ?? null,
                        'prePopulate' => $settings['address3PrePopulate'] ?? null,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'address-line3',
                            ],
                            [
                                'label' => 'data-address3',
                                'value' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'fields' => [
                    [
                        'type' => subfields\AddressCity::class,
                        'label' =>  trim($settings['cityLabel'] ?? '') ?: Craft::t('formie', 'City'),
                        'handle' => 'city',
                        'enabled' => $settings['cityEnabled'] ?? true,
                        'required' => $settings['cityRequired'] ?? false,
                        'errorMessage' => $settings['cityErrorMessage'] ?? null,
                        'placeholder' => $settings['cityPlaceholder'] ?? null,
                        'defaultValue' => $settings['cityDefaultValue'] ?? null,
                        'prePopulate' => $settings['cityPrePopulate'] ?? null,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'address-level2',
                            ],
                            [
                                'label' => 'data-city',
                                'value' => true,
                            ],
                        ],
                    ],
                    [
                        'type' => subfields\AddressZip::class,
                        'label' => trim($settings['zipLabel'] ?? '') ?: Craft::t('formie', 'ZIP / Postal Code'),
                        'handle' => 'zip',
                        'enabled' => $settings['zipEnabled'] ?? true,
                        'required' => $settings['zipRequired'] ?? false,
                        'errorMessage' => $settings['zipErrorMessage'] ?? null,
                        'placeholder' => $settings['zipPlaceholder'] ?? null,
                        'defaultValue' => $settings['zipDefaultValue'] ?? null,
                        'prePopulate' => $settings['zipPrePopulate'] ?? null,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'postal-code',
                            ],
                            [
                                'label' => 'data-zip',
                                'value' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'fields' => [
                    [
                        'type' => subfields\AddressState::class,
                        'label' => trim($settings['stateLabel'] ?? '') ?: Craft::t('formie', 'State / Province'),
                        'handle' => 'state',
                        'enabled' => $settings['stateEnabled'] ?? true,
                        'required' => $settings['stateRequired'] ?? false,
                        'errorMessage' => $settings['stateErrorMessage'] ?? null,
                        'placeholder' => $settings['statePlaceholder'] ?? null,
                        'defaultValue' => $settings['stateDefaultValue'] ?? null,
                        'prePopulate' => $settings['statePrePopulate'] ?? null,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'address-level1',
                            ],
                            [
                                'label' => 'data-state',
                                'value' => true,
                            ],
                        ],
                    ],
                    [
                        'type' => subfields\AddressCountry::class,
                        'label' => trim($settings['countryLabel'] ?? '') ?: Craft::t('formie', 'Country'),
                        'handle' => 'country',
                        'enabled' => $settings['countryEnabled'] ?? true,
                        'required' => $settings['countryRequired'] ?? false,
                        'errorMessage' => $settings['countryErrorMessage'] ?? null,
                        'placeholder' => $settings['countryPlaceholder'] ?? null,
                        'defaultValue' => $settings['countryDefaultValue'] ?? null,
                        'prePopulate' => $settings['countryPrePopulate'] ?? null,
                        'optionLabel' => $settings['countryOptionLabel'] ?? 'full',
                        'optionValue' => $settings['countryOptionValue'] ?? 'short',
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'country',
                            ],
                            [
                                'label' => 'data-country',
                                'value' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Fire a 'ModifyMigrationAddressConfig' event
        $event = new ModifyMigrationAddressConfigEvent([
            'addressConfig' => $addressConfig,
            'settings' => $settings,
        ]);
        $this->trigger(self::EVENT_MODIFY_MIGRATION_ADDRESS_CONFIG, $event);

        return $event->addressConfig;
    }

    private function _getDateCalendarConfig(array $settings): array
    {
        return [
            [
                'fields' => [
                    [
                        'type' => subfields\DateDate::class,
                        'label' => trim($settings['dateLabel'] ?? '') ?: Craft::t('formie', 'Date'),
                        'handle' => 'date',
                        'enabled' => $settings['includeDate'] ?? true,
                        'required' => $settings['required'] ?? false,
                        'placeholder' => $settings['placeholder'] ?? null,
                        'errorMessage' => $settings['errorMessage'] ?? null,
                        'defaultValue' => $settings['defaultValue'] ?? null,
                        'labelPosition' => HiddenPosition::class,
                        'inputAttributes' => [
                            [
                                'label' => 'type',
                                'value' => 'date',
                            ],
                            [
                                'label' => 'autocomplete',
                                'value' => 'off',
                            ],
                        ],
                    ],
                    [
                        'type' => subfields\DateTime::class,
                        'label' => trim($settings['timeLabel'] ?? '') ?: Craft::t('formie', 'Time'),
                        'handle' => 'time',
                        'enabled' => $settings['includeTime'] ?? true,
                        'required' => $settings['required'] ?? false,
                        'placeholder' => $settings['placeholder'] ?? null,
                        'errorMessage' => $settings['errorMessage'] ?? null,
                        'defaultValue' => $settings['defaultValue'] ?? null,
                        'labelPosition' => HiddenPosition::class,
                        'inputAttributes' => [
                            [
                                'label' => 'type',
                                'value' => 'time',
                            ],
                            [
                                'label' => 'autocomplete',
                                'value' => 'off',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function _getDateDropdownsConfig(array $settings): array
    {
        return [
            [
                'fields' => [
                    [
                        'type' => subfields\DateYearDropdown::class,
                        'label' => trim($settings['yearLabel'] ?? '') ?: Craft::t('formie', 'Year'),
                        'handle' => 'year',
                        'enabled' => $settings['includeDate'] ?? true,
                        'placeholder' => $settings['yearPlaceholder'] ?? null,
                        'options' => [],
                    ],
                    [
                        'type' => subfields\DateMonthDropdown::class,
                        'label' => trim($settings['monthLabel'] ?? '') ?: Craft::t('formie', 'Month'),
                        'handle' => 'month',
                        'enabled' => $settings['includeDate'] ?? true,
                        'placeholder' => $settings['monthPlaceholder'] ?? null,
                        'options' => $this->_getMonthOptions(),
                    ],
                    [
                        'type' => subfields\DateDayDropdown::class,
                        'label' => Craft::t('formie', 'Day'),
                        'handle' => 'day',
                        'enabled' => $settings['includeDate'] ?? true,
                        'placeholder' => $settings['dayPlaceholder'] ?? null,
                        'options' => $this->_generateOptions(1, 31),
                    ],
                    [
                        'type' => subfields\DateHourDropdown::class,
                        'label' => trim($settings['hourLabel'] ?? '') ?: Craft::t('formie', 'Hour'),
                        'handle' => 'hour',
                        'enabled' => $settings['includeTime'] ?? true,
                        'placeholder' => $settings['hourPlaceholder'] ?? null,
                        'options' => $this->_generateOptions(0, 23),
                    ],
                    [
                        'type' => subfields\DateMinuteDropdown::class,
                        'label' => trim($settings['minueLabel'] ?? '') ?: Craft::t('formie', 'Minute'),
                        'handle' => 'minute',
                        'enabled' => $settings['includeTime'] ?? true,
                        'placeholder' => $settings['minutePlaceholder'] ?? null,
                        'options' => $this->_generateOptions(0, 59),
                    ],
                    [
                        'type' => subfields\DateSecondDropdown::class,
                        'label' => trim($settings['secondLabel'] ?? '') ?: Craft::t('formie', 'Second'),
                        'handle' => 'second',
                        'enabled' => false,
                        'placeholder' => $settings['secondPlaceholder'] ?? null,
                        'options' => $this->_generateOptions(0, 59),
                    ],
                    [
                        'type' => subfields\DateAmPmDropdown::class,
                        'label' => trim($settings['ampmLabel'] ?? '') ?: Craft::t('formie', 'AM/PM'),
                        'handle' => 'ampm',
                        'enabled' => false,
                        'placeholder' => $settings['ampmPlaceholder'] ?? null,
                        'options' => [
                            ['value' => 'AM', 'label' => Craft::t('formie', 'AM')],
                            ['value' => 'PM', 'label' => Craft::t('formie', 'PM')],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function _getDateInputsConfig(array $settings): array
    {
        return [
            [
                'fields' => [
                    [
                        'type' => subfields\DateYearNumber::class,
                        'label' => trim($settings['yearLabel'] ?? '') ?: Craft::t('formie', 'Year'),
                        'handle' => 'year',
                        'enabled' => $settings['includeDate'] ?? true,
                        'placeholder' => $settings['yearPlaceholder'] ?? null,
                        'limit' => true,
                        'min' => 1924,
                        'max' => 2124,
                    ],
                    [
                        'type' => subfields\DateMonthNumber::class,
                        'label' => trim($settings['monthLabel'] ?? '') ?: Craft::t('formie', 'Month'),
                        'handle' => 'month',
                        'enabled' => $settings['includeDate'] ?? true,
                        'placeholder' => $settings['monthPlaceholder'] ?? null,
                        'limit' => true,
                        'min' => 1,
                        'max' => 12,
                    ],
                    [
                        'type' => subfields\DateDayNumber::class,
                        'label' => Craft::t('formie', 'Day'),
                        'handle' => 'day',
                        'enabled' => $settings['includeDate'] ?? true,
                        'placeholder' => $settings['dayPlaceholder'] ?? null,
                        'limit' => true,
                        'min' => 1,
                        'max' => 31,
                    ],
                    [
                        'type' => subfields\DateHourNumber::class,
                        'label' => trim($settings['hourLabel'] ?? '') ?: Craft::t('formie', 'Hour'),
                        'handle' => 'hour',
                        'enabled' => $settings['includeTime'] ?? true,
                        'placeholder' => $settings['hourPlaceholder'] ?? null,
                        'limit' => true,
                        'min' => 0,
                        'max' => 23,
                    ],
                    [
                        'type' => subfields\DateMinuteNumber::class,
                        'label' => trim($settings['minueLabel'] ?? '') ?: Craft::t('formie', 'Minute'),
                        'handle' => 'minute',
                        'enabled' => $settings['includeTime'] ?? true,
                        'placeholder' => $settings['minutePlaceholder'] ?? null,
                        'limit' => true,
                        'min' => 0,
                        'max' => 59,
                    ],
                    [
                        'type' => subfields\DateSecondNumber::class,
                        'label' => trim($settings['secondLabel'] ?? '') ?: Craft::t('formie', 'Second'),
                        'handle' => 'second',
                        'enabled' => false,
                        'placeholder' => $settings['secondPlaceholder'] ?? null,
                        'limit' => true,
                        'min' => 0,
                        'max' => 59,
                    ],
                    [
                        'type' => subfields\DateAmPmDropdown::class,
                        'label' => trim($settings['ampmLabel'] ?? '') ?: Craft::t('formie', 'AM/PM'),
                        'handle' => 'ampm',
                        'enabled' => false,
                        'placeholder' => $settings['ampmPlaceholder'] ?? null,
                        'options' => [
                            ['value' => 'AM', 'label' => Craft::t('formie', 'AM')],
                            ['value' => 'PM', 'label' => Craft::t('formie', 'PM')],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function _getNameConfig(array $settings): array
    {
        return [
            [
                'fields' => [
                    [
                        'type' => subfields\NamePrefix::class,
                        'label' => trim($settings['prefixLabel'] ?? '') ?: Craft::t('formie', 'Prefix'),
                        'handle' => 'prefix',
                        'enabled' => $settings['prefixEnabled'] ?? false,
                        'required' => $settings['prefixRequired'] ?? false,
                        'errorMessage' => $settings['prefixErrorMessage'] ?? null,
                        'placeholder' => $settings['prefixPlaceholder'] ?? null,
                        'defaultValue' => $settings['prefixDefaultValue'] ?? null,
                        'prePopulate' => $settings['prefixPrePopulate'] ?? null,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'honorific-prefix',
                            ],
                        ],
                    ],
                    [
                        'type' => subfields\NameFirst::class,
                        'label' => trim($settings['firstNameLabel'] ?? '') ?: Craft::t('formie', 'First Name'),
                        'handle' => 'firstName',
                        'enabled' => $settings['firstNameEnabled'] ?? true,
                        'required' => $settings['firstNameRequired'] ?? false,
                        'errorMessage' => $settings['firstNameErrorMessage'] ?? null,
                        'placeholder' => $settings['firstNamePlaceholder'] ?? null,
                        'defaultValue' => $settings['firstNameDefaultValue'] ?? null,
                        'prePopulate' => $settings['firstNamePrePopulate'] ?? null,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'given-name',
                            ],
                        ],
                    ],
                    [
                        'type' => subfields\NameMiddle::class,
                        'label' => trim($settings['middleNameLabel'] ?? '') ?: Craft::t('formie', 'Middle Name'),
                        'handle' => 'middleName',
                        'enabled' => $settings['middleNameEnabled'] ?? false,
                        'required' => $settings['middleNameRequired'] ?? false,
                        'errorMessage' => $settings['middleNameErrorMessage'] ?? null,
                        'placeholder' => $settings['middleNamePlaceholder'] ?? null,
                        'defaultValue' => $settings['middleNameDefaultValue'] ?? null,
                        'prePopulate' => $settings['middleNamePrePopulate'] ?? null,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'additional-name',
                            ],
                        ],
                    ],
                    [
                        'type' => subfields\NameLast::class,
                        'label' => trim($settings['lastNameLabel'] ?? '') ?: Craft::t('formie', 'Last Name'),
                        'handle' => 'lastName',
                        'enabled' => $settings['lastNameEnabled'] ?? true,
                        'required' => $settings['lastNameRequired'] ?? false,
                        'errorMessage' => $settings['lastNameErrorMessage'] ?? null,
                        'placeholder' => $settings['lastNamePlaceholder'] ?? null,
                        'defaultValue' => $settings['lastNameDefaultValue'] ?? null,
                        'prePopulate' => $settings['lastNamePrePopulate'] ?? null,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'family-name',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function _generateOptions(int $start, int $end, ?string $placeholder = null): array
    {
        $options = [['value' => '', 'label' => $placeholder, 'disabled' => true]];

        for ($i = $start; $i <= $end; $i++) {
            $options[] = ['label' => $i, 'value' => $i];
        }

        return $options;
    }

    private function _getMonthOptions(?string $placeholder = null): array
    {
        $options = [['value' => '', 'label' => $placeholder, 'disabled' => true]];

        foreach (Craft::$app->getLocale()->getMonthNames() as $index => $monthName) {
            $options[] = ['value' => $index + 1, 'label' => $monthName];
        }

        return $options;
    }

    private function _cleanupOldTables(): void
    {
        // Now, remove all Formie fields from the main fields table, to prevent any more fatal errors
        $fields = (new Query())->from('{{%fields}}')->all();

        foreach ($fields as $field) {
            if (str_contains($field['context'], 'formie:') || str_contains($field['context'], 'formieField:')) {
                $this->delete('{{%fields}}', ['id' => $field['id']]);
            }
        }

        // Finish cleaning up old tables
        $tables = [
            'formie_nested',
            'formie_nestedfieldrows',
            'formie_newlayout',
            'formie_newnestedlayout',
            'formie_pagesettings',
            'formie_rows',
            'formie_syncfields',
            'formie_syncs',
        ];

        foreach ($tables as $table) {
            $this->dropTableIfExists('{{%' . $table . '}}');
        }

        $fieldlayouts = (new Query())->from('{{%fieldlayouts}}')->all();
        $formTemplateLayoutIds = array_values(array_filter((new Query())->select('fieldLayoutId')->from(Table::FORMIE_FORM_TEMPLATES)->column()));

        foreach ($fieldlayouts as $fieldlayout) {
            // Delete all Group/Repeater layouts
            if ($fieldlayout['type'] === 'verbb\formie\fields\formfields\Group') {
                $this->delete('{{%fieldlayouts}}', ['id' => $fieldlayout['id']]);
            }

            if ($fieldlayout['type'] === 'verbb\formie\fields\formfields\Repeater') {
                $this->delete('{{%fieldlayouts}}', ['id' => $fieldlayout['id']]);
            }

            // Delete any form field layout that doesn't belong to a Form Template
            if ($fieldlayout['type'] === 'verbb\formie\elements\Form') {
                if (!in_array($fieldlayout['id'], $formTemplateLayoutIds)) {
                    $this->delete('{{%fieldlayouts}}', ['id' => $fieldlayout['id']]);
                }
            }
        }

        // Remove all old content tables
        foreach (Craft::$app->getDb()->schema->getTableNames() as $tableName) {
            if (str_starts_with($tableName, 'fmc_') || str_starts_with($tableName, 'fmcd_')) {
                MigrationHelper::dropAllForeignKeysOnTable($tableName, $this);

                $this->dropTableIfExists($tableName);
            }
        }

        if ($this->db->columnExists(Table::FORMIE_FORMS, 'fieldLayoutId')) {
            $this->dropColumn(Table::FORMIE_FORMS, 'fieldLayoutId');
        }

        if ($this->db->columnExists(Table::FORMIE_FORMS, 'fieldContentTable')) {
            $this->dropColumn(Table::FORMIE_FORMS, 'fieldContentTable');
        }

        if ($this->db->columnExists(Table::FORMIE_SUBMISSIONS, 'title')) {
            $this->dropColumn(Table::FORMIE_SUBMISSIONS, 'title');
        }

        $forms = (new Query())->select('id')->from(Table::FORMIE_FORMS)->all();

        foreach ($forms as $form) {
            // Clear the original form's title so that it can be removed from the content table. Done at the very end
            // during cleanup to prevent loss of migrated content - https://github.com/verbb/formie/issues/2384
            $this->update('{{%content}}', ['title' => null], ['elementId' => $form['id']]);
        }

    }
}
