<?php
namespace verbb\formie\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\StringHelper;

class m250516_000000_payment_redirect extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%formie_payments}}', 'redirectUrl')) {
            $this->addColumn('{{%formie_payments}}', 'redirectUrl', $this->text()->after('message'));
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m250516_000000_payment_redirect cannot be reverted.\n";
        return false;
    }
}
