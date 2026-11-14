<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m261115_000000_legacy_to_string_tokens extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Formie 3 offered fields with sub-fields, such as Name and Address, as {field:handle.__toString}. Sites
        // that upgraded before those were read as the whole field still hold them, and they fail to resolve.
        return (new m260928_010000_reference_contract(['db' => $this->db]))->safeUp();
    }

    public function safeDown(): bool
    {
        echo "m261115_000000_legacy_to_string_tokens cannot be reverted.\n";

        return false;
    }
}
