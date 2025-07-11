<?php
namespace verbb\formie\migrations;

use verbb\formie\fields\Forms;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;

class m250711_000000_fix_element_field_source_ids extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $fields = (new Query())
            ->select(['*'])
            ->from([Table::FIELDS])
            ->where(['type' => Forms::class])
            ->all();

        foreach ($fields as $field) {
            $settings = Json::decode($field['settings']);
            $sources = $settings['sources'] ?? [];
            $hasChanged = false;

            if (is_array($sources)) {
                foreach ($sources as $key => $source) {
                    $parts = explode(':', $source);

                    if (isset($parts[1]) && is_numeric($parts[1])) {
                        $templateUid = Db::uidById(Table::FORMIE_FORM_TEMPLATES, $parts[1]);

                        if ($templateUid) {
                            $sources[$key] = $parts[0] . ':' . $templateUid;

                            $hasChanged = true;
                        }
                    }
                }

                if ($hasChanged) {
                    $settings['sources'] = $sources;

                    $this->update(Table::FIELDS, [
                        'settings' => Json::encode($settings),
                    ], ['id' => $field['id']], [], false);
                }
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m250711_000000_fix_element_field_source_ids cannot be reverted.\n";

        return false;
    }
}
