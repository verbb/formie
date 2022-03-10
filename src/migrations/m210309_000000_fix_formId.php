<?php
namespace verbb\formie\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;

class m210309_000000_fix_formId extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Fix any fields with an empty `formId` by re-saving the form. Watch out for nested fields!
        $fields = (new Query())
            ->select(['*'])
            ->from('{{%fields}}')
            ->all();

        foreach ($fields as $field) {
            if (str_contains($field['context'], 'formie:')) {
                $settings = Json::decode($field['settings']);
                $formId = $settings['formId'] ?? null;

                if (!$formId) {
                    $settings['formId'] = $this->getFormId($field);
                }

                $this->update('{{%fields}}', ['settings' => Json::encode($settings)], ['id' => $field['id']], [], false);
            }

            if (str_contains($field['context'], 'formieField:')) {
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

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m210309_000000_fix_formId cannot be reverted.\n";
        return false;
    }

    private function getFormId($field): ?int
    {
        $formContext = explode(':', $field['context']);

        return Db::idByUid('{{%formie_forms}}', $formContext[1]);
    }
}
