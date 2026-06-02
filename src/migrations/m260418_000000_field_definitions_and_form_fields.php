<?php
namespace verbb\formie\migrations;

use verbb\formie\compatibility\fields\FieldConfigNormalizer;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m260418_000000_field_definitions_and_form_fields extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $fieldsTableExists = $this->db->tableExists(Table::FORMIE_FIELDS);
        $formFieldsTableExists = $this->db->tableExists(Table::FORMIE_FORM_FIELDS);

        if ($fieldsTableExists && !$formFieldsTableExists && $this->db->columnExists(Table::FORMIE_FIELDS, 'layoutId')) {
            $this->renameTable(Table::FORMIE_FIELDS, Table::FORMIE_FORM_FIELDS);
            $fieldsTableExists = false;
            $formFieldsTableExists = true;
        }

        if (!$fieldsTableExists) {
            $this->createTable(Table::FORMIE_FIELDS, [
                'id' => $this->primaryKey(),
                'type' => $this->string()->notNull(),
                'label' => $this->text()->notNull(),
                'handle' => $this->string(64)->notNull(),
                'settings' => $this->mediumText(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$formFieldsTableExists) {
            $this->createTable(Table::FORMIE_FORM_FIELDS, [
                'id' => $this->primaryKey(),
                'fieldId' => $this->integer()->notNull(),
                'layoutId' => $this->integer()->notNull(),
                'pageId' => $this->integer()->notNull(),
                'rowId' => $this->integer()->notNull(),
                'sortOrder' => $this->smallInteger()->unsigned(),
                'settings' => $this->mediumText(),
                'reference' => $this->string(36),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->columnExists(Table::FORMIE_FORM_FIELDS, 'fieldId')) {
            $this->addColumn(Table::FORMIE_FORM_FIELDS, 'fieldId', $this->integer()->after('id'));
        }

        $this->tryCreateIndex('formie_fields_handle_idx', Table::FORMIE_FIELDS, 'handle', false);
        $this->tryCreateIndex('formie_form_fields_fieldId_idx', Table::FORMIE_FORM_FIELDS, 'fieldId', false);
        $this->tryCreateIndex('formie_form_fields_reference_unq', Table::FORMIE_FORM_FIELDS, 'reference', true);

        if ($this->db->columnExists(Table::FORMIE_FORM_FIELDS, 'layoutId')) {
            $this->tryCreateIndex('formie_form_fields_layoutId_idx', Table::FORMIE_FORM_FIELDS, 'layoutId', false);
        }

        if ($this->db->columnExists(Table::FORMIE_FORM_FIELDS, 'pageId')) {
            $this->tryCreateIndex('formie_form_fields_pageId_idx', Table::FORMIE_FORM_FIELDS, 'pageId', false);
        }

        if ($this->db->columnExists(Table::FORMIE_FORM_FIELDS, 'rowId')) {
            $this->tryCreateIndex('formie_form_fields_rowId_idx', Table::FORMIE_FORM_FIELDS, 'rowId', false);
        }

        $this->dropAllForeignKeysToTableIfPossible(Table::FORMIE_FIELDS, ['fieldId']);
        $this->dropAllForeignKeysToTableIfPossible(Table::FORMIE_FORM_FIELDS, ['fieldId']);

        $this->tryAddForeignKey('formie_form_fields_fieldId_fk', Table::FORMIE_FORM_FIELDS, ['fieldId'], Table::FORMIE_FIELDS, ['id'], 'CASCADE', null);
        $this->tryAddForeignKey('formie_payments_fieldId_fk', Table::FORMIE_PAYMENTS, ['fieldId'], Table::FORMIE_FORM_FIELDS, ['id'], 'CASCADE', null);
        $this->tryAddForeignKey('formie_subscriptions_fieldId_fk', Table::FORMIE_SUBSCRIPTIONS, ['fieldId'], Table::FORMIE_FORM_FIELDS, ['id'], 'RESTRICT', null);

        if ($this->db->columnExists(Table::FORMIE_FORM_FIELDS, 'syncId')) {
            $legacyRows = (new Query())
                ->from(Table::FORMIE_FORM_FIELDS)
                ->orderBy(['id' => SORT_ASC])
                ->all();

            if ($legacyRows) {
                $rowsById = [];

                foreach ($legacyRows as $row) {
                    $rowsById[(int)$row['id']] = $row;
                }

                $groups = [];

                foreach ($legacyRows as $row) {
                    $groupId = $this->resolveLegacyGroupId($row, $rowsById);
                    $groups[$groupId][] = $row;
                }

                foreach ($groups as $groupId => $groupRows) {
                    $representative = $rowsById[$groupId] ?? $groupRows[0];
                    $definitionSettings = Json::decodeIfJson($representative['settings']) ?: [];

                    if (is_array($definitionSettings)) {
                        unset($definitionSettings['required']);
                        FieldConfigNormalizer::normalize($definitionSettings, $representative['type']);
                    } else {
                        $definitionSettings = [];
                    }

                    $existingDefinition = (new Query())
                        ->from(Table::FORMIE_FIELDS)
                        ->where(['id' => (int)$groupId])
                        ->exists();

                    if (!$existingDefinition) {
                        Craft::$app->getDb()->createCommand()
                            ->insert(Table::FORMIE_FIELDS, [
                                'id' => (int)$groupId,
                                'type' => $representative['type'],
                                'label' => $representative['label'],
                                'handle' => $representative['handle'],
                                'settings' => Json::encode($definitionSettings),
                                'dateCreated' => $representative['dateCreated'],
                                'dateUpdated' => $representative['dateUpdated'],
                                'uid' => $representative['uid'],
                            ])
                            ->execute();
                    }

                    foreach ($groupRows as $groupRow) {
                        $rowSettings = Json::decodeIfJson($groupRow['settings']) ?: [];
                        $required = is_array($rowSettings) ? (bool)($rowSettings['required'] ?? false) : false;

                        Craft::$app->getDb()->createCommand()
                            ->update(Table::FORMIE_FORM_FIELDS, [
                                'fieldId' => (int)$groupId,
                                'settings' => Json::encode(['required' => $required]),
                            ], ['id' => (int)$groupRow['id']])
                            ->execute();
                    }
                }
            }
        }

        $this->dropLegacyFormFieldColumns();

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260418_000000_field_definitions_and_form_fields cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private function resolveLegacyGroupId(array $row, array $rowsById): int
    {
        $rowId = (int)($row['id'] ?? 0);
        $syncId = (int)($row['syncId'] ?? 0);

        if (!$syncId || !isset($rowsById[$syncId])) {
            return $rowId;
        }

        $visited = [];
        $currentId = $rowId;

        while (true) {
            if (isset($visited[$currentId])) {
                return $currentId;
            }

            $visited[$currentId] = true;
            $current = $rowsById[$currentId] ?? null;

            if (!$current) {
                return $rowId;
            }

            $currentSyncId = (int)($current['syncId'] ?? 0);

            if (!$currentSyncId || !isset($rowsById[$currentSyncId]) || $currentSyncId === $currentId) {
                return $currentId;
            }

            $currentId = $currentSyncId;
        }
    }

    private function tryCreateIndex(string $name, string $table, string|array $columns, bool $unique): void
    {
        try {
            $this->createIndex($name, $table, $columns, $unique);
        } catch (\Throwable) {
        }
    }

    private function tryAddForeignKey(string $name, string $table, array $columns, string $refTable, array $refColumns, ?string $delete = null, ?string $update = null): void
    {
        try {
            $this->addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete, $update);
        } catch (\Throwable) {
        }
    }

    private function dropAllForeignKeysToTableIfPossible(string $table, array $columns): void
    {
        $schema = Craft::$app->getDb()->getSchema()->getTableSchema($table);

        if (!$schema) {
            return;
        }

        foreach ($schema->foreignKeys as $name => $foreignKey) {
            $fkColumns = (array)array_keys($foreignKey);
            $intersect = array_intersect($fkColumns, $columns);

            if ($intersect) {
                try {
                    $this->dropForeignKey($name, $table);
                } catch (\Throwable) {
                }
            }
        }
    }

    private function dropLegacyFormFieldColumns(): void
    {
        if (!$this->db->tableExists(Table::FORMIE_FORM_FIELDS)) {
            return;
        }

        if ($this->db->columnExists(Table::FORMIE_FORM_FIELDS, 'syncId')) {
            $this->dropAllForeignKeysToTableIfPossible(Table::FORMIE_FORM_FIELDS, ['syncId']);
        }

        foreach (['syncId', 'label', 'handle', 'type'] as $column) {
            if (!$this->db->columnExists(Table::FORMIE_FORM_FIELDS, $column)) {
                continue;
            }

            try {
                $this->dropColumn(Table::FORMIE_FORM_FIELDS, $column);
            } catch (\Throwable) {
            }
        }
    }
}
