<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m260621_000000_submission_guards extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->tableExists(Table::FORMIE_SPAM_SETTINGS)) {
            return true;
        }

        $this->addColumn(Table::FORMIE_SPAM_SETTINGS, 'enableHoneypot', $this->boolean()->notNull()->defaultValue(true)->after('spamKeywords'));
        $this->addColumn(Table::FORMIE_SPAM_SETTINGS, 'honeypotFieldName', $this->string()->notNull()->defaultValue('formieHoneypot')->after('enableHoneypot'));
        $this->addColumn(Table::FORMIE_SPAM_SETTINGS, 'enableMinimumSubmitTime', $this->boolean()->notNull()->defaultValue(true)->after('honeypotFieldName'));
        $this->addColumn(Table::FORMIE_SPAM_SETTINGS, 'minimumSubmitTime', $this->integer()->notNull()->defaultValue(3)->after('enableMinimumSubmitTime'));
        $this->addColumn(Table::FORMIE_SPAM_SETTINGS, 'enableReplayProtection', $this->boolean()->notNull()->defaultValue(true)->after('minimumSubmitTime'));

        return true;
    }

    public function safeDown(): bool
    {
        if (!$this->db->tableExists(Table::FORMIE_SPAM_SETTINGS)) {
            return true;
        }

        $this->dropColumn(Table::FORMIE_SPAM_SETTINGS, 'enableReplayProtection');
        $this->dropColumn(Table::FORMIE_SPAM_SETTINGS, 'minimumSubmitTime');
        $this->dropColumn(Table::FORMIE_SPAM_SETTINGS, 'enableMinimumSubmitTime');
        $this->dropColumn(Table::FORMIE_SPAM_SETTINGS, 'honeypotFieldName');
        $this->dropColumn(Table::FORMIE_SPAM_SETTINGS, 'enableHoneypot');

        return true;
    }
}
