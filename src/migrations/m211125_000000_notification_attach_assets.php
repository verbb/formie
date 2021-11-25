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

class m211125_000000_notification_attach_assets extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%formie_notifications}}', 'attachAssets')) {
            $this->addColumn('{{%formie_notifications}}', 'attachAssets', $this->text()->after('attachPdf'));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m211125_000000_notification_attach_assets cannot be reverted.\n";
        return false;
    }
}
