<?php
namespace verbb\formie\migrations;

use verbb\formie\elements\Form;
use verbb\formie\fields\formfields\Date;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;

class m250117_000000_date_year_min extends Migration
{
    public function safeUp(): bool
    {
        $fields = (new Query())
            ->select(['*'])
            ->from('{{%fields}}')
            ->where(['type' => Date::class])
            ->all();

        foreach ($fields as $field) {
            $settings = Json::decode($field['settings']);
            $minYearRange = $settings['minYearRange'] ?? null;

            if ($minYearRange) {
                $settings['minYearRange'] = $minYearRange * -1;

                $this->update('{{%fields}}', [
                    'settings' => Json::encode($settings),
                ], ['id' => $field['id']]);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m250117_000000_date_year_min cannot be reverted.\n";
        return false;
    }
}
