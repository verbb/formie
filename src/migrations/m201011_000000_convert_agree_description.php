<?php
namespace verbb\formie\migrations;

use verbb\formie\elements\Form;
use verbb\formie\fields\formfields\Agree;
use verbb\formie\prosemirror\toprosemirror\Renderer;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;

class m201011_000000_convert_agree_description extends Migration
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

            if (isset($settings['description'])) {
                $description = (new Renderer)->render('<p>' . $settings['description'] . '</p>');
                $settings['description'] = $description['content'];

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
        echo "m201011_000000_convert_agree_description cannot be reverted.\n";
        return false;
    }
}
