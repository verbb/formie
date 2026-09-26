<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\IntegrationSecrets;
use verbb\formie\helpers\Table;
use verbb\formie\models\IntegrationDispatchPlan;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\ProjectConfig;

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
            $settings['integrations'] = IntegrationSecrets::protect((array)($settings['integrations'] ?? []));
            $this->update(Table::FORMIE_FORMS, ['settings' => Json::encode($settings)], ['id' => $row['id']]);
        }
        if ($this->db->columnExists(Table::FORMIE_NOTIFICATIONS, 'dispatchTiming')) {
            $this->update(Table::FORMIE_NOTIFICATIONS, ['dispatchTiming' => 'afterFinalizedDeliveryAttempts'], ['dispatchTiming' => 'afterIntegrations']);
        }
        foreach ((new Query())->from(Table::FORMIE_INTEGRATIONS)->each() as $row) {
            $settings = is_string($row['settings']) ? Json::decode($row['settings']) : $row['settings'];
            $this->update(Table::FORMIE_INTEGRATIONS, ['settings' => Json::encode(IntegrationSecrets::protect((array)$settings, true)), 'cache' => null], ['id' => $row['id']]);
        }
        $projectConfig = Craft::$app->getProjectConfig();
        foreach ((array)$projectConfig->get('formie.integrations', true) as $uid => $config) {
            $settings = ProjectConfig::unpackAssociativeArrays((array)($config['settings'] ?? []));
            $protected = IntegrationSecrets::protect($settings, true);
            if ($settings !== $protected) {
                $projectConfig->set('formie.integrations.' . $uid . '.settings', ProjectConfig::packAssociativeArrays($protected), 'Encrypt literal integration settings');
            }
        }
        foreach ((new Query())->from(Table::FORMIE_STENCILS)->each() as $row) {
            $data = is_string($row['data']) ? Json::decode($row['data']) : $row['data'];
            if (is_array($data) && isset($data['settings']['integrations'])) {
                $data['settings']['integrations'] = IntegrationSecrets::protect((array)$data['settings']['integrations']);
                $this->update(Table::FORMIE_STENCILS, ['data' => Json::encode($data)], ['id' => $row['id']]);
            }
        }
        foreach ((array)$projectConfig->get('formie.stencils', true) as $uid => $config) {
            $data = ProjectConfig::unpackAssociativeArrays((array)($config['data'] ?? []));
            if (isset($data['settings']['integrations'])) {
                $data['settings']['integrations'] = IntegrationSecrets::protect((array)$data['settings']['integrations']);
                $projectConfig->set('formie.stencils.' . $uid . '.data', ProjectConfig::packAssociativeArrays($data), 'Encrypt literal stencil integration settings');
            }
        }
        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }
}
