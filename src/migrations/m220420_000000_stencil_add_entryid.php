<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m220420_000000_stencil_add_entryid extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%formie_stencils}}', 'submitActionEntryId')) {
            $this->addColumn('{{%formie_stencils}}', 'submitActionEntryId', $this->integer()->after('templateId'));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m220420_000000_stencil_add_entryid cannot be reverted.\n";
        return false;
    }
}
