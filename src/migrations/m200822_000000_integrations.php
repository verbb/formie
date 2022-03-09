<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m200822_000000_integrations extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%formie_integrations}}')) {
            $this->createTable('{{%formie_integrations}}', [
                'id' => $this->primaryKey(),
                'name' => $this->string()->notNull(),
                'handle' => $this->string(64)->notNull(),
                'type' => $this->string()->notNull(),
                'sortOrder' => $this->smallInteger()->unsigned(),
                'enabled' => $this->boolean()->notNull()->defaultValue(true),
                'settings' => $this->text(),
                'cache' => $this->longText(),
                'tokenId' => $this->integer(),
                'dateDeleted' => $this->dateTime(),
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
        echo "m200822_000000_integrations cannot be reverted.\n";
        return false;
    }
}