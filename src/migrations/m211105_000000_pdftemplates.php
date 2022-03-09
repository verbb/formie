<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m211105_000000_pdftemplates extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
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

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m211105_000000_pdftemplates cannot be reverted.\n";
        return false;
    }
}
