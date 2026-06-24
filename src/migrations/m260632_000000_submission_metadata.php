<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m260632_000000_submission_metadata extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->columnExists(Table::FORMIE_SUBMISSIONS, 'metadata')) {
            $after = $this->db->columnExists(Table::FORMIE_SUBMISSIONS, 'integrationDispatchContext')
                ? 'integrationDispatchContext'
                : 'content';

            $this->addColumn(Table::FORMIE_SUBMISSIONS, 'metadata', $this->json()->after($after));
        }

        return true;
    }

    public function safeDown(): bool
    {
        if ($this->db->columnExists(Table::FORMIE_SUBMISSIONS, 'metadata')) {
            $this->dropColumn(Table::FORMIE_SUBMISSIONS, 'metadata');
        }

        return true;
    }
}
