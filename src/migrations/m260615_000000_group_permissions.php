<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m260615_000000_group_permissions extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Existing per-form grants remain supported. Converting one form grant to
        // its group would also grant access to previously restricted sibling forms.
        return true;
    }

    public function safeDown(): bool
    {
        echo "m260615_000000_group_permissions cannot be reverted.\n";

        return false;
    }
}
