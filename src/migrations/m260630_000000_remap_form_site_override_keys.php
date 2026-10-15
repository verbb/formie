<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m260630_000000_remap_form_site_override_keys extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Deferred to m261016_000000_migrate_form_site_content, after the Form schema is complete.
        return true;
    }

    public function safeDown(): bool
    {
        return true;
    }
}
