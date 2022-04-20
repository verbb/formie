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

class m220420_000000_stencil_add_entryid extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%formie_stencils}}', 'attachAssets')) {
            $this->addColumn('{{%formie_stencils}}', 'submitActionEntryId', $this->integer()->after('templateId'));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m220420_000000_stencil_add_entryid cannot be reverted.\n";
        return false;
    }
}
