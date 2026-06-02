<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;
use Throwable;

class m260223_000002_pending_uploads extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->tableExists(Table::FORMIE_PENDING_UPLOADS)) {
            $this->createTable(Table::FORMIE_PENDING_UPLOADS, [
                'id' => $this->primaryKey(),
                'assetId' => $this->integer()->notNull(),
                'formId' => $this->integer(),
                'submissionId' => $this->integer(),
                'fieldUid' => $this->string(64),
                'isFinalized' => $this->boolean()->notNull()->defaultValue(false),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        try {
            $this->createIndex(null, Table::FORMIE_PENDING_UPLOADS, ['assetId'], true);
            $this->createIndex(null, Table::FORMIE_PENDING_UPLOADS, ['submissionId'], false);
            $this->createIndex(null, Table::FORMIE_PENDING_UPLOADS, ['isFinalized', 'dateUpdated'], false);
        } catch (Throwable) {
            // Indexes may already exist.
        }

        try {
            $this->addForeignKey(
                null,
                Table::FORMIE_PENDING_UPLOADS,
                ['assetId'],
                '{{%assets}}',
                ['id'],
                'CASCADE',
                null
            );
        } catch (Throwable) {
            // FK may already exist.
        }

        try {
            $this->addForeignKey(
                null,
                Table::FORMIE_PENDING_UPLOADS,
                ['submissionId'],
                Table::FORMIE_SUBMISSIONS,
                ['id'],
                'CASCADE',
                null
            );
        } catch (Throwable) {
            // FK may already exist.
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "Pending uploads migration cannot be reverted.\n";

        return false;
    }
}
