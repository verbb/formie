<?php
namespace verbb\formie\migrations;

use verbb\formie\elements\Form;
use verbb\formie\helpers\FieldTraversal;
use verbb\formie\helpers\Table;
use verbb\formie\references\ReferenceMigration;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m261115_020000_legacy_setting_tokens extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $sharedDefinitionIds = (new Query())
            ->select(['fieldId'])
            ->from(Table::FORMIE_FORM_FIELDS)
            ->groupBy(['fieldId'])
            ->having('COUNT(*) > 1')
            ->column($this->db);

        foreach (Form::find()->forProjectConfig()->site('*')->unique()->status(null)->trashed(null)->each() as $form) {
            $fields = $form->getFields();

            $this->_migrateRow(Table::FORMIE_FORMS, 'settings', ['id' => $form->id], fn(array $settings) => ReferenceMigration::legacyFormSettings($settings, $fields));
            $this->_migrateRow(Table::FORMIE_FORM_SITE_OVERRIDES, 'overrides', ['formId' => $form->id], fn(array $settings) => ReferenceMigration::legacyFormSettings($settings, $fields));

            foreach (FieldTraversal::recursively($fields) as $field) {
                if ($field->definitionId) {
                    // References belong to one form, so a definition shared between forms stores the readable path instead.
                    $useReferences = !in_array($field->definitionId, $sharedDefinitionIds);
                    $update = fn(array $settings) => ReferenceMigration::legacyFieldSettings($settings, $field, $fields, $useReferences);

                    $this->_migrateRow(Table::FORMIE_FIELDS, 'settings', ['id' => $field->definitionId], $update);
                    $this->_migrateRow(Table::FORMIE_FIELD_SITE_OVERRIDES, 'overrides', ['fieldId' => $field->definitionId], $update);
                }
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261115_020000_legacy_setting_tokens cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _migrateRow(string $table, string $column, array $where, callable $update): void
    {
        foreach ((new Query())->select(['id', $column])->from($table)->where($where)->each() as $row) {
            $data = Json::decodeIfJson($row[$column] ?? null);

            if (!is_array($data)) {
                continue;
            }

            $migrated = $update($data);

            if ($migrated !== $data) {
                $this->update($table, [$column => Json::encode($migrated)], ['id' => $row['id']], [], false);
            }
        }
    }
}
