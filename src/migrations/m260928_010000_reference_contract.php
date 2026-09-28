<?php
namespace verbb\formie\migrations;

use verbb\formie\elements\Form;
use verbb\formie\helpers\FieldTraversal;
use verbb\formie\helpers\Table;
use verbb\formie\models\StencilData;
use verbb\formie\references\ReferenceMigration;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\ProjectConfig;

class m260928_010000_reference_contract extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        foreach (Form::find()->status(null)->each() as $form) {
            $this->_migrateFormSettings($form);
            $this->_migrateFieldSettings($form);
            $this->_migratePageSettings($form);
            $this->_migrateNotifications($form);
        }

        $this->_migrateStencils();

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260928_010000_reference_contract cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _migrateFormSettings(Form $form): void
    {
        $this->_migrateJsonRows($form, Table::FORMIE_FORMS, 'settings', ['id' => $form->id]);
        $this->_migrateJsonRows($form, Table::FORMIE_FORM_SITE_OVERRIDES, 'overrides', ['formId' => $form->id]);
    }

    private function _migrateFieldSettings(Form $form): void
    {
        foreach (FieldTraversal::recursively($form->getFields()) as $field) {
            if (!$field->id) {
                continue;
            }

            $this->_migrateJsonRows($form, Table::FORMIE_FORM_FIELDS, 'settings', ['id' => $field->id]);

            if ($field->definitionId) {
                $this->_migrateJsonRows($form, Table::FORMIE_FIELDS, 'settings', ['id' => $field->definitionId]);
                $this->_migrateJsonRows($form, Table::FORMIE_FIELD_SITE_OVERRIDES, 'overrides', ['fieldId' => $field->definitionId]);
            }
        }
    }

    private function _migratePageSettings(Form $form): void
    {
        foreach ($form->getPages() as $page) {
            if ($page->id) {
                $this->_migrateJsonRows($form, Table::FORMIE_FIELD_LAYOUT_PAGES, 'settings', ['id' => $page->id]);
            }
        }
    }

    private function _migrateNotifications(Form $form): void
    {
        foreach ((new Query())->from(Table::FORMIE_NOTIFICATIONS)->where(['formId' => $form->id])->each() as $row) {
            $changes = [];
            foreach ($row as $column => $value) {
                if (!is_string($value)) {
                    continue;
                }

                $migrated = ReferenceMigration::canonicalFieldTokens($form, $value);
                if ($migrated !== $value) {
                    $changes[$column] = $migrated;
                }
            }

            if ($changes !== []) {
                $this->update(Table::FORMIE_NOTIFICATIONS, $changes, ['id' => $row['id']], [], false);
            }
        }
    }

    private function _migrateStencils(): void
    {
        if ($this->db->tableExists(Table::FORMIE_STENCILS)) {
            foreach ((new Query())->select(['id', 'data'])->from(Table::FORMIE_STENCILS)->each() as $row) {
                $data = Json::decodeIfJson($row['data'] ?? null);
                if (!is_array($data)) {
                    continue;
                }

                $migrated = $this->_migrateStencilData($data);
                if ($migrated !== $data) {
                    $this->update(Table::FORMIE_STENCILS, ['data' => Json::encode($migrated)], ['id' => $row['id']], [], false);
                }
            }
        }

        $projectConfig = Craft::$app->getProjectConfig();
        foreach ((array)$projectConfig->get('formie.stencils', true) as $uid => $config) {
            $data = ProjectConfig::unpackAssociativeArrays((array)($config['data'] ?? []));
            $migrated = $this->_migrateStencilData($data);
            if ($migrated !== $data) {
                $projectConfig->set(
                    'formie.stencils.' . $uid . '.data',
                    ProjectConfig::packAssociativeArrays($migrated),
                    'Migrate Formie field reference tokens',
                );
            }
        }
    }

    private function _migrateStencilData(array $data): array
    {
        if (!str_contains(Json::encode($data), '{field:')) {
            return $data;
        }

        $stencilData = new StencilData($data);
        $form = new Form();
        $stencilData->populateToForm($form);
        $encoded = Json::encode($data);
        $migrated = ReferenceMigration::canonicalFieldTokensForFields($form->getFields(), $encoded);

        return $migrated === $encoded ? $data : Json::decode($migrated);
    }

    private function _migrateJsonRows(Form $form, string $table, string $column, array $where): void
    {
        if (!$this->db->tableExists($table) || !$this->db->columnExists($table, $column)) {
            return;
        }

        foreach ((new Query())->select(['id', $column])->from($table)->where($where)->each() as $row) {
            $data = Json::decodeIfJson($row[$column] ?? null);
            if (!is_array($data)) {
                continue;
            }

            $encoded = Json::encode($data);
            $migrated = ReferenceMigration::canonicalFieldTokens($form, $encoded);
            if ($migrated !== $encoded) {
                $this->update($table, [$column => $migrated], ['id' => $row['id']], [], false);
            }
        }
    }
}
