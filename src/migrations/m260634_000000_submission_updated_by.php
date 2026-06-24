<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m260634_000000_submission_updated_by extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->columnExists(Table::FORMIE_SUBMISSIONS, 'updatedById')) {
            $this->addColumn(Table::FORMIE_SUBMISSIONS, 'updatedById', $this->integer()->after('userId'));
        }

        $this->dropIndexIfExists(Table::FORMIE_SUBMISSIONS, ['updatedById']);
        $this->createIndex(null, Table::FORMIE_SUBMISSIONS, 'updatedById', false);

        $this->dropForeignKeyIfExists(Table::FORMIE_SUBMISSIONS, ['updatedById']);
        $this->addForeignKey(
            null,
            Table::FORMIE_SUBMISSIONS,
            ['updatedById'],
            '{{%users}}',
            ['id'],
            'SET NULL',
            null,
        );

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropForeignKeyIfExists(Table::FORMIE_SUBMISSIONS, ['updatedById']);

        if ($this->db->columnExists(Table::FORMIE_SUBMISSIONS, 'updatedById')) {
            $this->dropColumn(Table::FORMIE_SUBMISSIONS, 'updatedById');
        }

        return true;
    }
}
