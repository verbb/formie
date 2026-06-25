<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m260644_000000_field_site_overrides extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if ($this->db->tableExists(Table::FORMIE_FIELD_SITE_OVERRIDES)) {
            return true;
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

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists(Table::FORMIE_FIELD_SITE_OVERRIDES);

        return true;
    }
}
