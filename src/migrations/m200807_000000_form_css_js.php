<?php
namespace verbb\formie\migrations;

use craft\db\Migration;
use craft\helpers\Db;

class m200807_000000_form_css_js extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%formie_formtemplates}}', 'outputJsBase')) {
            $this->addColumn('{{%formie_formtemplates}}', 'outputJsBase', $this->boolean()->defaultValue(true)->after('outputCssTheme'));
        }

        if ($this->db->columnExists('{{%formie_formtemplates}}', 'outputJs')) {
            Db::renameColumn('{{%formie_formtemplates}}', 'outputJs', 'outputJsTheme', $this);
        }

        if (!$this->db->columnExists('{{%formie_formtemplates}}', 'outputCssLocation')) {
            $this->addColumn('{{%formie_formtemplates}}', 'outputCssLocation', $this->string()->after('outputJsTheme'));
        }

        if (!$this->db->columnExists('{{%formie_formtemplates}}', 'outputJsLocation')) {
            $this->addColumn('{{%formie_formtemplates}}', 'outputJsLocation', $this->string()->after('outputCssLocation'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m200807_000000_form_css_js cannot be reverted.\n";
        return false;
    }
}