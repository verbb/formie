<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;
use verbb\formie\references\ReferenceMigration;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m260926_000000_reference_slots extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        foreach ((new Query())->select(['id', 'settings'])->from(Table::FORMIE_FORMS)->each() as $row) {
            $settings = Json::decode($row['settings']) ?: [];

            if (!isset($settings['integrations']) || !is_array($settings['integrations'])) {
                continue;
            }
            $migrated = ReferenceMigration::integrationSlots($settings['integrations']);

            if ($migrated === $settings['integrations']) {
                continue;
            }
            $settings['integrations'] = $migrated;
            $this->update(Table::FORMIE_FORMS, ['settings' => Json::encode($settings)], ['id' => $row['id']], [], false);
        }
        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }
}
