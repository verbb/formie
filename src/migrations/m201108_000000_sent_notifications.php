<?php
namespace verbb\formie\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;

class m201108_000000_sent_notifications extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->getDb()->tableExists('{{%formie_sentnotifications}}')) {
            $this->createTable('{{%formie_sentnotifications}}', [
                'id' => $this->primaryKey(),
                'title' => $this->string(),
                'formId' => $this->integer(),
                'submissionId' => $this->integer(),
                'subject' => $this->string(),
                'to' => $this->string(),
                'cc' => $this->string(),
                'bcc' => $this->string(),
                'replyTo' => $this->string(),
                'replyToName' => $this->string(),
                'from' => $this->string(),
                'fromName' => $this->string(),
                'body' => $this->text(),
                'htmlBody' => $this->text(),
                'info' => $this->text(),
                'dateCreated' => $this->dateTime(),
                'dateUpdated' => $this->dateTime(),
                'uid' => $this->uid()
            ]);

            $this->addForeignKey(null, '{{%formie_sentnotifications}}', ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
            $this->addForeignKey(null, '{{%formie_sentnotifications}}', ['formId'], '{{%formie_forms}}', ['id'], 'CASCADE', null);
            $this->addForeignKey(null, '{{%formie_sentnotifications}}', ['submissionId'], '{{%formie_submissions}}', ['id'], 'CASCADE', null);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m201108_000000_sent_notifications cannot be reverted.\n";
        return false;
    }
}
