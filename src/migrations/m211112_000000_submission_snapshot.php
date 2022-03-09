<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m211112_000000_submission_snapshot extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%formie_submissions}}', 'snapshot')) {
            $this->addColumn('{{%formie_submissions}}', 'snapshot', $this->text()->after('spamReason'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m211112_000000_submission_snapshot cannot be reverted.\n";
        return false;
    }
}
