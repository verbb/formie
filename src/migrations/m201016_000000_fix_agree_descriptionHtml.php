<?php
namespace verbb\formie\migrations;

use verbb\formie\elements\Form;
use verbb\formie\fields\formfields\Agree;
use verbb\formie\prosemirror\toprosemirror\Renderer;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;

class m201016_000000_fix_agree_descriptionHtml extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
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
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m201016_000000_fix_agree_descriptionHtml cannot be reverted.\n";
        return false;
    }
}
