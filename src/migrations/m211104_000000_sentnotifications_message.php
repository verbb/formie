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

class m211104_000000_sentnotifications_message extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%formie_sentnotifications}}', 'message')) {
            $this->addColumn('{{%formie_sentnotifications}}', 'message', $this->text()->after('info'));
        }

        if (!$this->db->columnExists('{{%formie_sentnotifications}}', 'success')) {
            $this->addColumn('{{%formie_sentnotifications}}', 'success', $this->boolean()->after('info'));
        }

        $this->db->createCommand()
            ->update('{{%formie_sentnotifications}}', ['success' => true])
            ->execute();
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m211104_000000_sentnotifications_message cannot be reverted.\n";
        return false;
    }
}
