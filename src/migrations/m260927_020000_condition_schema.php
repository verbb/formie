<?php
namespace verbb\formie\migrations;

use verbb\formie\conditions\ConditionMigration;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\ProjectConfig;

class m260927_020000_condition_schema extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        foreach ([Table::FORMIE_FIELDS => 'settings', Table::FORMIE_FORM_FIELDS => 'settings', Table::FORMIE_FORMS => 'settings', Table::FORMIE_FIELD_LAYOUT_PAGES => 'settings', Table::FORMIE_NOTIFICATIONS => 'conditions', Table::FORMIE_STENCILS => 'data', Table::FORMIE_FORM_SITE_OVERRIDES => 'overrides', Table::FORMIE_FIELD_SITE_OVERRIDES => 'overrides'] as $table => $column) {
            if (!$this->db->tableExists($table)) {
                continue;
            }
            foreach ((new Query())->from($table)->each() as $row) {
                $data = Json::decodeIfJson($row[$column]);
                if (!is_array($data)) {
                    continue;
                }
                $updated = ConditionMigration::migrate($data);
                if ($updated !== $data) {
                    $this->update($table, [$column => Json::encode($updated)], ['id' => $row['id']]);
                }
            }
        }
        $projectConfig = Craft::$app->getProjectConfig();
        foreach (['fields', 'stencils', 'formDefaults'] as $section) {
            $data = (array)$projectConfig->get('formie.' . $section, true);
            $unpacked = ProjectConfig::unpackAssociativeArrays($data);
            $updated = ConditionMigration::migrate($unpacked);
            if ($updated !== $unpacked) {
                $projectConfig->set('formie.' . $section, ProjectConfig::packAssociativeArrays($updated), 'Version condition configuration');
            }
        }
        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }
}
