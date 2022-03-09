<?php
namespace verbb\formie\migrations;

use verbb\formie\fields\formfields\Agree;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;

class m201016_000000_fix_agree_descriptionHtml extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $fields = (new Query())
            ->select(['*'])
            ->from('{{%fields}}')
            ->where(['type' => Agree::class])
            ->all();

        foreach ($fields as $field) {
            $settings = Json::decode($field['settings']);

            if (array_key_exists('descriptionHtml', $settings)) {
                ArrayHelper::remove($settings, 'descriptionHtml');

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
        echo "m201016_000000_fix_agree_descriptionHtml cannot be reverted.\n";
        return false;
    }
}
