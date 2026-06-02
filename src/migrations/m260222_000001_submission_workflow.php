<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;
use Throwable;

class m260222_000001_submission_workflow extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->tableExists(Table::FORMIE_SUBMISSION_WORKFLOW)) {
            $this->createTable(Table::FORMIE_SUBMISSION_WORKFLOW, [
                'id' => $this->primaryKey(),
                'submissionId' => $this->integer()->notNull(),
                'stage' => $this->string(64)->notNull(),
                'idempotencyKey' => $this->string(255),
                'isDispatched' => $this->boolean()->notNull()->defaultValue(true),
                'dateDispatched' => $this->dateTime()->notNull(),
                'meta' => $this->mediumText(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        try {
            $this->createIndex(null, Table::FORMIE_SUBMISSION_WORKFLOW, 'submissionId', false);
        } catch (Throwable) {
            // Index may already exist in some environments.
        }

        try {
            $this->createIndex(null, Table::FORMIE_SUBMISSION_WORKFLOW, ['submissionId', 'stage', 'idempotencyKey'], true);
        } catch (Throwable) {
            // Index may already exist in some environments.
        }

        try {
            $this->addForeignKey(
                null,
                Table::FORMIE_SUBMISSION_WORKFLOW,
                ['submissionId'],
                Table::FORMIE_SUBMISSIONS,
                ['id'],
                'CASCADE',
                null
            );
        } catch (Throwable) {
            // Foreign key may already exist in some environments.
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260222_000001_submission_workflow cannot be reverted.\n";

        return false;
    }
}
