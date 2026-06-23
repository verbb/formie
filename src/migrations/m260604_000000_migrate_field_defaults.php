<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\helpers\MigrationHelper;

use craft\db\Migration;

class m260604_000000_migrate_field_defaults extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $plugin = Formie::$plugin;
        $settings = $plugin->getSettings()->toArray();
        $settings = $plugin->getFormDefaults()->migrateLegacyFieldDefaults($settings);

        MigrationHelper::savePluginSettingsIfAllowed($plugin, $settings);

        return true;
    }
}
