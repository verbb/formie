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

class m201123_000000_phone_refactor extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
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
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m201123_000000_phone_refactor cannot be reverted.\n";
        return false;
    }
}
