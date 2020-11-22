<?php
namespace verbb\formie\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;

class m201122_000000_notification_conditions extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%formie_notifications}}', 'enableConditions')) {
            $this->addColumn('{{%formie_notifications}}', 'enableConditions', $this->boolean()->defaultValue(false)->after('attachFiles'));
        }

        if (!$this->db->columnExists('{{%formie_notifications}}', 'conditions')) {
            $this->addColumn('{{%formie_notifications}}', 'conditions', $this->text()->after('enableConditions'));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m201122_000000_notification_conditions cannot be reverted.\n";
        return false;
    }
}
