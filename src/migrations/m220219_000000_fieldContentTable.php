<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Db;

class m220219_000000_fieldContentTable extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Factor in `{{$fmc_*}}`
        $this->alterColumn('{{%formie_forms}}', 'fieldContentTable', $this->string(74)->notNull());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m220219_000000_fieldContentTable cannot be reverted.\n";
        return false;
    }
}
