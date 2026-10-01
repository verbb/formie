<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;
use verbb\formie\services\DeliveryAttempts;
use verbb\formie\services\SubmissionDispatches;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use DateTime;

class m260927_050000_submission_dispatches extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $table = SubmissionDispatches::TABLE;

        if ($this->db->tableExists($table)) {
            return true;
        }
        $this->createTable($table, [
            'id' => $this->primaryKey(),
            'submissionId' => $this->integer()->notNull(),
            'uid' => $this->string(255)->notNull(),
            'identity' => $this->string(64)->notNull(),
            'kind' => $this->string(32)->notNull(),
            'status' => $this->string(32)->notNull(),
            'submissionVersion' => $this->integer()->notNull(),
            'command' => $this->text(),
            'schedulingComplete' => $this->boolean()->notNull()->defaultValue(false),
            'failureCode' => $this->string(64),
            'scheduledAt' => $this->dateTime(),
            'startedAt' => $this->dateTime(),
            'completedAt' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
        ]);
        $this->createIndex(null, $table, 'identity', true);
        $this->createIndex(null, $table, ['submissionId', 'uid'], true);
        $this->createIndex(null, $table, ['schedulingComplete', 'status', 'scheduledAt']);
        $this->addForeignKey(null, $table, 'submissionId', Table::FORMIE_SUBMISSIONS, 'id', 'CASCADE');
        $this->_backfillHistory();
        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }


    // Private Methods
    // =========================================================================

    private function _backfillHistory(): void
    {
        $table = SubmissionDispatches::TABLE;
        // Existing completions are historical, not an invitation to deliver again.
        // Existing attempt identities and queue jobs remain untouched.
        $now = Db::prepareDateForDb(new DateTime());

        foreach ((new Query())->select(['id', 'stateVersion'])->from(Table::FORMIE_SUBMISSIONS)->where(['isIncomplete' => false])->each() as $row) {
            $this->db->createCommand()->upsert($table, [
                'submissionId' => $row['id'], 'uid' => StringHelper::UUID(),
                'identity' => hash('sha256', Json::encode([(int)$row['id'], 'completion'])),
                'kind' => 'completion', 'status' => 'completed', 'submissionVersion' => $row['stateVersion'],
                'schedulingComplete' => true, 'completedAt' => $now, 'dateCreated' => $now, 'dateUpdated' => $now,
            ], false)->execute();
        }

        foreach ((new Query())->select(['a.submissionId', 'a.executionUid', 's.stateVersion'])->distinct()
            ->from(['a' => DeliveryAttempts::TABLE])->innerJoin(['s' => Table::FORMIE_SUBMISSIONS], '[[s.id]] = [[a.submissionId]]')->each() as $row) {
            $statuses = (new Query())->select('status')->distinct()->from(DeliveryAttempts::TABLE)->where(['submissionId' => $row['submissionId'], 'executionUid' => $row['executionUid']])->column();
            $status = match (true) {
                (bool)array_intersect(['unknown', 'sending'], $statuses) => 'needs-attention',
                in_array('pending', $statuses, true) => 'scheduled',
                (bool)array_intersect(['failed', 'rejected'], $statuses) => 'completed-with-failures',
                default => 'completed',
            };
            $this->db->createCommand()->upsert($table, [
                'submissionId' => $row['submissionId'], 'uid' => $row['executionUid'],
                'identity' => hash('sha256', Json::encode([(int)$row['submissionId'], 'manual:' . $row['executionUid']])),
                'kind' => 'legacy', 'status' => $status, 'submissionVersion' => $row['stateVersion'],
                'schedulingComplete' => true, 'dateCreated' => $now, 'dateUpdated' => $now,
            ], false)->execute();
        }
    }
}
