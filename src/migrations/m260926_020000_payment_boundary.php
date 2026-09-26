<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;
use verbb\formie\models\PaymentMoney;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

use Throwable;

class m260926_020000_payment_boundary extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Keep historical decimal text exactly, including values whose scale needs review.
        $this->alterColumn(Table::FORMIE_PAYMENTS, 'amount', $this->string(80));
        $this->alterColumn(Table::FORMIE_PAYMENTS, 'status', $this->string(32)->notNull());
        foreach ([Table::FORMIE_PAYMENTS, Table::FORMIE_SUBSCRIPTIONS] as $table) {
            foreach ([
                'version' => $this->integer()->notNull()->defaultValue(0),
                'history' => $this->mediumText(),
                'scope' => $this->text(),
                'idempotencyKey' => $this->string(80),
            ] as $column => $type) {
                if (!$this->db->columnExists($table, $column)) {
                    $this->addColumn($table, $column, $type);
                }
            }
            $this->createIndex(null, $table, 'idempotencyKey', true);
        }
        foreach ([Table::FORMIE_PAYMENTS, Table::FORMIE_SUBSCRIPTIONS] as $table) {
            foreach ((new Query())->from($table)->each() as $row) {
                $scope = array_intersect_key($row, array_flip(['integrationId', 'submissionId', 'fieldId', 'subscriptionId', 'planId', 'reference', 'amount', 'currency']));
                $scope['legacy'] = true;
                $values = ['scope' => Json::encode($scope), 'idempotencyKey' => bin2hex(random_bytes(24))];
                if ($table === Table::FORMIE_PAYMENTS) {
                    $values['status'] = in_array($row['status'], ['success', 'failed', 'cancelled'], true) ? $row['status'] : 'unknown';
                    try {
                        PaymentMoney::fromDecimal((string)$row['amount'], (string)$row['currency']);
                    } catch (Throwable) {
                        $values['status'] = 'unknown';
                    }
                    $values['history'] = Json::encode([['status' => $values['status'], 'reason' => 'legacy migration', 'legacyStatus' => $row['status']]]);
                }
                $this->update($table, $values, ['id' => $row['id']]);
            }
        }
        $this->addColumn(Table::FORMIE_SUBSCRIPTIONS, 'status', $this->string(32)->notNull()->defaultValue('unknown'));
        $this->addColumn(Table::FORMIE_SUBSCRIPTIONS, 'archivedAt', $this->dateTime());
        $this->addColumn(Table::FORMIE_SUBSCRIPTIONS, 'providerUpdatedAt', $this->bigInteger());
        $this->alterColumn(Table::FORMIE_SUBSCRIPTIONS, 'reference', $this->string());
        foreach ((new Query())->from(Table::FORMIE_SUBSCRIPTIONS)->each() as $row) {
            $flags = (int)(bool)$row['isExpired'] + (int)(bool)$row['isCanceled'] + (int)(bool)$row['isSuspended'];
            $status = $flags > 1 ? 'unknown' : ($row['isExpired'] ? 'expired' : ($row['isCanceled'] ? 'cancelled' : ($row['isSuspended'] ? 'suspended' : ($row['hasStarted'] ? 'active' : 'pending'))));
            $this->update(Table::FORMIE_SUBSCRIPTIONS, ['status' => $status, 'history' => Json::encode([['status' => $status, 'reason' => 'legacy migration', 'flags' => array_intersect_key($row, array_flip(['hasStarted', 'isExpired', 'isCanceled', 'isSuspended']))]])], ['id' => $row['id']]);
        }
        foreach (['hasStarted', 'isSuspended', 'isCanceled', 'isExpired'] as $column) {
            $this->dropColumn(Table::FORMIE_SUBSCRIPTIONS, $column);
        }
        // Financial history survives deletion of its former owning configuration/content.
        foreach ([Table::FORMIE_PAYMENTS, Table::FORMIE_SUBSCRIPTIONS] as $table) {
            $schema = $this->db->getTableSchema($table, true);
            foreach ($schema->foreignKeys as $name => $fk) {
                $column = array_key_first(array_diff_key($fk, [0 => true]));
                if (in_array($column, ['submissionId', 'subscriptionId', 'fieldId', 'integrationId', 'planId'], true)) {
                    $this->dropForeignKey($name, $table);
                    $this->alterColumn($table, $column, $this->integer());
                    $this->addForeignKey(null, $table, $column, $fk[0], $fk[$column], 'SET NULL');
                }
            }
        }
        $this->createTable(Table::FORMIE_WEBHOOK_RECEIPTS, [
            'id' => $this->primaryKey(),
            'identity' => $this->char(64)->notNull(),
            'integrationId' => $this->integer()->notNull(),
            'environment' => $this->string(80)->notNull(),
            'eventId' => $this->string(255)->notNull(),
            'bodyHash' => $this->char(64)->notNull(),
            'eventHash' => $this->char(64)->notNull(),
            'history' => $this->mediumText()->notNull(),
            'body' => $this->mediumText()->notNull(),
            'headers' => $this->text()->notNull(),
            'display' => $this->mediumText()->notNull(),
            'status' => $this->string(32)->notNull(),
            'attempts' => $this->integer()->notNull()->defaultValue(0),
            'error' => $this->text(),
            'receivedAt' => $this->dateTime()->notNull(),
            'processedAt' => $this->dateTime(),
        ]);
        $this->createIndex(null, Table::FORMIE_WEBHOOK_RECEIPTS, 'identity', true);
        $this->createTable(Table::FORMIE_PAYMENT_CAPABILITIES, [
            'id' => $this->primaryKey(),
            'tokenHash' => $this->char(64)->notNull(),
            'purpose' => $this->string(32)->notNull(),
            'resourceId' => $this->integer()->notNull(),
            'scope' => $this->text()->notNull(),
            'expiresAt' => $this->integer()->notNull(),
            'revokedAt' => $this->integer(),
        ]);
        $this->createIndex(null, Table::FORMIE_PAYMENT_CAPABILITIES, 'tokenHash', true);
        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }
}
