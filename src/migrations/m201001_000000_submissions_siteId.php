<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m201001_000000_submissions_siteId extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%formie_submissions}}', 'siteId')) {
            $this->addColumn('{{%formie_submissions}}', 'siteId', $this->string()->after('formId'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m201001_000000_submissions_siteId cannot be reverted.\n";
        return false;
    }
}