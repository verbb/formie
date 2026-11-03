<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m261103_000002_legacy_integration_mappings extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Revisit mappings on sites that already ran the original migrations.
        return (new m260926_000000_reference_slots(['db' => $this->db]))->safeUp()
            && (new m260928_010000_reference_contract(['db' => $this->db]))->safeUp();
    }

    public function safeDown(): bool
    {
        echo "m261103_000002_legacy_integration_mappings cannot be reverted.\n";

        return false;
    }
}
