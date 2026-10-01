<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\helpers\SubscriptionProviderData;
use verbb\formie\helpers\Table;
use verbb\formie\models\PaymentMoney;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m260929_010000_payment_contracts extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $definitions = [
            Table::FORMIE_PAYMENTS => ['amountMinor' => $this->string(80), 'accountFingerprint' => $this->char(64)],
            Table::FORMIE_PAYMENT_PLANS => ['accountFingerprint' => $this->char(64), 'amountMinor' => $this->string(80)->notNull()->defaultValue('0'), 'currency' => $this->string(3), 'interval' => $this->string(16), 'intervalCount' => $this->integer()->notNull()->defaultValue(1), 'providerStatus' => $this->string(80)],
            Table::FORMIE_SUBSCRIPTIONS => ['accountFingerprint' => $this->char(64), 'terms' => $this->text(), 'providerData' => $this->text(), 'lastSyncedAt' => $this->dateTime()],
        ];

        foreach ($definitions as $table => $columns) {
            foreach ($columns as $name => $definition) {
                if (!$this->db->columnExists($table, $name)) {
                    $this->addColumn($table, $name, $definition);
                }
            }
        }

        if (!$this->db->tableExists('{{%formie_subscription_diagnostics}}')) {
            $this->createTable('{{%formie_subscription_diagnostics}}', [
                'id' => $this->primaryKey(), 'subscriptionUid' => $this->uid()->notNull(),
                'status' => $this->string(32)->notNull(), 'evidence' => $this->mediumText()->notNull(), 'observedAt' => $this->dateTime()->notNull(),
            ]);
            $this->createIndex(null, '{{%formie_subscription_diagnostics}}', ['subscriptionUid', 'observedAt']);
        }

        foreach ((new Query())->from(Table::FORMIE_PAYMENTS)->each() as $row) {
            $changes = ['status' => match ($row['status']) {
                'success' => 'succeeded', 'redirect' => 'requiresAction', default => $row['status']
            }];

            if ($row['amountMinor'] === null) {
                // Decimal strings are converted exactly with the currency's actual exponent.
                $changes['amountMinor'] = PaymentMoney::fromDecimal((string)$row['amount'], (string)$row['currency'])->minor;
            }

            foreach (['scope', 'history'] as $key) {
                $changes[$key] = Json::encode(self::normalizeStatus((array)Json::decodeIfJson($row[$key])));
            }
            $this->update(Table::FORMIE_PAYMENTS, $changes, ['id' => $row['id']], [], false);
        }

        foreach ($this->db->getSchema()->findUniqueIndexes($this->db->getTableSchema(Table::FORMIE_PAYMENT_PLANS, true)) as $name => $columns) {
            if ($columns === ['handle']) {
                $this->dropIndex($name, Table::FORMIE_PAYMENT_PLANS);
            }
        }

        foreach ((new Query())->from(Table::FORMIE_PAYMENT_PLANS)->each() as $row) {
            $data = (array)Json::decodeIfJson($row['planData']);
            $this->update(Table::FORMIE_PAYMENT_PLANS, [
                'amountMinor' => (string)($data['amount'] ?? $row['amountMinor']), 'currency' => isset($data['currency']) ? strtoupper($data['currency']) : $row['currency'],
                'interval' => $data['interval'] ?? $row['interval'], 'intervalCount' => $data['interval_count'] ?? $row['intervalCount'],
                'planData' => Json::encode(array_intersect_key($data, array_flip(['amount', 'currency', 'interval', 'interval_count']))),
            ], ['id' => $row['id']], [], false);
        }

        if ($this->db->columnExists(Table::FORMIE_SUBSCRIPTIONS, 'subscriptionData')) {
            foreach ((new Query())->from(Table::FORMIE_SUBSCRIPTIONS)->each() as $row) {
                $data = (array)Json::decodeIfJson($row['subscriptionData']);
                Formie::$plugin->getSubscriptions()->retainProviderEvidence($row['uid'], $row['status'], $data);
                $changes = ['providerData' => Json::encode(SubscriptionProviderData::project($data))];

                // Stripe's previous period-end fallback did not establish an actual charge date.
                if (isset($data['current_period_end'])) {
                    $changes['nextPaymentAt'] = null;
                }

                if ($row['planId']) {
                    $plan = (new Query())->from(Table::FORMIE_PAYMENT_PLANS)->where(['id' => $row['planId']])->one();

                    if ($plan) {
                        $changes['terms'] = Json::encode(array_intersect_key($plan, array_flip(['amountMinor', 'currency', 'interval', 'intervalCount'])));
                    }
                }
                $this->update(Table::FORMIE_SUBSCRIPTIONS, $changes, ['id' => $row['id']], [], false);
            }
            $this->dropColumn(Table::FORMIE_SUBSCRIPTIONS, 'subscriptionData');
        }

        foreach ([Table::FORMIE_PAYMENTS, Table::FORMIE_SUBSCRIPTIONS, Table::FORMIE_PAYMENT_PLANS] as $table) {
            $name = substr(str_replace(['{{%', '}}'], '', $table), 0, 40) . '_account_reference';
            $indexes = $this->db->getSchema()->getTableIndexes($table, true);

            if (!array_filter($indexes, fn($index) => $index->columnNames === ['integrationId', 'accountFingerprint', 'reference'])) {
                $this->createIndex($name, $table, ['integrationId', 'accountFingerprint', 'reference']);
            }
        }
        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }


    // Static Methods
    // =========================================================================

    public static function normalizeStatus(array $value): array
    {
        foreach ($value as $key => $item) {
            $value[$key] = is_array($item) ? self::normalizeStatus($item) : ($key === 'status' ? match ($item) {
                'success' => 'succeeded', 'redirect' => 'requiresAction', default => $item
            } : $item);
        }
        return $value;
    }
}
