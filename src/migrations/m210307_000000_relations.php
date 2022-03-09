<?php
namespace verbb\formie\migrations;

use craft\db\Migration;

class m210307_000000_relations extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%formie_relations}}')) {
            $this->createTable('{{%formie_relations}}', [
                'id' => $this->primaryKey(),
                'type' => $this->string(255)->notNull(),
                'sourceId' => $this->integer()->notNull(),
                'sourceSiteId' => $this->integer(),
                'targetId' => $this->integer()->notNull(),
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
        echo "m210307_000000_relations cannot be reverted.\n";
        return false;
    }
}
