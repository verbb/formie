<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m260622_000000_spam_screening_followups extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->tableExists(Table::FORMIE_SPAM_SETTINGS)) {
            return true;
        }

        $this->addColumn(Table::FORMIE_SPAM_SETTINGS, 'enableBlockedEmailDomains', $this->boolean()->notNull()->defaultValue(false)->after('enableReplayProtection'));
        $this->addColumn(Table::FORMIE_SPAM_SETTINGS, 'blockedEmailDomains', $this->mediumText()->after('enableBlockedEmailDomains'));
        $this->addColumn(Table::FORMIE_SPAM_SETTINGS, 'enableBlockFreeEmailDomains', $this->boolean()->notNull()->defaultValue(false)->after('blockedEmailDomains'));
        $this->addColumn(Table::FORMIE_SPAM_SETTINGS, 'enableFormSubmitExpiration', $this->boolean()->notNull()->defaultValue(false)->after('enableBlockFreeEmailDomains'));
        $this->addColumn(Table::FORMIE_SPAM_SETTINGS, 'formSubmitExpiration', $this->integer()->notNull()->defaultValue(86400)->after('enableFormSubmitExpiration'));

        return true;
    }

    public function safeDown(): bool
    {
        if (!$this->db->tableExists(Table::FORMIE_SPAM_SETTINGS)) {
            return true;
        }

        $this->dropColumn(Table::FORMIE_SPAM_SETTINGS, 'formSubmitExpiration');
        $this->dropColumn(Table::FORMIE_SPAM_SETTINGS, 'enableFormSubmitExpiration');
        $this->dropColumn(Table::FORMIE_SPAM_SETTINGS, 'enableBlockFreeEmailDomains');
        $this->dropColumn(Table::FORMIE_SPAM_SETTINGS, 'blockedEmailDomains');
        $this->dropColumn(Table::FORMIE_SPAM_SETTINGS, 'enableBlockedEmailDomains');

        return true;
    }
}
