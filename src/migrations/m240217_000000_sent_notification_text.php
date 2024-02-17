<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m240217_000000_sent_notification_text extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->alterColumn('{{%formie_sentnotifications}}', 'subject', $this->text());
        $this->alterColumn('{{%formie_sentnotifications}}', 'to', $this->text());
        $this->alterColumn('{{%formie_sentnotifications}}', 'cc', $this->text());
        $this->alterColumn('{{%formie_sentnotifications}}', 'bcc', $this->text());
        $this->alterColumn('{{%formie_sentnotifications}}', 'replyTo', $this->text());
        $this->alterColumn('{{%formie_sentnotifications}}', 'replyToName', $this->text());
        $this->alterColumn('{{%formie_sentnotifications}}', 'from', $this->text());
        $this->alterColumn('{{%formie_sentnotifications}}', 'fromName', $this->text());
        $this->alterColumn('{{%formie_sentnotifications}}', 'sender', $this->text());

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m240217_000000_sent_notification_text cannot be reverted.\n";
        return false;
    }
}
