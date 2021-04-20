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

class m210309_000000_fix_formId extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Fix any fields with an empty `formId` by re-saving the form. Watch out for nested fields!
        $fields = (new Query())
            ->select(['*'])
            ->from('{{%fields}}')
            ->all();

        foreach ($fields as $field) {
            if (strstr($field['context'], 'formie:')) {
                $settings = Json::decode($field['settings']);
                $formId = $settings['formId'] ?? null;

                if (!$formId) {
                    $settings['formId'] = $this->getFormId($field);
                }

                $this->update('{{%fields}}', ['settings' => Json::encode($settings)], ['id' => $field['id']], [], false);
            }

            if (strstr($field['context'], 'formieField:')) {
                $settings = Json::decode($field['settings']);
                $formId = $settings['formId'] ?? null;

                if (!$formId) {
                    $fieldContext = explode(':', $field['context']);

                    $outerField = (new Query())
                        ->select(['*'])
                        ->from('{{%fields}}')
                        ->where(['uid' => $fieldContext[1]])
                        ->one();

                    if ($outerField) {
                        $settings['formId'] = $this->getFormId($outerField);
                        
                        $this->update('{{%fields}}', ['settings' => Json::encode($settings)], ['id' => $field['id']], [], false);
                    }
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210309_000000_fix_formId cannot be reverted.\n";
        return false;
    }

    private function getFormId($field)
    {
        $formContext = explode(':', $field['context']);

        return Db::idByUid('{{%formie_forms}}', $formContext[1]);
    }
}
