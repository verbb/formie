<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m260927_060000_independent_submission_grants extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->tableExists(Table::FORMIE_SUBMISSION_GRANTS) || !$this->db->columnExists(Table::FORMIE_SUBMISSION_GRANTS, 'progressId')) {
            return true;
        }

        // Progress is an optional navigation hint. Removing it must not revoke a
        // still-valid capability for the incomplete submission itself.
        $this->dropForeignKeyIfExists(Table::FORMIE_SUBMISSION_GRANTS, ['progressId']);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSION_GRANTS, 'progressId', Table::FORMIE_SUBMISSION_PROGRESS, 'id', 'SET NULL');

        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }
}
