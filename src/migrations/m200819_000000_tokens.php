<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m200819_000000_tokens extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%formie_tokens}}')) {
            $this->createTable('{{%formie_tokens}}', [
                'id' => $this->primaryKey(),
                'type' => $this->string()->notNull(),
                'accessToken' => $this->text(),
                'secret' => $this->text(),
                'endOfLife' => $this->string(),
                'refreshToken' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m200819_000000_tokens cannot be reverted.\n";
        return false;
    }
}