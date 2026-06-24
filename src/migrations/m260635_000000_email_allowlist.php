<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m260635_000000_email_allowlist extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->tableExists(Table::FORMIE_SPAM_SETTINGS)) {
            return true;
        }

        $this->addColumn(Table::FORMIE_SPAM_SETTINGS, 'enableAllowedEmailDomains', $this->boolean()->notNull()->defaultValue(false)->after('enableBlockFreeEmailDomains'));
        $this->addColumn(Table::FORMIE_SPAM_SETTINGS, 'allowedEmailDomains', $this->mediumText()->after('enableAllowedEmailDomains'));

        return true;
    }

    public function safeDown(): bool
    {
        if (!$this->db->tableExists(Table::FORMIE_SPAM_SETTINGS)) {
            return true;
        }

        $this->dropColumn(Table::FORMIE_SPAM_SETTINGS, 'allowedEmailDomains');
        $this->dropColumn(Table::FORMIE_SPAM_SETTINGS, 'enableAllowedEmailDomains');

        return true;
    }
}
