<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m260928_000000_payment_external_boundaries extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $receiptColumns = [
            'integrationUid' => $this->uid(),
            'accountFingerprint' => $this->char(64),
            'eventType' => $this->string(255),
            'resourceType' => $this->string(255),
            'resourceReference' => $this->string(255),
            'providerCreatedAt' => $this->bigInteger(),
            'payload' => $this->mediumText(),
            'verifiedAt' => $this->dateTime(),
            'scheduledAt' => $this->dateTime(),
            'startedAt' => $this->dateTime(),
            'nextAttemptAt' => $this->dateTime(),
        ];

        foreach ($receiptColumns as $name => $type) {
            if (!$this->db->columnExists(Table::FORMIE_WEBHOOK_RECEIPTS, $name)) {
                $this->addColumn(Table::FORMIE_WEBHOOK_RECEIPTS, $name, $type);
            }
        }

        // Preserve beta receipt evidence for support, but do not attempt to
        // replay rows created before adapters produced normalized events.
        foreach ((new Query())->from(Table::FORMIE_WEBHOOK_RECEIPTS)->each() as $row) {
            $integrationUid = (new Query())
                ->select('uid')
                ->from(Table::FORMIE_INTEGRATIONS)
                ->where(['id' => $row['integrationId']])
                ->scalar();

            if (!is_string($integrationUid) || $integrationUid === '') {
                $hex = md5('legacy-payment-integration|' . $row['integrationId']);
                $integrationUid = substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20, 12);
            }

            $history = Json::decodeIfJson((string)$row['history']);
            $history = is_array($history) ? $history : [];
            $status = in_array($row['status'], ['processed', 'ignored'], true) ? $row['status'] : 'ignored';

            if ($status === 'ignored' && $row['status'] !== 'ignored') {
                $history[] = ['status' => 'ignored', 'at' => gmdate('c'), 'reason' => 'legacy receipt retained without normalized payload'];
            }

            $this->update(Table::FORMIE_WEBHOOK_RECEIPTS, [
                'integrationUid' => $integrationUid,
                'accountFingerprint' => hash('sha256', 'legacy|' . $row['integrationId'] . '|' . $row['environment']),
                'eventType' => 'legacy.unknown',
                'payload' => $row['body'],
                'verifiedAt' => $row['receivedAt'],
                'status' => $status,
                'history' => Json::encode(array_slice($history, -100)),
            ], ['id' => $row['id']]);
        }

        foreach ([
            'integrationUid' => $this->uid()->notNull(),
            'accountFingerprint' => $this->char(64)->notNull(),
            'eventType' => $this->string(255)->notNull(),
            'payload' => $this->mediumText()->notNull(),
            'verifiedAt' => $this->dateTime()->notNull(),
        ] as $name => $type) {
            $this->alterColumn(Table::FORMIE_WEBHOOK_RECEIPTS, $name, $type);
        }

        foreach (['lastReconciledAt', 'nextReconcileAt'] as $name) {
            if (!$this->db->columnExists(Table::FORMIE_PAYMENTS, $name)) {
                $this->addColumn(Table::FORMIE_PAYMENTS, $name, $this->bigInteger());
            }
        }

        if (!$this->db->columnExists(Table::FORMIE_PAYMENTS, 'reconciliationAttempts')) {
            $this->addColumn(Table::FORMIE_PAYMENTS, 'reconciliationAttempts', $this->integer()->notNull()->defaultValue(0));
        }

        // These capabilities were introduced during the 4.x beta. Their purpose
        // vocabulary changed before the stable contract, so stale beta bearers
        // must not cross the new authority boundary.
        $this->delete(Table::FORMIE_PAYMENT_CAPABILITIES);
        $this->createIndex(null, Table::FORMIE_WEBHOOK_RECEIPTS, ['status', 'nextAttemptAt'], false);
        $this->createIndex(null, Table::FORMIE_WEBHOOK_RECEIPTS, ['integrationUid', 'eventId'], false);

        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }
}
