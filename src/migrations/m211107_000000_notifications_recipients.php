<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m211107_000000_notifications_recipients extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%formie_notifications}}', 'recipients')) {
            $this->addColumn('{{%formie_notifications}}', 'recipients', $this->enum('recipients', ['email', 'conditions'])
                ->defaultValue('email')
                ->notNull()
                ->after('subject'));
        }

        if (!$this->db->columnExists('{{%formie_notifications}}', 'toConditions')) {
            $this->addColumn('{{%formie_notifications}}', 'toConditions', $this->text()->after('to'));
        }

        $this->db->createCommand()
            ->update('{{%formie_notifications}}', ['recipients' => 'email'])
            ->execute();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m211107_000000_notifications_recipients cannot be reverted.\n";
        return false;
    }
}
