<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;

use craft\db\Migration;
use craft\db\Query;
use Throwable;

class m260214_000000_field_references extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $table = $this->db->tableExists(Table::FORMIE_FORM_FIELDS) ? Table::FORMIE_FORM_FIELDS : Table::FORMIE_FIELDS;

        if (!$this->db->columnExists($table, 'reference')) {
            $this->addColumn($table, 'reference', $this->string(36)->after('settings'));
        }

        $rows = (new Query())
            ->select(['id', 'reference'])
            ->from($table)
            ->all();

        foreach ($rows as $row) {
            if (!empty($row['reference'])) {
                continue;
            }

            $this->update($table, [
                'reference' => StringHelper::UUID(),
            ], ['id' => $row['id']], [], false);
        }

        try {
            $this->createIndex(null, $table, 'reference', false);
        } catch (Throwable) {
            // Index may already exist in some environments.
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260214_000000_field_references cannot be reverted.\n";

        return false;
    }
}
