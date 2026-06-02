<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;
use Throwable;

class m260224_000005_submission_resume_tokens extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->tableExists(Table::FORMIE_SUBMISSION_RESUME_TOKENS)) {
            $this->createTable(Table::FORMIE_SUBMISSION_RESUME_TOKENS, [
                'id' => $this->primaryKey(),
                'token' => $this->string(128)->notNull(),
                'storageKey' => $this->string(255)->notNull(),
                'formId' => $this->integer()->notNull(),
                'siteId' => $this->integer()->notNull(),
                'submissionId' => $this->integer(),
                'capabilities' => $this->text(),
                'issuedAt' => $this->integer(),
                'expiresAt' => $this->integer(),
                'revokedAt' => $this->integer(),
                'dateExpires' => $this->dateTime(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        try {
            $this->createIndex(null, Table::FORMIE_SUBMISSION_RESUME_TOKENS, 'token', true);
            $this->createIndex(null, Table::FORMIE_SUBMISSION_RESUME_TOKENS, 'storageKey', false);
            $this->createIndex(null, Table::FORMIE_SUBMISSION_RESUME_TOKENS, 'dateExpires', false);
        } catch (Throwable) {
            // Indexes may already exist.
        }

        try {
            $this->addForeignKey(
                null,
                Table::FORMIE_SUBMISSION_RESUME_TOKENS,
                ['formId'],
                Table::FORMIE_FORMS,
                ['id'],
                'CASCADE',
                null
            );
        } catch (Throwable) {
            // FK may already exist.
        }

        try {
            $this->addForeignKey(
                null,
                Table::FORMIE_SUBMISSION_RESUME_TOKENS,
                ['siteId'],
                '{{%sites}}',
                ['id'],
                'CASCADE',
                null
            );
        } catch (Throwable) {
            // FK may already exist.
        }

        try {
            $this->addForeignKey(
                null,
                Table::FORMIE_SUBMISSION_RESUME_TOKENS,
                ['submissionId'],
                Table::FORMIE_SUBMISSIONS,
                ['id'],
                'SET NULL',
                null
            );
        } catch (Throwable) {
            // FK may already exist.
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260224_000005_submission_resume_tokens cannot be reverted.\n";

        return false;
    }
}
