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

class m211112_000000_submission_snapshot extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%formie_submissions}}', 'snapshot')) {
            $this->addColumn('{{%formie_submissions}}', 'snapshot', $this->text()->after('spamReason'));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m211112_000000_submission_snapshot cannot be reverted.\n";
        return false;
    }
}
