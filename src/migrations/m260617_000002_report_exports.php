<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m260617_000002_report_exports extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->tableExists(Table::FORMIE_REPORT_EXPORTS)) {
            $this->createTable(Table::FORMIE_REPORT_EXPORTS, [
                'id' => $this->primaryKey(),
                'reportId' => $this->integer()->notNull(),
                'userId' => $this->integer(),
                'scheduledReportId' => $this->integer(),
                'source' => $this->string(32)->notNull()->defaultValue('interactive'),
                'status' => $this->string(32)->notNull()->defaultValue('pending'),
                'format' => $this->string(16)->notNull()->defaultValue('csv'),
                'context' => $this->text(),
                'filename' => $this->string(),
                'filePath' => $this->string(),
                'fileSize' => $this->bigInteger()->unsigned(),
                'downloadToken' => $this->string(64)->notNull(),
                'notifyEmail' => $this->string(),
                'error' => $this->text(),
                'dateExpires' => $this->dateTime(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, Table::FORMIE_REPORT_EXPORTS, ['reportId'], false);
            $this->createIndex(null, Table::FORMIE_REPORT_EXPORTS, ['status'], false);
            $this->createIndex(null, Table::FORMIE_REPORT_EXPORTS, ['dateExpires'], false);
            $this->createIndex(null, Table::FORMIE_REPORT_EXPORTS, ['downloadToken'], true);

            $this->addForeignKey(
                null,
                Table::FORMIE_REPORT_EXPORTS,
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
        $this->dropTableIfExists(Table::FORMIE_REPORT_EXPORTS);

        return true;
    }
}
