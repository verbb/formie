<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m260926_000000_submission_operations extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->columnExists(Table::FORMIE_SUBMISSIONS, 'stateVersion')) {
            $this->addColumn(Table::FORMIE_SUBMISSIONS, 'stateVersion', $this->integer()->notNull()->defaultValue(0));
        }
        if (!$this->db->tableExists(Table::FORMIE_SUBMISSION_OPERATIONS)) {
            $this->createTable(Table::FORMIE_SUBMISSION_OPERATIONS, [
                'id' => $this->primaryKey(),
                'operationHash' => $this->char(64)->notNull(),
                'requestHash' => $this->char(64),
                'fingerprint' => $this->char(64)->notNull(),
                'formId' => $this->integer()->notNull(),
                'submissionId' => $this->integer(),
                'operation' => $this->string(32)->notNull(),
                'state' => $this->string(16)->notNull(),
                'outcome' => $this->mediumText(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'expiresAt' => $this->dateTime()->notNull(),
            ]);
            $this->createIndex(null, Table::FORMIE_SUBMISSION_OPERATIONS, 'operationHash', true);
            $this->createIndex(null, Table::FORMIE_SUBMISSION_OPERATIONS, 'expiresAt');
            $this->createIndex(null, Table::FORMIE_SUBMISSION_OPERATIONS, 'requestHash', true);
            $this->addForeignKey(null, Table::FORMIE_SUBMISSION_OPERATIONS, 'formId', Table::FORMIE_FORMS, 'id', 'CASCADE');
            // Keep the receipt if its submission is deleted, so a retry cannot create a replacement.
            $this->addForeignKey(null, Table::FORMIE_SUBMISSION_OPERATIONS, 'submissionId', Table::FORMIE_SUBMISSIONS, 'id', 'SET NULL');
        }
        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }
}
