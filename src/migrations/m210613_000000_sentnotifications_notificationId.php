<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m210613_000000_sentnotifications_notificationId extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%formie_sentnotifications}}', 'notificationId')) {
            $this->addColumn('{{%formie_sentnotifications}}', 'notificationId', $this->integer()->after('submissionId'));

            $this->addForeignKey(null, '{{%formie_sentnotifications}}', ['notificationId'], '{{%formie_notifications}}', ['id'], 'CASCADE', null);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m210613_000000_sentnotifications_notificationId cannot be reverted.\n";
        return false;
    }
}
