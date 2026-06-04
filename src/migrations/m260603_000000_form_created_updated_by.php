<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m260603_000000_form_created_updated_by extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->columnExists(Table::FORMIE_FORMS, 'createdById')) {
            $this->addColumn(Table::FORMIE_FORMS, 'createdById', $this->integer()->after('fileUploadsAction'));
        }

        if (!$this->db->columnExists(Table::FORMIE_FORMS, 'updatedById')) {
            $this->addColumn(Table::FORMIE_FORMS, 'updatedById', $this->integer()->after('createdById'));
        }

        $this->dropIndexIfExists(Table::FORMIE_FORMS, ['createdById']);
        $this->createIndex(null, Table::FORMIE_FORMS, 'createdById', false);

        $this->dropIndexIfExists(Table::FORMIE_FORMS, ['updatedById']);
        $this->createIndex(null, Table::FORMIE_FORMS, 'updatedById', false);

        $this->dropForeignKeyIfExists(Table::FORMIE_FORMS, ['createdById']);
        $this->addForeignKey(
            null,
            Table::FORMIE_FORMS,
            ['createdById'],
            '{{%users}}',
            ['id'],
            'SET NULL',
            null,
        );

        $this->dropForeignKeyIfExists(Table::FORMIE_FORMS, ['updatedById']);
        $this->addForeignKey(
            null,
            Table::FORMIE_FORMS,
            ['updatedById'],
            '{{%users}}',
            ['id'],
            'SET NULL',
            null,
        );

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropForeignKeyIfExists(Table::FORMIE_FORMS, ['updatedById']);
        $this->dropForeignKeyIfExists(Table::FORMIE_FORMS, ['createdById']);

        if ($this->db->columnExists(Table::FORMIE_FORMS, 'updatedById')) {
            $this->dropColumn(Table::FORMIE_FORMS, 'updatedById');
        }

        if ($this->db->columnExists(Table::FORMIE_FORMS, 'createdById')) {
            $this->dropColumn(Table::FORMIE_FORMS, 'createdById');
        }

        return true;
    }
}
