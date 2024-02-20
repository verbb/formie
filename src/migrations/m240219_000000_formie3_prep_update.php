<?php
namespace verbb\formie\migrations;

use verbb\formie\elements\Form;
use verbb\formie\helpers\Plugin;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\StringHelper;

class m240219_000000_formie3_prep_update extends Migration
{
    public function safeUp(): bool
    {
        $this->alterColumn('{{%formie_newlayout}}', 'layoutConfig', $this->longText());
        $this->alterColumn('{{%formie_newnestedlayout}}', 'layoutConfig', $this->longText());
        
        foreach (Form::find()->status(null)->all() as $form) {
            Plugin::saveFormie3Layout($form);
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m240219_000000_formie3_prep_update cannot be reverted.\n";
        return false;
    }
}
