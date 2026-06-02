<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;
use Throwable;

class m260224_000004_submission_drafts extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->tableExists(Table::FORMIE_SUBMISSION_DRAFTS)) {
            $this->createTable(Table::FORMIE_SUBMISSION_DRAFTS, [
                'id' => $this->primaryKey(),
                'storageKey' => $this->string(255)->notNull(),
                'value' => $this->mediumText(),
                'dateExpires' => $this->dateTime(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        try {
            $this->createIndex(null, Table::FORMIE_SUBMISSION_DRAFTS, ['storageKey'], true);
            $this->createIndex(null, Table::FORMIE_SUBMISSION_DRAFTS, ['dateExpires'], false);
        } catch (Throwable) {
            // Indexes may already exist.
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260224_000004_submission_drafts cannot be reverted.\n";

        return false;
    }
}
