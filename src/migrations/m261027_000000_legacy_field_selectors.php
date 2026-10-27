<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m261027_000000_legacy_field_selectors extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Revisit every persisted reference location for sites that already ran the original migration.
        return (new m260928_010000_reference_contract(['db' => $this->db]))->safeUp();
    }

    public function safeDown(): bool
    {
        echo "m261027_000000_legacy_field_selectors cannot be reverted.\n";

        return false;
    }
}
