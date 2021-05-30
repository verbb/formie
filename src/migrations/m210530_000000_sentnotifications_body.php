<?php
namespace verbb\formie\migrations;

use verbb\formie\elements\Form;
use verbb\formie\fields\formfields\Phone;
use verbb\formie\prosemirror\toprosemirror\Renderer;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;

class m210530_000000_sentnotifications_body extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn('{{%formie_sentnotifications}}', 'body', $this->mediumText());
        $this->alterColumn('{{%formie_sentnotifications}}', 'htmlBody', $this->mediumText());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210530_000000_sentnotifications_body cannot be reverted.\n";
        return false;
    }
}
