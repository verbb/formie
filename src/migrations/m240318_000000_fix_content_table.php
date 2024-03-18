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

class m240318_000000_fix_content_table extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if ($this->db->columnExists(Table::FORMIE_FORMS, 'fieldContentTable')) {
            $this->dropColumn(Table::FORMIE_FORMS, 'fieldContentTable');
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m240318_000000_fix_content_table cannot be reverted.\n";

        return false;
    }
}
