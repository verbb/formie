<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m250612_100000_integration_dispatch extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->columnExists(Table::FORMIE_SUBMISSIONS, 'integrationDispatchContext')) {
            $this->addColumn(Table::FORMIE_SUBMISSIONS, 'integrationDispatchContext', $this->json()->after('content'));
        }

        if (!$this->db->columnExists(Table::FORMIE_NOTIFICATIONS, 'dispatchTiming')) {
            $this->addColumn(Table::FORMIE_NOTIFICATIONS, 'dispatchTiming', $this->string(32)->notNull()->defaultValue('default')->after('conditions'));
        }

        return true;
    }

    public function safeDown(): bool
    {
        if ($this->db->columnExists(Table::FORMIE_SUBMISSIONS, 'integrationDispatchContext')) {
            $this->dropColumn(Table::FORMIE_SUBMISSIONS, 'integrationDispatchContext');
        }

        if ($this->db->columnExists(Table::FORMIE_NOTIFICATIONS, 'dispatchTiming')) {
            $this->dropColumn(Table::FORMIE_NOTIFICATIONS, 'dispatchTiming');
        }

        return true;
    }
}
