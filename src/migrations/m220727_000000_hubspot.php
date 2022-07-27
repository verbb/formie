<?php
namespace verbb\formie\migrations;

use verbb\formie\integrations\crm\HubSpot;
use verbb\formie\integrations\crm\HubSpotLegacy;

use craft\db\Migration;

class m220727_000000_hubspot extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->update('{{%formie_integrations}}', ['type' => HubSpotLegacy::class], ['type' => HubSpot::class]);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220727_000000_hubspot cannot be reverted.\n";
        return false;
    }
}
