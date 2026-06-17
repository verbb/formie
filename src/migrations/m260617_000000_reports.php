<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m260617_000000_reports extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->tableExists(Table::FORMIE_REPORTS)) {
            $this->createTable(Table::FORMIE_REPORTS, [
                'id' => $this->primaryKey(),
                'name' => $this->string()->notNull(),
                'handle' => $this->string(64)->notNull(),
                'sortOrder' => $this->smallInteger()->unsigned(),
                'dateDeleted' => $this->dateTime(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, Table::FORMIE_REPORTS, ['handle'], true);
        }

        if (!$this->db->tableExists(Table::FORMIE_SCHEDULED_REPORTS)) {
            $this->createTable(Table::FORMIE_SCHEDULED_REPORTS, [
                'id' => $this->primaryKey(),
                'reportId' => $this->integer()->notNull(),
                'name' => $this->string()->notNull(),
                'enabled' => $this->boolean()->notNull()->defaultValue(true),
                'lastSentAt' => $this->dateTime(),
                'dateDeleted' => $this->dateTime(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, Table::FORMIE_SCHEDULED_REPORTS, ['reportId'], false);
            $this->addForeignKey(
                null,
                Table::FORMIE_SCHEDULED_REPORTS,
                ['reportId'],
                Table::FORMIE_REPORTS,
                ['id'],
                'CASCADE',
                null,
            );
        }

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists(Table::FORMIE_SCHEDULED_REPORTS);
        $this->dropTableIfExists(Table::FORMIE_REPORTS);

        return true;
    }
}
