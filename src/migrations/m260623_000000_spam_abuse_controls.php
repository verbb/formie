<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m260623_000000_spam_abuse_controls extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->addColumn(Table::FORMIE_SPAM_SETTINGS, 'enableSuspiciousTextDetection', $this->boolean()->notNull()->defaultValue(false)->after('formSubmitExpiration'));
        $this->addColumn(Table::FORMIE_SPAM_SETTINGS, 'suspiciousTextMinimumWordLength', $this->integer()->notNull()->defaultValue(6)->after('enableSuspiciousTextDetection'));
        $this->addColumn(Table::FORMIE_SPAM_SETTINGS, 'suspiciousTextAllowedTerms', $this->mediumText()->after('suspiciousTextMinimumWordLength'));
        $this->addColumn(Table::FORMIE_SPAM_SETTINGS, 'enableMaximumLinks', $this->boolean()->notNull()->defaultValue(false)->after('suspiciousTextAllowedTerms'));
        $this->addColumn(Table::FORMIE_SPAM_SETTINGS, 'maximumLinks', $this->integer()->notNull()->defaultValue(10)->after('enableMaximumLinks'));
        $this->addColumn(Table::FORMIE_SPAM_SETTINGS, 'enableGlobalSubmissionThrottling', $this->boolean()->notNull()->defaultValue(false)->after('maximumLinks'));
        $this->addColumn(Table::FORMIE_SPAM_SETTINGS, 'globalSubmissionThrottleLimit', $this->integer()->notNull()->defaultValue(50)->after('enableGlobalSubmissionThrottling'));
        $this->addColumn(Table::FORMIE_SPAM_SETTINGS, 'globalSubmissionThrottleWindowSeconds', $this->integer()->notNull()->defaultValue(60)->after('globalSubmissionThrottleLimit'));
        $this->addColumn(Table::FORMIE_SPAM_SETTINGS, 'enableIpSubmissionThrottling', $this->boolean()->notNull()->defaultValue(false)->after('globalSubmissionThrottleWindowSeconds'));
        $this->addColumn(Table::FORMIE_SPAM_SETTINGS, 'ipSubmissionThrottleMinutes', $this->integer()->notNull()->defaultValue(5)->after('enableIpSubmissionThrottling'));

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropColumn(Table::FORMIE_SPAM_SETTINGS, 'ipSubmissionThrottleMinutes');
        $this->dropColumn(Table::FORMIE_SPAM_SETTINGS, 'enableIpSubmissionThrottling');
        $this->dropColumn(Table::FORMIE_SPAM_SETTINGS, 'globalSubmissionThrottleWindowSeconds');
        $this->dropColumn(Table::FORMIE_SPAM_SETTINGS, 'globalSubmissionThrottleLimit');
        $this->dropColumn(Table::FORMIE_SPAM_SETTINGS, 'enableGlobalSubmissionThrottling');
        $this->dropColumn(Table::FORMIE_SPAM_SETTINGS, 'maximumLinks');
        $this->dropColumn(Table::FORMIE_SPAM_SETTINGS, 'enableMaximumLinks');
        $this->dropColumn(Table::FORMIE_SPAM_SETTINGS, 'suspiciousTextAllowedTerms');
        $this->dropColumn(Table::FORMIE_SPAM_SETTINGS, 'suspiciousTextMinimumWordLength');
        $this->dropColumn(Table::FORMIE_SPAM_SETTINGS, 'enableSuspiciousTextDetection');

        return true;
    }
}
