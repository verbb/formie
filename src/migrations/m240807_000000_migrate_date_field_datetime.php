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

class m240807_000000_migrate_date_field_datetime extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $fields = (new Query())
            ->select(['*'])
            ->from('{{%fields}}')
            ->where(['type' => Date::class])
            ->all();

        foreach ($fields as $field) {
            $settings = Json::decode($field['settings']);

            $updatedValues = false;

            if (array_key_exists('defaultValue', $settings) && $settings['defaultValue']) {
                $settings['defaultValue'] = Db::prepareDateForDb($settings['defaultValue']);

                $updatedValues = true;
            }

            if (array_key_exists('minDate', $settings) && $settings['minDate']) {
                $settings['minDate'] = Db::prepareDateForDb($settings['minDate']);

                $updatedValues = true;
            }

            if (array_key_exists('maxDate', $settings) && $settings['maxDate']) {
                $settings['maxDate'] = Db::prepareDateForDb($settings['maxDate']);

                $updatedValues = true;
            }

            if ($updatedValues) {
                $this->db->createCommand()
                    ->update('{{%fields}}', [
                        'settings' => Json::encode($settings),
                    ], ['id' => $field['id']])
                    ->execute();
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m240807_000000_migrate_date_field_datetime cannot be reverted.\n";
        return false;
    }
}
