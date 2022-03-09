<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m210530_000000_sentnotifications_body extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->alterColumn('{{%formie_sentnotifications}}', 'body', $this->mediumText());
        $this->alterColumn('{{%formie_sentnotifications}}', 'htmlBody', $this->mediumText());

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m210530_000000_sentnotifications_body cannot be reverted.\n";
        return false;
    }
}
