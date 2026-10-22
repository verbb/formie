<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m261023_000000_field_definition_sequence extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Repair sites that completed the field-table split before its sequence fix.
        if ($this->db->getIsPgsql()) {
            $this->db->createCommand()->resetSequence(Table::FORMIE_FIELDS)->execute();
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261023_000000_field_definition_sequence cannot be reverted.\n";

        return false;
    }
}
