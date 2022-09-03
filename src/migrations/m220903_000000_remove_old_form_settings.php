<?php
namespace verbb\formie\migrations;

use verbb\formie\integrations\crm\HubSpot;
use verbb\formie\integrations\crm\HubSpotLegacy;

use craft\db\Migration;

class m220903_000000_remove_old_form_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if ($this->db->columnExists('{{%formie_forms}}', 'requireUser')) {
            $this->dropColumn('{{%formie_forms}}', 'requireUser');
        }

        if ($this->db->columnExists('{{%formie_forms}}', 'availability')) {
            $this->dropColumn('{{%formie_forms}}', 'availability');
        }

        if ($this->db->columnExists('{{%formie_forms}}', 'availabilityFrom')) {
            $this->dropColumn('{{%formie_forms}}', 'availabilityFrom');
        }

        if ($this->db->columnExists('{{%formie_forms}}', 'availabilityTo')) {
            $this->dropColumn('{{%formie_forms}}', 'availabilityTo');
        }

        if ($this->db->columnExists('{{%formie_forms}}', 'availabilitySubmissions')) {
            $this->dropColumn('{{%formie_forms}}', 'availabilitySubmissions');
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220903_000000_remove_old_form_settings cannot be reverted.\n";
        return false;
    }
}
