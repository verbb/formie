<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m220904_000000_add_siteid_entry_redirect extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%formie_forms}}', 'submitActionEntrySiteId')) {
            $this->addColumn('{{%formie_forms}}', 'submitActionEntrySiteId', $this->integer()->after('submitActionEntryId'));
        }

        if (!$this->db->columnExists('{{%formie_stencils}}', 'submitActionEntrySiteId')) {
            $this->addColumn('{{%formie_stencils}}', 'submitActionEntrySiteId', $this->integer()->after('submitActionEntryId'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220904_000000_add_siteid_entry_redirect cannot be reverted.\n";
        return false;
    }
}
