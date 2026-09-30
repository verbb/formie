<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\ProjectConfig;

/** One-time data upgrade; the beta policy tree is not a supported runtime API. */
class m260929_000000_form_integration_policy extends Migration
{
    // Static Methods
    // =========================================================================

    public static function normalize(array $data): array
    {
        if (array_key_exists('integrationPolicies', $data)) {
            foreach ((array)($data['integrationPolicies']['rerun'] ?? []) as $handle => $trigger) {
                if (is_array($trigger)) {
                    $data['integrations'][$handle]['trigger'] ??= $trigger;
                }
            }
            unset($data['integrationPolicies']);
        }
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::normalize($value);
            }
        }
        return $data;
    }


    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        foreach ([Table::FORMIE_FORMS => 'settings', Table::FORMIE_STENCILS => 'data', Table::FORMIE_FORM_SITE_OVERRIDES => 'overrides', Table::FORMIE_SUBMISSIONS => 'snapshot'] as $table => $column) {
            foreach ((new Query())->select(['id', $column])->from($table)->each() as $row) {
                $data = Json::decodeIfJson($row[$column]);
                if (is_array($data) && ($updated = self::normalize($data)) !== $data) {
                    $this->update($table, [$column => Json::encode($updated)], ['id' => $row['id']]);
                }
            }
        }
        $projectConfig = Craft::$app->getProjectConfig();
        foreach (['stencils', 'formDefaults'] as $section) {
            $data = ProjectConfig::unpackAssociativeArrays((array)$projectConfig->get('formie.' . $section, true));
            if (($updated = self::normalize($data)) !== $data) {
                $projectConfig->set('formie.' . $section, ProjectConfig::packAssociativeArrays($updated), 'Move integration trigger policy into form bindings');
            }
        }
        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }
}
