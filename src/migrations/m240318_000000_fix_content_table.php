<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m240318_000000_fix_content_table extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if ($this->db->columnExists(Table::FORMIE_FORMS, 'fieldContentTable')) {
            $this->dropColumn(Table::FORMIE_FORMS, 'fieldContentTable');
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m240318_000000_fix_content_table cannot be reverted.\n";

        return false;
    }
}
