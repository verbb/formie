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

class m210613_000000_sentnotifications_notificationId extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%formie_sentnotifications}}', 'notificationId')) {
            $this->addColumn('{{%formie_sentnotifications}}', 'notificationId', $this->integer()->after('submissionId'));

            $this->addForeignKey(null, '{{%formie_sentnotifications}}', ['notificationId'], '{{%formie_notifications}}', ['id'], 'CASCADE', null);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210613_000000_sentnotifications_notificationId cannot be reverted.\n";
        return false;
    }
}
