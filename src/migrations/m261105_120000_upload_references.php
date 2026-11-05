<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\services\UploadReferences;

use craft\db\Migration;
use craft\helpers\Db;

class m261105_120000_upload_references extends Migration
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();
        // MySQL DDL commits implicitly. Publish coverage in a real transaction
        // after schema creation, so remote queue workers cannot run ahead of it.
        $this->on(self::EVENT_AFTER_UP, fn() => $this->db->transaction(fn() => Formie::$plugin->getUploadReferences()->refreshSchemas()));
    }

    public function safeUp(): bool
    {
        $this->createTables();

        return true;
    }

    public function createTables(): void
    {
        if (!$this->db->columnExists(Table::FORMIE_SUBMISSIONS, 'uploadReferenceVersion')) {
            $this->addColumn(Table::FORMIE_SUBMISSIONS, 'uploadReferenceVersion', $this->string(36));
        }

        if (!$this->db->tableExists(UploadReferences::STATE_TABLE)) {
            $this->createTable(UploadReferences::STATE_TABLE, ['id' => $this->integer()->notNull(), 'PRIMARY KEY ([[id]])']);
        }

        $this->upsert(UploadReferences::STATE_TABLE, ['id' => 1], false);

        if (!$this->db->tableExists(Table::FORMIE_SUBMISSION_ASSETS)) {
            $this->createTable(Table::FORMIE_SUBMISSION_ASSETS, [
                'submissionId' => $this->integer()->notNull(),
                'assetId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                'PRIMARY KEY ([[submissionId]], [[assetId]])',
            ]);
        }

        if (!$this->db->tableExists(Table::FORMIE_UPLOAD_REFERENCE_FORMS)) {
            $this->createTable(Table::FORMIE_UPLOAD_REFERENCE_FORMS, [
                'formId' => $this->integer()->notNull(),
                'generation' => $this->integer()->notNull()->defaultValue(1),
                'schemaFingerprint' => $this->string(64)->notNull(),
                'schemaData' => $this->json(),
                'cursor' => $this->integer()->notNull()->defaultValue(0),
                'highWaterId' => $this->integer()->notNull()->defaultValue(0),
                'state' => $this->string(16)->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                'PRIMARY KEY ([[formId]])',
            ]);
        }

        if (!$this->db->tableExists(Table::FORMIE_UPLOAD_REFERENCE_UNKNOWN)) {
            $this->createTable(Table::FORMIE_UPLOAD_REFERENCE_UNKNOWN, [
                'submissionId' => $this->integer()->notNull(),
                'formId' => $this->integer()->notNull(),
                'reasonCode' => $this->string(32)->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                'PRIMARY KEY ([[submissionId]])',
            ]);
        }

        // Each operation is independently restartable after MySQL's implicit DDL commits.
        foreach ([
            [Table::FORMIE_SUBMISSION_ASSETS, ['assetId', 'submissionId']],
            [Table::FORMIE_UPLOAD_REFERENCE_FORMS, ['state', 'formId']],
            [Table::FORMIE_UPLOAD_REFERENCE_UNKNOWN, ['formId', 'submissionId']],
        ] as [$table, $columns]) {
            if (!Db::findIndex($table, $columns, false, $this->db)) {
                $this->createIndex(null, $table, $columns);
            }
        }

        foreach ([
            [Table::FORMIE_SUBMISSION_ASSETS, 'submissionId', Table::FORMIE_SUBMISSIONS],
            [Table::FORMIE_UPLOAD_REFERENCE_UNKNOWN, 'submissionId', Table::FORMIE_SUBMISSIONS],
            [Table::FORMIE_UPLOAD_REFERENCE_FORMS, 'formId', Table::FORMIE_FORMS],
        ] as [$table, $column, $target]) {
            $schema = $this->db->getTableSchema($table, true);
            $found = false;

            foreach ($schema->foreignKeys as $key) {
                $found = $found || isset($key[$column]);
            }

            if (!$found) {
                $this->addForeignKey(null, $table, $column, $target, 'id', 'CASCADE');
            }
        }
    }

    public function safeDown(): bool
    {
        return false;
    }
}
