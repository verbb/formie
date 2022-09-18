<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m220918_000000_email_sender extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%formie_notifications}}', 'sender')) {
            $this->addColumn('{{%formie_notifications}}', 'sender', $this->text()->after('fromName'));
        }

        if (!$this->db->columnExists('{{%formie_sentnotifications}}', 'sender')) {
            $this->addColumn('{{%formie_sentnotifications}}', 'sender', $this->string()->after('fromName'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220918_000000_email_sender cannot be reverted.\n";
        return false;
    }
}
