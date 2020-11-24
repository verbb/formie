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

class m201124_100000_form_file_uploads_data_retention extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%formie_forms}}', 'fileUploadsAction')) {
            $this->addColumn('{{%formie_forms}}', 'fileUploadsAction', $this->enum('fileUploadsAction', ['retain', 'delete'])
                ->defaultValue('retain')
                ->notNull()
                ->after('userDeletedAction'));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m201124_100000_form_file_uploads_data_retention cannot be reverted.\n";
        return false;
    }
}
