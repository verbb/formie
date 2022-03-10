<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m210131_000000_form_data_retention extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $items = ['forever', 'minutes', 'hours', 'days', 'weeks', 'months', 'years'];

        if ($this->db->getIsPgsql()) {
            // Manually construct the SQL for Postgres
            $checkSql = '[[dataRetention]] in (' . implode(',', array_map(function(string $item) {
                    return $this->db->quoteValue($item);
                }, $items)) . ')';

            $this->execute("alter table {{%formie_forms}} drop constraint {{%formie_forms_dataRetention_check}}, add check ($checkSql)");
        } else {
            $this->alterColumn('{{%formie_forms}}', 'dataRetention', $this->enum('dataRetention', $items)
                ->defaultValue('forever')
                ->notNull());
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m210131_000000_form_data_retention cannot be reverted.\n";
        return false;
    }
}
