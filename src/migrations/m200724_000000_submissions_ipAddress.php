<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m200724_000000_submissions_ipAddress extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%formie_submissions}}', 'ipAddress')) {
            $this->addColumn('{{%formie_submissions}}', 'ipAddress', $this->string()->after('spamReason'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m200724_000000_submissions_ipAddress cannot be reverted.\n";
        return false;
    }
}