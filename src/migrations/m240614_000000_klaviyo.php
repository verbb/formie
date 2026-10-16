<?php
namespace verbb\formie\migrations;

use verbb\formie\integrations\crm\Klaviyo as KlaviyoCRM;
use verbb\formie\integrations\emailmarketing\Klaviyo as KlaviyoEM;

use craft\db\Migration;

class m240614_000000_klaviyo extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // These are historical stored type names; the legacy classes no longer ship.
        $this->update('{{%formie_integrations}}', ['type' => 'verbb\\formie\\integrations\\emailmarketing\\KlaviyoLegacy'], ['type' => KlaviyoEM::class]);
        $this->update('{{%formie_integrations}}', ['type' => 'verbb\\formie\\integrations\\crm\\KlaviyoLegacy'], ['type' => KlaviyoCRM::class]);

        return true;
    }

    public function safeDown(): bool
    {
        echo "m240614_000000_klaviyo cannot be reverted.\n";
        return false;
    }
}
