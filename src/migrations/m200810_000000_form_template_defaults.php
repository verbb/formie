<?php
namespace verbb\formie\migrations;

use craft\db\Migration;
use craft\db\Query;

class m200810_000000_form_template_defaults extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $formTemplates = (new Query())
            ->select(['*'])
            ->from('{{%formie_formtemplates}}')
            ->where(['outputCssLocation' => null])
            ->all();

        foreach ($formTemplates as $formTemplate) {
            $this->update('{{%formie_formtemplates}}', ['outputCssLocation' => 'page-header'], ['id' => $formTemplate['id']], [], false);
        }

        $formTemplates = (new Query())
            ->select(['*'])
            ->from('{{%formie_formtemplates}}')
            ->where(['outputJsLocation' => null])
            ->all();

        foreach ($formTemplates as $formTemplate) {
            $this->update('{{%formie_formtemplates}}', ['outputJsLocation' => 'page-footer'], ['id' => $formTemplate['id']], [], false);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m200810_000000_form_template_defaults cannot be reverted.\n";
        return false;
    }
}