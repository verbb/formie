<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Db;

class m211211_000000_stencil_uids extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('plugins.formie.schemaVersion', true);

        if (version_compare($schemaVersion, '1.2.6', '>=')) {
            return;
        }

        // Update the project config for stencils.
        $data = [];

        foreach (Formie::$plugin->getStencils()->getAllStencils() as $stencil) {
            $data[$stencil->uid] = $stencil->getConfig();
        }

        $projectConfig->set('formie.stencils', $data);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m211211_000000_stencil_uids cannot be reverted.\n";
        return false;
    }
}
