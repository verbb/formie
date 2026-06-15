<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m260624_000000_drop_suspicious_text_minimum_word_length extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if ($this->db->columnExists(Table::FORMIE_SPAM_SETTINGS, 'suspiciousTextMinimumWordLength')) {
            $this->dropColumn(Table::FORMIE_SPAM_SETTINGS, 'suspiciousTextMinimumWordLength');
        }

        return true;
    }

    public function safeDown(): bool
    {
        if (!$this->db->columnExists(Table::FORMIE_SPAM_SETTINGS, 'suspiciousTextMinimumWordLength')) {
            $this->addColumn(
                Table::FORMIE_SPAM_SETTINGS,
                'suspiciousTextMinimumWordLength',
                $this->integer()->notNull()->defaultValue(6)->after('enableSuspiciousTextDetection'),
            );
        }

        return true;
    }
}
