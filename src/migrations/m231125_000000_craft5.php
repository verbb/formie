<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\fields\Group;
use verbb\formie\fields\Repeater;
use verbb\formie\helpers\Table;
use verbb\formie\models\FieldLayout;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\MigrationHelper;
use craft\migrations\BaseContentRefactorMigration;

use Throwable;

class m231125_000000_craft5 extends BaseContentRefactorMigration
{
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
                'settings' => $this->text(),
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

            $formLayout = new FieldLayout($layoutConfig);

            if (!Formie::$plugin->getFields()->saveLayout($formLayout)) {
                echo '    > ' . $form['handle'] . ': Unable to save field layout - ' . Json::encode($formLayout->getErrors()) . PHP_EOL;
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
            $templateId = $form['templateId'];

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

                Db::update(Table::FORMIE_SUBMISSIONS, ['content' => Db::prepareForJsonColumn($content, $this->db)], ['id' => $submission['id']]);

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
                    $submissionContent = (new Query())->select('content')->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $submissionId])->scalar();
                    $submissionContent = Json::decode($submissionContent);

                    $submissionContent = array_merge($submissionContent, $fieldContent);

                    Db::update(Table::FORMIE_SUBMISSIONS, ['content' => Db::prepareForJsonColumn($submissionContent, $this->db)], ['id' => $submissionId]);

                    echo '    > Updated Submission ' . $submissionId . ' content for nested content.' . PHP_EOL;
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
                $this->delete('{{%content}}', ['elementId' => $elementId]);
            
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

                        Db::update(Table::FORMIE_SUBMISSIONS, ['content' => Db::prepareForJsonColumn($submissionContent, $this->db)], ['id' => $submissionId]);

                        echo '    > Updated Submission ' . $submissionId . ' content for related content.' . PHP_EOL;
                    }
                }
            }
        }
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
    }
}
