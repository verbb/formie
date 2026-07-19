<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m260711_000000_ensure_site_overrides_tables extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->_ensureFormSiteOverridesTable();
        $this->_ensureFieldSiteOverridesTable();

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260711_000000_ensure_site_overrides_tables cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _ensureFormSiteOverridesTable(): void
    {
        if ($this->db->tableExists(Table::FORMIE_FORM_SITE_OVERRIDES)) {
            return;
        }

        $this->createTable(Table::FORMIE_FORM_SITE_OVERRIDES, [
            'id' => $this->primaryKey(),
            'formId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'overrides' => $this->json()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(
            null,
            Table::FORMIE_FORM_SITE_OVERRIDES,
            ['formId', 'siteId'],
            true,
        );

        $this->createIndex(null, Table::FORMIE_FORM_SITE_OVERRIDES, 'siteId', false);

        $this->addForeignKey(
            null,
            Table::FORMIE_FORM_SITE_OVERRIDES,
            ['formId'],
            Table::FORMIE_FORMS,
            ['id'],
            'CASCADE',
            null,
        );

        $this->addForeignKey(
            null,
            Table::FORMIE_FORM_SITE_OVERRIDES,
            ['siteId'],
            Table::SITES,
            ['id'],
            'CASCADE',
            null,
        );
    }

    private function _ensureFieldSiteOverridesTable(): void
    {
        if ($this->db->tableExists(Table::FORMIE_FIELD_SITE_OVERRIDES)) {
            return;
        }

        $this->createTable(Table::FORMIE_FIELD_SITE_OVERRIDES, [
            'id' => $this->primaryKey(),
            'fieldId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'overrides' => $this->json()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(
            null,
            Table::FORMIE_FIELD_SITE_OVERRIDES,
            ['fieldId', 'siteId'],
            true,
        );

        $this->createIndex(null, Table::FORMIE_FIELD_SITE_OVERRIDES, 'siteId', false);

        $this->addForeignKey(
            null,
            Table::FORMIE_FIELD_SITE_OVERRIDES,
            ['fieldId'],
            Table::FORMIE_FIELDS,
            ['id'],
            'CASCADE',
            null,
        );

        $this->addForeignKey(
            null,
            Table::FORMIE_FIELD_SITE_OVERRIDES,
            ['siteId'],
            Table::SITES,
            ['id'],
            'CASCADE',
            null,
        );
    }
}
