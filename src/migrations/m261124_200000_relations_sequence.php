<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m261124_200000_relations_sequence extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Repair sites where saving relations reset the sequence below existing rows.
        if ($this->db->getIsPgsql() && $this->db->tableExists(Table::FORMIE_RELATIONS)) {
            $this->db->createCommand()->resetSequence(Table::FORMIE_RELATIONS)->execute();
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261124_200000_relations_sequence cannot be reverted.\n";

        return false;
    }
}
