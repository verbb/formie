<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m220219_000000_fieldContentTable extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Factor in `{{$fmc_*}}`
        $this->alterColumn('{{%formie_forms}}', 'fieldContentTable', $this->string(74)->notNull());

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220219_000000_fieldContentTable cannot be reverted.\n";
        return false;
    }
}
