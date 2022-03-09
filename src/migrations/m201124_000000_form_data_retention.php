<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m201124_000000_form_data_retention extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->alterColumn('{{%formie_forms}}', 'dataRetention', $this->enum('dataRetention', ['forever', 'hours', 'days', 'weeks', 'months', 'years'])
            ->defaultValue('forever')
            ->notNull());

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m201124_000000_form_data_retention cannot be reverted.\n";
        return false;
    }
}
