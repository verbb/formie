<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m211125_000000_notification_attach_assets extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%formie_notifications}}', 'attachAssets')) {
            $this->addColumn('{{%formie_notifications}}', 'attachAssets', $this->text()->after('attachPdf'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m211125_000000_notification_attach_assets cannot be reverted.\n";
        return false;
    }
}
