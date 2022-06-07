<?php
namespace verbb\formie\migrations;

use verbb\formie\fields\formfields\FileUpload;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;

class m220530_000000_payments extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%formie_payments_plans}}')) {
            $this->createTable('{{%formie_payments_plans}}', [
                'id' => $this->primaryKey(),
                'integrationId' => $this->integer()->notNull(),
                'name' => $this->string(),
                'handle' => $this->string(),
                'reference' => $this->string()->notNull(),
                'enabled' => $this->boolean()->notNull(),
                'planData' => $this->text(),
                'isArchived' => $this->boolean()->notNull(),
                'dateArchived' => $this->dateTime(),
                'sortOrder' => $this->integer(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, '{{%formie_payments_plans}}', 'integrationId', false);
            $this->createIndex(null, '{{%formie_payments_plans}}', 'handle', true);
            $this->createIndex(null, '{{%formie_payments_plans}}', 'reference', false);

            $this->addForeignKey(null, '{{%formie_payments_plans}}', ['integrationId'], '{{%formie_integrations}}', ['id'], 'CASCADE', null);
        }

        if (!$this->db->tableExists('{{%formie_payments_subscriptions}}')) {
            $this->createTable('{{%formie_payments_subscriptions}}', [
                'id' => $this->primaryKey(),
                'integrationId' => $this->integer(),
                'submissionId' => $this->integer(),
                'fieldId' => $this->integer(),
                'planId' => $this->integer(),
                'reference' => $this->string()->notNull(),
                'subscriptionData' => $this->text(),
                'trialDays' => $this->integer()->notNull(),
                'nextPaymentDate' => $this->dateTime(),
                'hasStarted' => $this->boolean()->notNull()->defaultValue(true),
                'isSuspended' => $this->boolean()->notNull()->defaultValue(false),
                'dateSuspended' => $this->dateTime(),
                'isCanceled' => $this->boolean()->notNull(),
                'dateCanceled' => $this->dateTime(),
                'isExpired' => $this->boolean()->notNull(),
                'dateExpired' => $this->dateTime(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, '{{%formie_payments_subscriptions}}', 'integrationId', false);
            $this->createIndex(null, '{{%formie_payments_subscriptions}}', 'submissionId', false);
            $this->createIndex(null, '{{%formie_payments_subscriptions}}', 'fieldId', false);
            $this->createIndex(null, '{{%formie_payments_subscriptions}}', 'planId', false);
            $this->createIndex(null, '{{%formie_payments_subscriptions}}', 'reference', false);
            $this->createIndex(null, '{{%formie_payments_subscriptions}}', 'nextPaymentDate', false);
            $this->createIndex(null, '{{%formie_payments_subscriptions}}', 'dateExpired', false);
            $this->createIndex(null, '{{%formie_payments_subscriptions}}', 'dateExpired', false);

            $this->addForeignKey(null, '{{%formie_payments_subscriptions}}', ['integrationId'], '{{%formie_integrations}}', ['id'], 'RESTRICT', null);
            $this->addForeignKey(null, '{{%formie_payments_subscriptions}}', ['submissionId'], '{{%formie_submissions}}', ['id'], 'RESTRICT', null);
            $this->addForeignKey(null, '{{%formie_payments_subscriptions}}', ['fieldId'], '{{%fields}}', ['id'], 'RESTRICT', null);
            $this->addForeignKey(null, '{{%formie_payments_subscriptions}}', ['planId'], '{{%formie_payments_plans}}', ['id'], 'RESTRICT', null);
        }
        
        if (!$this->db->tableExists('{{%formie_payments}}')) {
            $this->createTable('{{%formie_payments}}', [
                'id' => $this->primaryKey(),
                'integrationId' => $this->integer()->notNull(),
                'submissionId' => $this->integer()->notNull(),
                'fieldId' => $this->integer()->notNull(),
                'subscriptionId' => $this->integer(),
                'amount' => $this->decimal(14, 4),
                'currency' => $this->string(),
                'status' => $this->enum('status', ['pending', 'redirect', 'success', 'failed', 'processing'])->notNull(),
                'reference' => $this->string(),
                'code' => $this->string(),
                'message' => $this->text(),
                'note' => $this->mediumText(),
                'response' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, '{{%formie_payments}}', 'integrationId', false);
            $this->createIndex(null, '{{%formie_payments}}', 'fieldId', false);
            $this->createIndex(null, '{{%formie_payments}}', 'reference', false);

            $this->addForeignKey(null, '{{%formie_payments}}', ['submissionId'], '{{%formie_submissions}}', ['id'], 'CASCADE', null);
            $this->addForeignKey(null, '{{%formie_payments}}', ['subscriptionId'], '{{%formie_payments_subscriptions}}', ['id'], 'CASCADE', null);
            $this->addForeignKey(null, '{{%formie_payments}}', ['fieldId'], '{{%fields}}', ['id'], 'CASCADE', null);
            $this->addForeignKey(null, '{{%formie_payments}}', ['integrationId'], '{{%formie_integrations}}', ['id'], 'CASCADE', null);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220530_000000_payments cannot be reverted.\n";
        return false;
    }
}
