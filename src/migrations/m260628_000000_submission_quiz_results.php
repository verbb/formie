<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m260628_000000_submission_quiz_results extends Migration
{
    // Public Methods
    // =========================================================================
    
    public function safeUp(): bool
    {
        if ($this->db->tableExists(Table::FORMIE_SUBMISSION_QUIZ_RESULTS)) {
            return true;
        }

        $this->createTable(Table::FORMIE_SUBMISSION_QUIZ_RESULTS, [
            'id' => $this->primaryKey(),
            'submissionId' => $this->integer()->notNull(),
            'score' => $this->decimal(12, 4)->notNull()->defaultValue(0),
            'maxScore' => $this->decimal(12, 4)->notNull()->defaultValue(0),
            'percentage' => $this->decimal(8, 2)->notNull()->defaultValue(0),
            'passed' => $this->boolean()->notNull()->defaultValue(false),
            'questionResults' => $this->json(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, Table::FORMIE_SUBMISSION_QUIZ_RESULTS, 'submissionId', true);
        $this->addForeignKey(
            null,
            Table::FORMIE_SUBMISSION_QUIZ_RESULTS,
            ['submissionId'],
            Table::FORMIE_SUBMISSIONS,
            ['id'],
            'CASCADE',
            null,
        );

        return true;
    }

    public function safeDown(): bool
    {
        if (!$this->db->tableExists(Table::FORMIE_SUBMISSION_QUIZ_RESULTS)) {
            return true;
        }

        $this->dropTableIfExists(Table::FORMIE_SUBMISSION_QUIZ_RESULTS);

        return true;
    }
}
