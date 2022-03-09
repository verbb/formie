<?php
namespace verbb\formie\migrations;

use verbb\formie\fields\formfields\Agree;
use verbb\formie\prosemirror\toprosemirror\Renderer;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m201011_000000_convert_agree_description extends Migration
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

            if (array_key_exists('description', $settings)) {
                $description = (new Renderer)->render('<p>' . $settings['description'] . '</p>');
                $settings['description'] = $description['content'];

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
        echo "m201011_000000_convert_agree_description cannot be reverted.\n";
        return false;
    }
}
