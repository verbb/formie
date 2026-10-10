<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\OperationalSecurity;

use craft\db\Migration;
use craft\db\Query;

class m261010_020000_operational_cryptography extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->_reencrypt('{{%formie_delivery_attempts}}', ['data', 'response']);
        $this->_reencrypt('{{%formie_delivery_diagnostics}}', ['data']);
        $this->_reencrypt('{{%formie_webhookreceipts}}', ['body', 'headers', 'payload']);
        $this->_reencrypt('{{%formie_subscription_diagnostics}}', ['evidence']);

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261010_020000_operational_cryptography cannot restore the former operational key.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _reencrypt(string $table, array $columns): void
    {
        if (!$this->db->tableExists($table)) {
            return;
        }

        foreach ((new Query())->select(['id', ...$columns])->from($table)->each() as $row) {
            $updates = [];

            foreach ($columns as $column) {
                $value = $row[$column] ?? null;

                if (!is_string($value) || $value === '') {
                    continue;
                }

                $plain = OperationalSecurity::decrypt($value);

                if ($plain !== false) {
                    $updates[$column] = OperationalSecurity::encrypt($plain);
                }
            }

            if ($updates) {
                $condition = ['id' => $row['id']];

                foreach (array_keys($updates) as $column) {
                    $condition[$column] = $row[$column];
                }

                // A queue worker may finish the attempt while this migration is
                // scanning. Never replace newer evidence with the stale snapshot.
                $this->update($table, $updates, $condition);
            }
        }
    }
}
