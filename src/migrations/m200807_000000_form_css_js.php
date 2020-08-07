<?php
namespace verbb\formie\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

class m200807_000000_form_css_js extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%formie_formtemplates}}', 'outputJsBase')) {
            $this->addColumn('{{%formie_formtemplates}}', 'outputJsBase', $this->boolean()->defaultValue(true)->after('outputCssTheme'));
        }

        if ($this->db->columnExists('{{%formie_formtemplates}}', 'outputJs')) {
            MigrationHelper::renameColumn('{{%formie_formtemplates}}', 'outputJs', 'outputJsTheme', $this);
        }

        if (!$this->db->columnExists('{{%formie_formtemplates}}', 'outputCssLocation')) {
            $this->addColumn('{{%formie_formtemplates}}', 'outputCssLocation', $this->string()->after('outputJsTheme'));
        }

        if (!$this->db->columnExists('{{%formie_formtemplates}}', 'outputJsLocation')) {
            $this->addColumn('{{%formie_formtemplates}}', 'outputJsLocation', $this->string()->after('outputCssLocation'));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200807_000000_form_css_js cannot be reverted.\n";
        return false;
    }
}