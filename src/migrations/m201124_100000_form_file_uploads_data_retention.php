<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m201124_100000_form_file_uploads_data_retention extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%formie_forms}}', 'fileUploadsAction')) {
            $this->addColumn('{{%formie_forms}}', 'fileUploadsAction', $this->enum('fileUploadsAction', ['retain', 'delete'])
                ->defaultValue('retain')
                ->notNull()
                ->after('userDeletedAction'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m201124_100000_form_file_uploads_data_retention cannot be reverted.\n";
        return false;
    }
}
