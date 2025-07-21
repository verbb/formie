<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m220917_000000_submission_spamclass extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%formie_submissions}}', 'spamClass')) {
            $this->addColumn('{{%formie_submissions}}', 'spamClass', $this->string()->after('spamReason'));
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m220917_000000_submission_spamclass cannot be reverted.\n";
        return false;
    }
}
