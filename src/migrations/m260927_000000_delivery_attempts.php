<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;
use verbb\formie\models\IntegrationDispatchPlan;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m260927_000000_delivery_attempts extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%formie_delivery_attempts}}')) {
            $this->createTable('{{%formie_delivery_attempts}}', [
                'id' => $this->primaryKey(),
                'uid' => $this->uid()->notNull(),
                'identity' => $this->string(64)->notNull(),
                'submissionId' => $this->integer()->notNull(),
                'formId' => $this->integer()->notNull(),
                'binding' => $this->string(255)->notNull(),
                'step' => $this->string(255)->notNull(),
                'parentUid' => $this->string(36),
                'executionUid' => $this->string(255)->notNull(),
                'execution' => $this->string(16)->notNull(),
                'status' => $this->string(16)->notNull()->defaultValue('pending'),
                'payloadHash' => $this->string(64),
                'requestKey' => $this->uid()->notNull(),
                'result' => $this->text(),
                'data' => $this->mediumText(),
                'response' => $this->mediumText(),
                'startedAt' => $this->dateTime(),
                'completedAt' => $this->dateTime(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
            ]);
            $this->createIndex(null, '{{%formie_delivery_attempts}}', 'uid', true);
            $this->createIndex(null, '{{%formie_delivery_attempts}}', 'identity', true);
            $this->createIndex(null, '{{%formie_delivery_attempts}}', ['submissionId', 'executionUid']);
            $this->createIndex(null, '{{%formie_delivery_attempts}}', 'parentUid');
            $this->addForeignKey(null, '{{%formie_delivery_attempts}}', 'submissionId', Table::FORMIE_SUBMISSIONS, 'id', 'CASCADE');
        }

        if (!$this->db->tableExists('{{%formie_delivery_diagnostics}}')) {
            $this->createTable('{{%formie_delivery_diagnostics}}', [
                'id' => $this->primaryKey(),
                'attemptId' => $this->integer()->notNull(),
                'checkpoint' => $this->string(64)->notNull(),
                'data' => $this->text()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
            ]);
            $this->createIndex(null, '{{%formie_delivery_diagnostics}}', ['attemptId', 'id']);
            $this->addForeignKey(null, '{{%formie_delivery_diagnostics}}', 'attemptId', '{{%formie_delivery_attempts}}', 'id', 'CASCADE');
        }

        // Canonicalize beta plans without resaving elements or firing deliveries.
        foreach ((new Query())->from(Table::FORMIE_FORMS)->each() as $row) {
            $settings = is_string($row['settings']) ? Json::decode($row['settings']) : $row['settings'];

            if (!is_array($settings)) {
                continue;
            }

            if (isset($settings['integrationDispatch'])) {
                $settings['integrationDispatch'] = IntegrationDispatchPlan::fromFormSettings($settings['integrationDispatch'])->toSettingsArray();
            }
            $this->update(Table::FORMIE_FORMS, ['settings' => Json::encode($settings)], ['id' => $row['id']]);
        }

        if ($this->db->columnExists(Table::FORMIE_NOTIFICATIONS, 'dispatchTiming')) {
            $this->update(Table::FORMIE_NOTIFICATIONS, ['dispatchTiming' => 'afterFinalizedDeliveryAttempts'], ['dispatchTiming' => 'afterIntegrations']);
        }

        // Keep connection state and provider field metadata used by existing mappings.
        // IntegrationConfig safely reads legacy metadata and marks it stale until refreshed.

        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }
}
