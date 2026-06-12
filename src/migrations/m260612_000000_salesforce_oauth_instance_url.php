<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;
use verbb\formie\integrations\crm\Salesforce;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;

class m260612_000000_salesforce_oauth_instance_url extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%auth_oauth_tokens}}')) {
            return true;
        }

        $integrations = (new Query())
            ->select(['id', 'settings'])
            ->from([Table::FORMIE_INTEGRATIONS])
            ->where(['type' => Salesforce::class])
            ->all();

        foreach ($integrations as $integration) {
            $settings = $integration['settings'] ?? [];

            if (is_string($settings)) {
                $settings = Json::decodeIfJson($settings) ?: [];
            }

            $apiDomain = trim((string)($settings['apiDomain'] ?? ''));

            if ($apiDomain === '') {
                continue;
            }

            $apiDomain = rtrim($apiDomain, '/');

            $token = (new Query())
                ->select(['id', 'values'])
                ->from(['{{%auth_oauth_tokens}}'])
                ->where([
                    'ownerHandle' => 'formie',
                    'reference' => (string)$integration['id'],
                ])
                ->orderBy(['dateCreated' => SORT_DESC])
                ->one();

            if (!$token) {
                continue;
            }

            $values = $token['values'] ?? [];

            if (is_string($values)) {
                $values = Json::decodeIfJson($values) ?: [];
            }

            if (!is_array($values)) {
                $values = [];
            }

            $instanceUrl = trim((string)($values['instance_url'] ?? ''));

            if ($instanceUrl !== '') {
                continue;
            }

            $values['instance_url'] = $apiDomain;

            $this->update('{{%auth_oauth_tokens}}', [
                'values' => Json::encode($values),
                'dateUpdated' => Db::prepareDateForDb(new \DateTime()),
            ], [
                'id' => $token['id'],
            ], [], false);
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260612_000000_salesforce_oauth_instance_url cannot be reverted.\n";

        return false;
    }
}
