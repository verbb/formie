<?php
namespace verbb\formie\migrations;

use Craft;
use craft\db\Migration;

/**
 * m200724_000000_submissions_ipAddress migration.
 */
class m200724_000000_submissions_ipAddress extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%formie_submissions}}', 'ipAddress')) {
            $this->addColumn('{{%formie_submissions}}', 'ipAddress', $this->string()->after('spamReason'));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200724_000000_submissions_ipAddress cannot be reverted.\n";
        return false;
    }
}