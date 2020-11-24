<?php
namespace verbb\formie\migrations;

use verbb\formie\elements\Form;
use verbb\formie\fields\formfields\Agree;
use verbb\formie\prosemirror\toprosemirror\Renderer;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;

class m201119_000000_increase_stencil extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn('{{%formie_forms}}', 'settings', $this->mediumText());
        $this->alterColumn('{{%formie_stencils}}', 'data', $this->mediumText());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m201119_000000_increase_stencil cannot be reverted.\n";
        return false;
    }
}
