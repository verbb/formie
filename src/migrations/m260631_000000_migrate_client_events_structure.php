<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\ClientEventsHelper;
use verbb\formie\helpers\Table;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m260631_000000_migrate_client_events_structure extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $table = Table::FORMIE_FIELD_LAYOUT_PAGES;

        foreach ((new Query())->select(['id', 'settings'])->from($table)->each() as $row) {
            $settings = Json::decodeIfJson($row['settings'] ?? '{}');

            if (!is_array($settings)) {
                continue;
            }

            if (!empty($settings['clientEvents']) || empty($settings['clientEventFields'])) {
                continue;
            }

            $settings['clientEvents'] = ClientEventsHelper::migrateLegacyEventFields($settings['clientEventFields']);

            $this->update($table, [
                'settings' => Json::encode($settings),
            ], ['id' => $row['id']], [], false);
        }

        return true;
    }

    public function safeDown(): bool
    {
        $table = Table::FORMIE_FIELD_LAYOUT_PAGES;

        foreach ((new Query())->select(['id', 'settings'])->from($table)->each() as $row) {
            $settings = Json::decodeIfJson($row['settings'] ?? '{}');

            if (!is_array($settings) || empty($settings['clientEvents'])) {
                continue;
            }

            unset($settings['clientEvents']);

            $this->update($table, [
                'settings' => Json::encode($settings),
            ], ['id' => $row['id']], [], false);
        }

        return true;
    }
}
