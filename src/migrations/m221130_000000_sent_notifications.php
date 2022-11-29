<?php
namespace verbb\formie\migrations;

use craft\db\Migration;
use craft\helpers\MigrationHelper;

class m221130_000000_sent_notifications extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Change values to allow null
        MigrationHelper::dropForeignKeyIfExists('{{%formie_sentnotifications}}', ['formId'], $this);
        MigrationHelper::dropForeignKeyIfExists('{{%formie_sentnotifications}}', ['submissionId'], $this);
        MigrationHelper::dropForeignKeyIfExists('{{%formie_sentnotifications}}', ['notificationId'], $this);
        $this->addForeignKey(null, '{{%formie_sentnotifications}}', ['formId'], '{{%formie_forms}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%formie_sentnotifications}}', ['submissionId'], '{{%formie_submissions}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%formie_sentnotifications}}', ['notificationId'], '{{%formie_notifications}}', ['id'], 'SET NULL', null);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m221130_000000_sent_notifications cannot be reverted.\n";
        return false;
    }
}
