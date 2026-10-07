<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m240325_000000_notifications_custom_settings extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->columnExists(Table::FORMIE_NOTIFICATIONS, 'customSettings')) {
            $this->addColumn(Table::FORMIE_NOTIFICATIONS, 'customSettings', $this->text()->after('conditions'));
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m240325_000000_notifications_custom_settings cannot be reverted.\n";

        return false;
    }
}
