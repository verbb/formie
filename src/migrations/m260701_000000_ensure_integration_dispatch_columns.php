<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m260701_000000_ensure_integration_dispatch_columns extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->columnExists(Table::FORMIE_SUBMISSIONS, 'integrationDispatchContext')) {
            $after = $this->db->columnExists(Table::FORMIE_SUBMISSIONS, 'content')
                ? 'content'
                : null;

            $column = $this->json();

            if ($after) {
                $column->after($after);
            }

            $this->addColumn(Table::FORMIE_SUBMISSIONS, 'integrationDispatchContext', $column);
        }

        if (!$this->db->columnExists(Table::FORMIE_NOTIFICATIONS, 'dispatchTiming')) {
            $after = null;

            if ($this->db->columnExists(Table::FORMIE_NOTIFICATIONS, 'customSettings')) {
                $after = 'customSettings';
            } elseif ($this->db->columnExists(Table::FORMIE_NOTIFICATIONS, 'conditions')) {
                $after = 'conditions';
            }

            $column = $this->string(32)->notNull()->defaultValue('default');

            if ($after) {
                $column->after($after);
            }

            $this->addColumn(Table::FORMIE_NOTIFICATIONS, 'dispatchTiming', $column);
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260701_000000_ensure_integration_dispatch_columns cannot be reverted.\n";

        return false;
    }
}
