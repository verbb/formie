<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;

use Craft;
use craft\db\Migration;

class m260607_000000_field_palette extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Project config writes belong on the dev/source environment only.
        // Other environments receive `formie.fieldPalette` via project config sync.
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            return true;
        }

        $plugin = Formie::$plugin;
        $fieldPalette = $plugin->getFieldPalette();

        if (!$fieldPalette->hasStoredConfig()) {
            $fieldPalette->saveDefaultPalette(Craft::t('formie', 'Migrate field palette from legacy disabled field types'));
        }

        $settings = $plugin->getSettings()->toArray();

        if (!empty($settings['disabledFields'])) {
            $settings['disabledFields'] = [];
            Craft::$app->getPlugins()->savePluginSettings($plugin, $settings);
        }

        return true;
    }
}
