<?php
namespace verbb\formie\migrations;

use verbb\formie\integrations\emailmarketing\Klaviyo as KlaviyoEM;
use verbb\formie\integrations\emailmarketing\KlaviyoLegacy as KlaviyoEMLegacy;
use verbb\formie\integrations\crm\Klaviyo as KlaviyoCRM;
use verbb\formie\integrations\crm\KlaviyoLegacy as KlaviyoCRMLegacy;

use craft\db\Migration;

class m240614_000000_klaviyo extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->update('{{%formie_integrations}}', ['type' => KlaviyoEMLegacy::class], ['type' => KlaviyoEM::class]);
        $this->update('{{%formie_integrations}}', ['type' => KlaviyoCRMLegacy::class], ['type' => KlaviyoCRM::class]);

        return true;
    }

    public function safeDown(): bool
    {
        echo "m240614_000000_klaviyo cannot be reverted.\n";
        return false;
    }
}
