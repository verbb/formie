<?php
namespace verbb\formie\migrations;

use verbb\formie\fields\formfields\Phone;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;

class m201123_000000_phone_refactor extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $fields = (new Query())
            ->select(['*'])
            ->from('{{%fields}}')
            ->where(['type' => Phone::class])
            ->all();

        $propertiesToRemove = [
            'validate',
            'showCountryCode',
            'countryLabel',
            'numberLabel',
            'countryPlaceholder',
            'validateType',
            'validateCountry',
            'validateCharacter',
            'numberCollapsed',
            'numberPlaceholder',
            'numberDefaultValue',
            'numberPrePopulate',
            'countryPrePopulate',
            'countryRestrict',
        ];

        foreach ($fields as $field) {
            $settings = Json::decode($field['settings']);

            foreach ($propertiesToRemove as $propertyToRemove) {
                if (array_key_exists($propertyToRemove, $settings)) {
                    ArrayHelper::remove($settings, $propertyToRemove);
                }
            }

            $this->db->createCommand()
                ->update('{{%fields}}', [
                    'settings' => Json::encode($settings),
                ], ['id' => $field['id']])
                ->execute();
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m201123_000000_phone_refactor cannot be reverted.\n";
        return false;
    }
}
