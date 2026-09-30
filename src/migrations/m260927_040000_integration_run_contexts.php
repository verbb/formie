<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;
use verbb\formie\models\IntegrationRunResults;
use verbb\formie\services\IntegrationDispatcher;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;

use DateTime;

class m260927_040000_integration_run_contexts extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $table = IntegrationDispatcher::CONTEXT_TABLE;
        if (!$this->db->tableExists($table)) {
            $this->createTable($table, [
                'id' => $this->primaryKey(),
                'submissionId' => $this->integer()->notNull(),
                'runUid' => $this->string(255)->notNull(),
                'context' => $this->mediumText()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
            ]);
            $this->createIndex(null, $table, ['submissionId', 'runUid'], true);
            $this->addForeignKey(null, $table, 'submissionId', Table::FORMIE_SUBMISSIONS, 'id', 'CASCADE');
        }
        if ($this->db->columnExists(Table::FORMIE_SUBMISSIONS, 'integrationDispatchContext')) {
            $now = Db::prepareDateForDb(new DateTime());
            foreach ((new Query())->select(['id', 'integrationDispatchContext'])->from(Table::FORMIE_SUBMISSIONS)->each() as $row) {
                // Older saves could double-encode this JSON column. Preserve only
                // its recorded run attribution; never pretend it is the latest run.
                $context = IntegrationRunResults::fromStorage(Json::decodeIfJson($row['integrationDispatchContext']));
                $runs = [];
                foreach ($context->results as $handle => $result) {
                    if (!is_array($result)) {
                        continue;
                    }
                    $runUid = $result['executionUid'] ?? null;
                    if (!is_string($runUid) || $runUid === '' || strlen($runUid) > 255) {
                        $runUid = 'legacy-context:' . $row['id'];
                    }
                    $runs[$runUid]['results'][$handle] = $result;
                }
                foreach ($runs as $runUid => $value) {
                    $this->db->createCommand()->upsert($table, [
                        'submissionId' => $row['id'], 'runUid' => (string)$runUid,
                        'context' => Json::encode($value), 'dateCreated' => $now, 'dateUpdated' => $now,
                    ], false)->execute();
                }
            }
            $this->dropColumn(Table::FORMIE_SUBMISSIONS, 'integrationDispatchContext');
        }
        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }
}
