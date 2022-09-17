<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m220905_000000_integration_enabled extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->alterColumn('{{%formie_integrations}}', 'enabled', $this->string()->notNull()->defaultValue('true'));

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220905_000000_integration_enabled cannot be reverted.\n";
        return false;
    }
}
