<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

use yii\db\Expression;

class m260320_000007_form_template_output_flags extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $table = Table::FORMIE_FORM_TEMPLATES;

        if (!$this->db->columnExists($table, 'outputCss')) {
            $this->addColumn($table, 'outputCss', $this->boolean()->defaultValue(true)->after('useCustomTemplates'));
        }

        if (!$this->db->columnExists($table, 'outputJs')) {
            $this->addColumn($table, 'outputJs', $this->boolean()->defaultValue(true)->after('outputCss'));
        }

        $this->update($table, [
            'outputCss' => new Expression('[[outputCssLayout]] OR [[outputCssTheme]]'),
            'outputJs' => new Expression('[[outputJsBase]] OR [[outputJsTheme]]'),
        ]);

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260320_000007_form_template_output_flags cannot be reverted.\n";

        return false;
    }
}
