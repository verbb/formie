<?php
namespace verbb\formie\migrations;

use verbb\formie\elements\Form;
use verbb\formie\prosemirror\toprosemirror\Renderer;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;

class m200725_000000_convert_success_message extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $forms = (new Query())
            ->select(['id', 'handle', 'settings'])
            ->from('{{%formie_forms}}')
            ->all();

        foreach ($forms as $form) {
            $settings = Json::decode($form['settings']);

            $submitActionMessage = (new Renderer)->render('<p>' . $settings['submitActionMessage'] . '</p>');
            $settings['submitActionMessage'] = $submitActionMessage['content'];
            
            $this->db->createCommand()
                ->update('{{%formie_forms}}', [
                    'settings' => Json::encode($settings),
                ], ['id' => $form['id']])
                ->execute();
        }

        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('plugins.formie.schemaVersion', true);
        
        if (version_compare($schemaVersion, '1.0.2', '>=')) {
            return;
        }

        // Update the project config for schemas
        $stencils = $projectConfig->get('formie.stencils');

        foreach ($stencils as $key => $stencil) {
            $data = $stencil['data'];
            
            $submitActionMessage = (new Renderer)->render('<p>' . $data['settings']['submitActionMessage'] . '</p>');
            $data['settings']['submitActionMessage'] = Json::encode($submitActionMessage['content']);

            $projectConfig->set('formie.stencils.' . $key . '.data', $data);
        }

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200725_000000_convert_success_message cannot be reverted.\n";
        return false;
    }
}