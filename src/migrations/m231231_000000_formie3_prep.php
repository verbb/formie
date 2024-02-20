<?php
namespace verbb\formie\migrations;

use verbb\formie\elements\Form;
use verbb\formie\helpers\Plugin;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\StringHelper;

class m231231_000000_formie3_prep extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%formie_newlayout}}')) {
            $this->createTable('{{%formie_newlayout}}', [
                'id' => $this->primaryKey(),
                'formId' => $this->integer()->notNull(),
                'layoutConfig' => $this->longText(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists('{{%formie_newnestedlayout}}')) {
            $this->createTable('{{%formie_newnestedlayout}}', [
                'id' => $this->primaryKey(),
                'fieldId' => $this->integer()->notNull(),
                'layoutConfig' => $this->longText(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        foreach (Form::find()->status(null)->all() as $form) {
            Plugin::saveFormie3Layout($form);
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m231231_000000_formie3_prep cannot be reverted.\n";
        return false;
    }
}
