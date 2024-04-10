<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\fields\Group;
use verbb\formie\fields\Repeater;
use verbb\formie\helpers\Table;
use verbb\formie\models\FieldLayout;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\MigrationHelper;
use craft\migrations\BaseContentRefactorMigration;

use Throwable;

class m240407_000000_payment_fk extends Migration
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
        echo "m240407_000000_payment_fk cannot be reverted.\n";

        return false;
    }
}
