<?php
namespace verbb\formie\migrations;

use verbb\formie\conditions\ConditionMigration;
use verbb\formie\elements\Form;
use verbb\formie\helpers\FieldTraversal;
use verbb\formie\helpers\Table;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m261115_010000_condition_legacy_field_tokens extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // References belong to one form, so a definition shared between forms stores the readable path instead.
        $sharedDefinitionIds = (new Query())
            ->select(['fieldId'])
            ->from(Table::FORMIE_FORM_FIELDS)
            ->groupBy(['fieldId'])
            ->having('COUNT(*) > 1')
            ->column($this->db);

        foreach (Form::find()->forProjectConfig()->site('*')->unique()->status(null)->trashed(null)->each() as $form) {
            $this->_migrateRows($form, Table::FORMIE_FORMS, ['settings'], ['id' => $form->id]);
            $this->_migrateRows($form, Table::FORMIE_FORM_SITE_OVERRIDES, ['overrides'], ['formId' => $form->id]);
            $this->_migrateRows($form, Table::FORMIE_NOTIFICATIONS, ['conditions', 'toConditions'], ['formId' => $form->id]);

            foreach ($form->getPages() as $page) {
                if ($page->id) {
                    $this->_migrateRows($form, Table::FORMIE_FIELD_LAYOUT_PAGES, ['settings'], ['id' => $page->id]);
                }
            }

            foreach (FieldTraversal::recursively($form->getFields()) as $field) {
                if ($field->definitionId) {
                    $useReferences = !in_array($field->definitionId, $sharedDefinitionIds);

                    $this->_migrateRows($form, Table::FORMIE_FIELDS, ['settings'], ['id' => $field->definitionId], $useReferences);
                    $this->_migrateRows($form, Table::FORMIE_FIELD_SITE_OVERRIDES, ['overrides'], ['fieldId' => $field->definitionId], $useReferences);
                }
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261115_010000_condition_legacy_field_tokens cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _migrateRows(Form $form, string $table, array $columns, array $where, bool $useReferences = true): void
    {
        foreach ((new Query())->select(['id', ...$columns])->from($table)->where($where)->each() as $row) {
            $update = [];

            foreach ($columns as $column) {
                $data = Json::decodeIfJson($row[$column] ?? null);

                if (!is_array($data)) {
                    continue;
                }

                $migrated = ConditionMigration::legacyFieldTokens($data, $form, $useReferences);

                if ($migrated !== $data) {
                    $update[$column] = Json::encode($migrated);
                }
            }

            if ($update) {
                $this->update($table, $update, ['id' => $row['id']], [], false);
            }
        }
    }
}
