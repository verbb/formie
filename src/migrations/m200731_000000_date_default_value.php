<?php
namespace verbb\formie\migrations;

use verbb\formie\elements\Form;
use verbb\formie\fields\formfields\Date;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;

class m200731_000000_date_default_value extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $fields = (new Query())
            ->select(['id', 'settings'])
            ->from([Table::FIELDS])
            ->where(['type' => Date::class])
            ->all($this->db);

        foreach ($fields as $field) {
            $settings = Json::decode($field['settings']);

            $settings['defaultOption'] = 'date';

            $this->update(Table::FIELDS, [
                'settings' => Json::encode($settings)
            ], ['id' => $field['id']], [], false);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200731_000000_date_default_value cannot be reverted.\n";
        return false;
    }
}

