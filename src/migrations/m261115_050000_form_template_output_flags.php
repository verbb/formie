<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;

class m261115_050000_form_template_output_flags extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $table = Table::FORMIE_FORM_TEMPLATES;

        // Only sites upgraded from Formie 3 still have its settings to read.
        if (!$this->db->columnExists($table, 'outputCssLayout') || !$this->db->columnExists($table, 'outputJsBase')) {
            return true;
        }

        $projectConfig = Craft::$app->getProjectConfig();

        // Formie 3's theme settings were nested under these, and only applied while they were on. Earlier upgrades
        // also turned assets on when only the theme setting was on. A template saved since then keeps its settings.
        foreach ((new Query())->select(['id', 'uid', 'outputCssLayout', 'outputJsBase'])->from($table)->all($this->db) as $row) {
            $config = $projectConfig->get('formie.formTemplates.' . $row['uid']);
            $update = [];

            if (is_array($config) && !array_key_exists('outputCss', $config)) {
                $update['outputCss'] = (bool)$row['outputCssLayout'];
            }

            if (is_array($config) && !array_key_exists('outputJs', $config)) {
                $update['outputJs'] = (bool)$row['outputJsBase'];
            }

            if ($update) {
                $this->update($table, $update, ['id' => $row['id']], [], false);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261115_050000_form_template_output_flags cannot be reverted.\n";

        return false;
    }
}
