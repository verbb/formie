<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m260926_010000_submission_continuity extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->tableExists(Table::FORMIE_SUBMISSION_PROGRESS)) {
            $this->createTable(Table::FORMIE_SUBMISSION_PROGRESS, [
                'id' => $this->primaryKey(),
                'formId' => $this->integer()->notNull(),
                'siteId' => $this->integer()->notNull(),
                'submissionId' => $this->integer(),
                'browserHash' => $this->char(64)->notNull(),
                'currentPageId' => $this->integer(),
                'content' => $this->mediumText(),
                'version' => $this->integer()->notNull()->defaultValue(0),
                'expiresAt' => $this->integer()->notNull(),
            ]);
            $this->createIndex(null, Table::FORMIE_SUBMISSION_PROGRESS, 'submissionId', true);
            $this->createIndex(null, Table::FORMIE_SUBMISSION_PROGRESS, 'expiresAt');
            $this->addForeignKey(null, Table::FORMIE_SUBMISSION_PROGRESS, 'submissionId', Table::FORMIE_SUBMISSIONS, 'id', 'CASCADE');
            $this->addForeignKey(null, Table::FORMIE_SUBMISSION_PROGRESS, 'formId', Table::FORMIE_FORMS, 'id', 'CASCADE');
            $this->addForeignKey(null, Table::FORMIE_SUBMISSION_PROGRESS, 'siteId', '{{%sites}}', 'id', 'CASCADE');
        }
        if (!$this->db->tableExists(Table::FORMIE_SUBMISSION_GRANTS)) {
            $this->createTable(Table::FORMIE_SUBMISSION_GRANTS, [
                'id' => $this->primaryKey(),
                'tokenHash' => $this->string(80),
                'bindingHash' => $this->char(64),
                'parentId' => $this->integer(),
                'progressId' => $this->integer(),
                'submissionId' => $this->integer(),
                'formId' => $this->integer()->notNull(),
                'siteId' => $this->integer()->notNull(),
                'purpose' => $this->string(32)->notNull(),
                'expiresAt' => $this->integer()->notNull(),
                'revokedAt' => $this->integer(),
                'dateCreated' => $this->dateTime()->notNull(),
            ]);
            $this->createIndex(null, Table::FORMIE_SUBMISSION_GRANTS, 'tokenHash', true);
            $this->createIndex(null, Table::FORMIE_SUBMISSION_GRANTS, ['formId', 'siteId', 'bindingHash']);
            $this->createIndex(null, Table::FORMIE_SUBMISSION_GRANTS, 'expiresAt');
            $this->addForeignKey(null, Table::FORMIE_SUBMISSION_GRANTS, 'parentId', Table::FORMIE_SUBMISSION_GRANTS, 'id', 'CASCADE');
            $this->addForeignKey(null, Table::FORMIE_SUBMISSION_GRANTS, 'progressId', Table::FORMIE_SUBMISSION_PROGRESS, 'id', 'SET NULL');
            $this->addForeignKey(null, Table::FORMIE_SUBMISSION_GRANTS, 'submissionId', Table::FORMIE_SUBMISSIONS, 'id', 'CASCADE');
            $this->addForeignKey(null, Table::FORMIE_SUBMISSION_GRANTS, 'formId', Table::FORMIE_FORMS, 'id', 'CASCADE');
            $this->addForeignKey(null, Table::FORMIE_SUBMISSION_GRANTS, 'siteId', '{{%sites}}', 'id', 'CASCADE');
        }
        foreach ([
            'state' => $this->string(16)->notNull()->defaultValue('staged'),
            'siteId' => $this->integer(),
            'browserHash' => $this->char(64),
            'contentKey' => $this->string(255),
            'progressId' => $this->integer(),
            'expiresAt' => $this->integer(),
            'capabilities' => $this->text(),
            'promotionFolderId' => $this->integer(),
            'promotionFilename' => $this->string(255),
            'promotionSourceFolderId' => $this->integer(),
            'contentHash' => $this->char(64),
            'promotionState' => $this->string(16),
            'failureCode' => $this->string(64),
        ] as $column => $definition) {
            if (!$this->db->columnExists(Table::FORMIE_PENDING_UPLOADS, $column)) {
                $this->addColumn(Table::FORMIE_PENDING_UPLOADS, $column, $definition);
            }
        }
        $this->update(Table::FORMIE_PENDING_UPLOADS, ['state' => 'finalized'], ['isFinalized' => true]);
        $this->update(Table::FORMIE_PENDING_UPLOADS, ['expiresAt' => time()], ['expiresAt' => null, 'state' => 'staged']);
        // Beta credentials cannot be migrated with trustworthy purpose or ownership.
        // Preserve canonical Submission content; deliberately expire the old links.
        foreach ([Table::FORMIE_SUBMISSION_RESUME_TOKENS, Table::FORMIE_SUBMISSION_DRAFTS] as $table) {
            if ($this->db->tableExists($table)) {
                $this->dropTable($table);
            }
        }
        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }
}
