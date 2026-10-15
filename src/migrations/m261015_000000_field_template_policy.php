<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\FieldBuilderPolicy;

use Craft;
use craft\db\Migration;

class m261015_000000_field_template_policy extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        // Persist only missing choices. Do not copy config-file overrides into
        // project config or replace decisions already deployed by the site owner.
        foreach (FieldBuilderPolicy::legacyTemplateSettings() as $key => $value) {
            $path = 'plugins.formie.settings.' . $key;

            if ($projectConfig->get($path) === null) {
                $projectConfig->set($path, $value, 'Preserve existing field template capabilities');
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }
}
