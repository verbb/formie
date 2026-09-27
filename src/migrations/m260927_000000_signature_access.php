<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m260927_000000_signature_access extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->columnExists(Table::FORMIE_SUBMISSIONS, 'signatureAccessKey')) {
            $this->addColumn(Table::FORMIE_SUBMISSIONS, 'signatureAccessKey', $this->string(64)->after('ipAddress'));
        }

        return true;
    }

    public function safeDown(): bool
    {
        if ($this->db->columnExists(Table::FORMIE_SUBMISSIONS, 'signatureAccessKey')) {
            $this->dropColumn(Table::FORMIE_SUBMISSIONS, 'signatureAccessKey');
        }

        return true;
    }
}
