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

class m211105_000000_pdftemplates extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->tableExists('{{%formie_pdftemplates}}')) {
            $this->createTable('{{%formie_pdftemplates}}', [
                'id' => $this->primaryKey(),
                'name' => $this->string()->notNull(),
                'handle' => $this->string(64)->notNull(),
                'template' => $this->string()->notNull(),
                'filenameFormat' => $this->string()->notNull(),
                'sortOrder' => $this->smallInteger()->unsigned(),
                'dateDeleted' => $this->dateTime(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->columnExists('{{%formie_notifications}}', 'pdfTemplateId')) {
            $this->addColumn('{{%formie_notifications}}', 'pdfTemplateId', $this->integer()->after('templateId'));
        }

        $this->addForeignKey(null, '{{%formie_notifications}}', ['pdfTemplateId'], '{{%formie_pdftemplates}}', ['id'], 'SET NULL', null);

        if (!$this->db->columnExists('{{%formie_notifications}}', 'attachPdf')) {
            $this->addColumn('{{%formie_notifications}}', 'attachPdf', $this->boolean()->defaultValue(false)->after('attachFiles'));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m211105_000000_pdftemplates cannot be reverted.\n";
        return false;
    }
}
