<?php
namespace verbb\formie\migrations;

use verbb\formie\elements\Form;
use verbb\formie\fields\formfields\Phone;
use verbb\formie\prosemirror\toprosemirror\Renderer;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;

class m210307_000000_relations extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
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
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210307_000000_relations cannot be reverted.\n";
        return false;
    }
}
