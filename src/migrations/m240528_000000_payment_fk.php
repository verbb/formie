<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m240528_000000_payment_fk extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->dropForeignKeyIfExists(Table::FORMIE_PAYMENTS, ['fieldId']);
        $this->dropForeignKeyIfExists(Table::FORMIE_SUBSCRIPTIONS, ['fieldId']);

        $this->addForeignKey(null, Table::FORMIE_PAYMENTS, ['fieldId'], Table::FORMIE_FIELDS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_SUBSCRIPTIONS, ['fieldId'], Table::FORMIE_FIELDS, ['id'], 'RESTRICT', null);

        return true;
    }

    public function safeDown(): bool
    {
        echo "m240528_000000_payment_fk cannot be reverted.\n";

        return false;
    }
}
