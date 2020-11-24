<?php
namespace verbb\formie\migrations;

use verbb\formie\elements\Form;
use verbb\formie\fields\formfields\Phone;
use verbb\formie\prosemirror\toprosemirror\Renderer;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;

class m201124_000000_form_data_retention extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn('{{%formie_forms}}', 'dataRetention', $this->enum('dataRetention', ['forever', 'hours', 'days', 'weeks', 'months', 'years'])
            ->defaultValue('forever')
            ->notNull());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m201124_000000_form_data_retention cannot be reverted.\n";
        return false;
    }
}
