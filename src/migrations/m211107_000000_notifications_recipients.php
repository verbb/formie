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

class m211107_000000_notifications_recipients extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%formie_notifications}}', 'fileUploadsAction')) {
            $this->addColumn('{{%formie_notifications}}', 'recipients', $this->enum('recipients', ['email', 'conditions'])
                ->defaultValue('email')
                ->notNull()
                ->after('subject'));
        }

        if (!$this->db->columnExists('{{%formie_notifications}}', 'toConditions')) {
            $this->addColumn('{{%formie_notifications}}', 'toConditions', $this->text()->after('to'));
        }

        $this->db->createCommand()
            ->update('{{%formie_notifications}}', ['recipients' => 'email'])
            ->execute();
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m211107_000000_notifications_recipients cannot be reverted.\n";
        return false;
    }
}
