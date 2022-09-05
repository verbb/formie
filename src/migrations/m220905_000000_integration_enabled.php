<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m220905_000000_integration_enabled extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn('{{%formie_integrations}}', 'enabled', $this->string()->notNull()->defaultValue('true'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m220905_000000_integration_enabled cannot be reverted.\n";
        return false;
    }
}
