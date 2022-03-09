<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m201122_000000_notification_conditions extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%formie_notifications}}', 'enableConditions')) {
            $this->addColumn('{{%formie_notifications}}', 'enableConditions', $this->boolean()->defaultValue(false)->after('attachFiles'));
        }

        if (!$this->db->columnExists('{{%formie_notifications}}', 'conditions')) {
            $this->addColumn('{{%formie_notifications}}', 'conditions', $this->text()->after('enableConditions'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m201122_000000_notification_conditions cannot be reverted.\n";
        return false;
    }
}
