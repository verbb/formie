<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m261115_040000_recipients_notification_tokens extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Formie 3 notifications could use a Recipients field's `{handle.label}` and `{handle.value}`. Formie 4 had no
        // such selectors, so sites that upgraded before them kept tokens that fail to resolve.
        // The stencil repair runs the notification repair first, then converts saved stencils.
        return (new m261105_060000_stencil_notification_tokens(['db' => $this->db]))->safeUp();
    }

    public function safeDown(): bool
    {
        echo "m261115_040000_recipients_notification_tokens cannot be reverted.\n";

        return false;
    }
}
