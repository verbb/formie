<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m211104_000000_sentnotifications_message extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%formie_sentnotifications}}', 'message')) {
            $this->addColumn('{{%formie_sentnotifications}}', 'message', $this->text()->after('info'));
        }

        if (!$this->db->columnExists('{{%formie_sentnotifications}}', 'success')) {
            $this->addColumn('{{%formie_sentnotifications}}', 'success', $this->boolean()->after('info'));
        }

        $this->db->createCommand()
            ->update('{{%formie_sentnotifications}}', ['success' => true])
            ->execute();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m211104_000000_sentnotifications_message cannot be reverted.\n";
        return false;
    }
}
