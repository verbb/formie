<?php
namespace verbb\formie\migrations;

use verbb\formie\conditions\ConditionMigration;
use verbb\formie\elements\Form;
use verbb\formie\helpers\FieldTraversal;
use verbb\formie\helpers\Table;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m261103_010000_condition_field_references extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // References belong to one form, so a definition shared between forms has to keep its handles.
        $sharedDefinitionIds = (new Query())
            ->select(['fieldId'])
            ->from(Table::FORMIE_FORM_FIELDS)
            ->groupBy(['fieldId'])
            ->having('COUNT(*) > 1')
            ->column($this->db);

        foreach (Form::find()->forProjectConfig()->site('*')->unique()->status(null)->trashed(null)->each() as $form) {
            $this->_migrateRows($form, Table::FORMIE_FORMS, 'settings', ['id' => $form->id]);
            $this->_migrateRows($form, Table::FORMIE_FORM_SITE_OVERRIDES, 'overrides', ['formId' => $form->id]);

            foreach ($form->getPages() as $page) {
                if ($page->id) {
                    $this->_migrateRows($form, Table::FORMIE_FIELD_LAYOUT_PAGES, 'settings', ['id' => $page->id]);
                }
            }

            foreach (FieldTraversal::recursively($form->getFields()) as $field) {
                if ($field->definitionId && !in_array($field->definitionId, $sharedDefinitionIds)) {
                    $this->_migrateRows($form, Table::FORMIE_FIELDS, 'settings', ['id' => $field->definitionId]);
                    $this->_migrateRows($form, Table::FORMIE_FIELD_SITE_OVERRIDES, 'overrides', ['fieldId' => $field->definitionId]);
                }
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261103_010000_condition_field_references cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _migrateRows(Form $form, string $table, string $column, array $where): void
    {
        foreach ((new Query())->select(['id', $column])->from($table)->where($where)->each() as $row) {
            $data = Json::decodeIfJson($row[$column] ?? null);

            if (!is_array($data)) {
                continue;
            }

            $migrated = ConditionMigration::fieldReferences($data, $form);

            if ($migrated !== $data) {
                $this->update($table, [$column => Json::encode($migrated)], ['id' => $row['id']], [], false);
            }
        }
    }
}
