<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\RuntimeConfigurationMigration;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\ProjectConfig;

class m260927_010000_instance_configuration extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%formie_instance_configs}}')) {
            $this->createTable('{{%formie_instance_configs}}', [
                'id' => $this->primaryKey(), 'tokenHash' => $this->string(64)->notNull(),
                'formId' => $this->integer()->notNull(), 'siteId' => $this->integer()->notNull(),
                'config' => $this->mediumText()->notNull(), 'expiresAt' => $this->integer()->notNull(),
            ]);
            $this->createIndex(null, '{{%formie_instance_configs}}', 'tokenHash', true);
            $this->createIndex(null, '{{%formie_instance_configs}}', 'expiresAt');
            $this->addForeignKey(null, '{{%formie_instance_configs}}', 'formId', '{{%formie_forms}}', 'id', 'CASCADE');
        }
        if (!$this->db->columnExists(Table::FORMIE_SUBMISSIONS, 'metadata')) {
            $this->addColumn(Table::FORMIE_SUBMISSIONS, 'metadata', $this->json());
        }
        foreach ([Table::FORMIE_FIELDS => 'settings', Table::FORMIE_FORM_FIELDS => 'settings', Table::FORMIE_FORMS => 'settings', Table::FORMIE_STENCILS => 'data', Table::FORMIE_FORM_SITE_OVERRIDES => 'overrides', Table::FORMIE_FIELD_SITE_OVERRIDES => 'overrides'] as $table => $column) {
            if (!$this->db->tableExists($table)) {
                continue;
            }
            foreach ((new Query())->from($table)->each() as $row) {
                $data = Json::decodeIfJson($row[$column]);
                if (!is_array($data)) {
                    continue;
                }
                $type = $row['type'] ?? null;
                if (in_array($table, [Table::FORMIE_FORM_FIELDS, Table::FORMIE_FIELD_SITE_OVERRIDES], true)) {
                    $type = (new Query())->select('type')->from(Table::FORMIE_FIELDS)->where(['id' => $row['fieldId']])->scalar() ?: null;
                }
                $updated = RuntimeConfigurationMigration::migrate($data, $type);
                if ($updated !== $data) {
                    $this->update($table, [$column => Json::encode($updated)], ['id' => $row['id']]);
                }
            }
        }
        $projectConfig = Craft::$app->getProjectConfig();
        foreach (['fields', 'stencils', 'formDefaults'] as $section) {
            $data = (array)$projectConfig->get('formie.' . $section, true);
            $unpacked = ProjectConfig::unpackAssociativeArrays($data);
            $updated = RuntimeConfigurationMigration::migrate($unpacked);
            if ($updated !== $unpacked) {
                $projectConfig->set('formie.' . $section, ProjectConfig::packAssociativeArrays($updated), 'Migrate completion and prefill settings');
            }
        }
        // In-progress submission snapshots are decoded by stable UID on access;
        // do not resave submissions or trigger integrations during an upgrade.
        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }
}
