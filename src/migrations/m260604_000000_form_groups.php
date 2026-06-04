<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m260604_000000_form_groups extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->tableExists(Table::FORMIE_FORM_GROUPS)) {
            $this->createTable(Table::FORMIE_FORM_GROUPS, [
                'id' => $this->primaryKey(),
                'name' => $this->string()->notNull(),
                'handle' => $this->string(64)->notNull(),
                'sortOrder' => $this->smallInteger()->unsigned(),
                'dateDeleted' => $this->dateTime(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->columnExists(Table::FORMIE_FORMS, 'groupId')) {
            $this->addColumn(Table::FORMIE_FORMS, 'groupId', $this->integer()->after('templateId'));
            $this->createIndex(null, Table::FORMIE_FORMS, 'groupId', false);
            $this->addForeignKey(
                null,
                Table::FORMIE_FORMS,
                ['groupId'],
                Table::FORMIE_FORM_GROUPS,
                ['id'],
                'SET NULL',
                null,
            );
        }

        return true;
    }

    public function safeDown(): bool
    {
        if ($this->db->columnExists(Table::FORMIE_FORMS, 'groupId')) {
            $this->dropForeignKeyToIfExists(Table::FORMIE_FORMS, 'groupId');
            $this->dropIndexIfExists(Table::FORMIE_FORMS, 'groupId');
            $this->dropColumn(Table::FORMIE_FORMS, 'groupId');
        }

        $this->dropTableIfExists(Table::FORMIE_FORM_GROUPS);

        return true;
    }
}
