<?php
namespace verbb\formie\migrations;

use verbb\formie\fields\formfields\Address;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;

class m201012_000000_address_autocomplete extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $fields = (new Query())
            ->select(['*'])
            ->from('{{%fields}}')
            ->where(['type' => Address::class])
            ->all();

        foreach ($fields as $field) {
            $settings = Json::decode($field['settings']);

            if (array_key_exists('enableAutocomplete', $settings)) {
                $settings['autocompleteEnabled'] = ArrayHelper::remove($settings, 'enableAutocomplete');

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
        echo "m201012_000000_address_autocomplete cannot be reverted.\n";
        return false;
    }
}
