<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;
use craft\db\Query;

class m260306_000006_field_reference_uniqueness extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $table = $this->db->tableExists(Table::FORMIE_FORM_FIELDS) ? Table::FORMIE_FORM_FIELDS : Table::FORMIE_FIELDS;

        $duplicates = (new Query())
            ->select([
                'reference',
                'count' => 'COUNT(*)',
            ])
            ->from($table)
            ->where(['not', ['reference' => null]])
            ->andWhere(['!=', 'reference', ''])
            ->groupBy(['reference'])
            ->having(['>', 'COUNT(*)', 1])
            ->all();

        if ($duplicates) {
            $examples = array_map(static fn(array $row) => sprintf('%s (%s)', $row['reference'], $row['count']), array_slice($duplicates, 0, 10));
            $message = 'Cannot add unique index for field references because duplicate values were found in `' . $table . '`: ' . implode(', ', $examples) . '.';

            if (count($duplicates) > 10) {
                $message .= ' Additional duplicate references exist.';
            }

            throw new \RuntimeException($message);
        }

        $this->createIndex(
            'formie_fields_reference_unq',
            $table,
            'reference',
            true
        );

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260306_000006_field_reference_uniqueness cannot be reverted.\n";

        return false;
    }
}
