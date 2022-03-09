<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m201119_000000_increase_stencil extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->alterColumn('{{%formie_forms}}', 'settings', $this->mediumText());
        $this->alterColumn('{{%formie_stencils}}', 'data', $this->mediumText());

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m201119_000000_increase_stencil cannot be reverted.\n";
        return false;
    }
}
