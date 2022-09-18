<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\NestedFieldRow;
use verbb\formie\elements\Submission;
use verbb\formie\elements\SentNotification;
use verbb\formie\models\Status;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use verbb\formie\services\Statuses;
use verbb\formie\services\Stencils;

use Craft;
use craft\db\Migration;
use craft\helpers\Json;
use craft\helpers\MigrationHelper;

/**
 * Install migration.
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();
        $this->insertDefaultData();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        // Drop all form content tables
        if ($this->db->tableExists('{{%formie_forms}}')) {
            foreach (Form::find()->trashed(null)->all() as $form) {
                /* @var Form $form */
                if ($this->db->tableExists($form->fieldContentTable)) {
                    MigrationHelper::dropAllForeignKeysOnTable($form->fieldContentTable, $this);
                }

                $this->dropTableIfExists($form->fieldContentTable);
            }
        }

        $this->dropForeignKeys();
        $this->removeTables();
        $this->removeContent();
        $this->dropProjectConfig();

        return true;
    }

    public function createTables(): void
    {
        $this->archiveTableIfExists('{{%formie_emailtemplates}}');
        $this->createTable('{{%formie_emailtemplates}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string(64)->notNull(),
            'template' => $this->string()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateDeleted' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%formie_forms}}');
        $this->createTable('{{%formie_forms}}', [
            'id' => $this->primaryKey(),
            'handle' => $this->string(64)->notNull(),
            // Factor in `{{$fmc_*}}`
            'fieldContentTable' => $this->string(74)->notNull(),
            'settings' => $this->mediumText(),
            'templateId' => $this->integer(),
            'submitActionEntryId' => $this->integer(),
            'submitActionEntrySiteId' => $this->integer(),
            'defaultStatusId' => $this->integer(),
            'dataRetention' => $this->enum('dataRetention', ['forever', 'minutes', 'hours', 'days', 'weeks', 'months', 'years'])
                ->defaultValue('forever')
                ->notNull(),
            'dataRetentionValue' => $this->integer(),
            'userDeletedAction' => $this->enum('userDeletedAction', ['retain', 'delete'])
                ->defaultValue('retain')
                ->notNull(),
            'fileUploadsAction' => $this->enum('fileUploadsAction', ['retain', 'delete'])
                ->defaultValue('retain')
                ->notNull(),
            'fieldLayoutId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%formie_formtemplates}}');
        $this->createTable('{{%formie_formtemplates}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string(64)->notNull(),
            'template' => $this->string(),
            'useCustomTemplates' => $this->boolean()->defaultValue(true),
            'outputCssLayout' => $this->boolean()->defaultValue(true),
            'outputCssTheme' => $this->boolean()->defaultValue(true),
            'outputJsBase' => $this->boolean()->defaultValue(true),
            'outputJsTheme' => $this->boolean()->defaultValue(true),
            'outputCssLocation' => $this->string(),
            'outputJsLocation' => $this->string(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'fieldLayoutId' => $this->integer(),
            'dateDeleted' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%formie_integrations}}');
        $this->createTable('{{%formie_integrations}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string(64)->notNull(),
            'type' => $this->string()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'enabled' => $this->string()->notNull()->defaultValue('true'),
            'settings' => $this->text(),
            'cache' => $this->longText(),
            'tokenId' => $this->integer(),
            'dateDeleted' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%formie_nested}}');
        $this->createTable('{{%formie_nested}}', [
            'id' => $this->primaryKey(),
            'fieldId' => $this->integer()->notNull(),
            'fieldLayoutId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%formie_nestedfieldrows}}');
        $this->createTable('{{%formie_nestedfieldrows}}', [
            'id' => $this->primaryKey(),
            'ownerId' => $this->integer()->notNull(),
            'fieldId' => $this->integer()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'deletedWithOwner' => $this->boolean()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%formie_notifications}}');
        $this->createTable('{{%formie_notifications}}', [
            'id' => $this->primaryKey(),
            'formId' => $this->integer()->notNull(),
            'templateId' => $this->integer(),
            'pdfTemplateId' => $this->integer(),
            'name' => $this->text()->notNull(),
            'enabled' => $this->boolean()->defaultValue(true),
            'subject' => $this->text(),
            'recipients' => $this->enum('recipients', ['email', 'conditions'])
                ->defaultValue('email')
                ->notNull(),
            'to' => $this->text(),
            'toConditions' => $this->text(),
            'cc' => $this->text(),
            'bcc' => $this->text(),
            'replyTo' => $this->text(),
            'replyToName' => $this->text(),
            'from' => $this->text(),
            'fromName' => $this->text(),
            'sender' => $this->text(),
            'content' => $this->text(),
            'attachFiles' => $this->boolean()->defaultValue(true),
            'attachPdf' => $this->boolean()->defaultValue(false),
            'attachAssets' => $this->text(),
            'enableConditions' => $this->boolean()->defaultValue(false),
            'conditions' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%formie_pagesettings}}');
        $this->createTable('{{%formie_pagesettings}}', [
            'id' => $this->primaryKey(),
            'fieldLayoutId' => $this->integer()->notNull(),
            'fieldLayoutTabId' => $this->integer()->notNull(),
            'settings' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%formie_payments}}');
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

        $this->archiveTableIfExists('{{%formie_payments_plans}}');
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

        $this->archiveTableIfExists('{{%formie_payments_subscriptions}}');
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

        $this->archiveTableIfExists('{{%formie_pdftemplates}}');
        $this->createTable('{{%formie_pdftemplates}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string(64)->notNull(),
            'template' => $this->string()->notNull(),
            'filenameFormat' => $this->string()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateDeleted' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%formie_relations}}');
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

        $this->archiveTableIfExists('{{%formie_rows}}');
        $this->createTable('{{%formie_rows}}', [
            'id' => $this->primaryKey(),
            'fieldLayoutId' => $this->integer()->notNull(),
            'fieldLayoutFieldId' => $this->integer()->notNull(),
            'row' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%formie_sentnotifications}}');
        $this->createTable('{{%formie_sentnotifications}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(),
            'formId' => $this->integer(),
            'submissionId' => $this->integer(),
            'notificationId' => $this->integer(),
            'subject' => $this->string(),
            'to' => $this->string(),
            'cc' => $this->string(),
            'bcc' => $this->string(),
            'replyTo' => $this->string(),
            'replyToName' => $this->string(),
            'from' => $this->string(),
            'fromName' => $this->string(),
            'sender' => $this->string(),
            'body' => $this->mediumText(),
            'htmlBody' => $this->mediumText(),
            'info' => $this->text(),
            'success' => $this->boolean(),
            'message' => $this->text(),
            'dateCreated' => $this->dateTime(),
            'dateUpdated' => $this->dateTime(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%formie_statuses}}');
        $this->createTable('{{%formie_statuses}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string(64)->notNull(),
            'color' => $this->enum('color', ['green', 'orange', 'red', 'blue', 'yellow', 'pink', 'purple', 'turquoise', 'light', 'grey', 'black'])
                ->defaultValue('green')
                ->notNull(),
            'description' => $this->string(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'isDefault' => $this->boolean(),
            'dateDeleted' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%formie_stencils}}');
        $this->createTable('{{%formie_stencils}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string(64)->notNull(),
            'data' => $this->mediumText(),
            'templateId' => $this->integer(),
            'submitActionEntryId' => $this->integer(),
            'submitActionEntrySiteId' => $this->integer(),
            'defaultStatusId' => $this->integer(),
            'dateDeleted' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%formie_submissions}}');
        $this->createTable('{{%formie_submissions}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'formId' => $this->integer()->notNull(),
            'statusId' => $this->integer(),
            'userId' => $this->integer(),
            'isIncomplete' => $this->boolean()->defaultValue(false),
            'isSpam' => $this->boolean()->defaultValue(false),
            'spamReason' => $this->text(),
            'spamClass' => $this->string(),
            'snapshot' => $this->text(),
            'ipAddress' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%formie_syncs}}');
        $this->createTable('{{%formie_syncs}}', [
            'id' => $this->primaryKey(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%formie_syncfields}}');
        $this->createTable('{{%formie_syncfields}}', [
            'id' => $this->primaryKey(),
            'syncId' => $this->integer()->notNull(),
            'fieldId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%formie_tokens}}');
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

    public function createIndexes(): void
    {
        $this->createIndex(null, '{{%formie_forms}}', 'templateId', false);
        $this->createIndex(null, '{{%formie_forms}}', 'defaultStatusId', false);
        $this->createIndex(null, '{{%formie_forms}}', 'submitActionEntryId', false);
        $this->createIndex(null, '{{%formie_forms}}', 'submitActionEntrySiteId', false);
        $this->createIndex(null, '{{%formie_forms}}', 'fieldLayoutId', false);
        $this->createIndex(null, '{{%formie_formtemplates}}', 'fieldLayoutId', false);
        $this->createIndex(null, '{{%formie_nested}}', 'fieldId', true);
        $this->createIndex(null, '{{%formie_nested}}', 'fieldLayoutId', false);
        $this->createIndex(null, '{{%formie_nestedfieldrows}}', 'ownerId', false);
        $this->createIndex(null, '{{%formie_nestedfieldrows}}', 'fieldId', false);
        $this->createIndex(null, '{{%formie_nestedfieldrows}}', 'sortOrder', false);
        $this->createIndex(null, '{{%formie_notifications}}', 'formId', false);
        $this->createIndex(null, '{{%formie_notifications}}', 'templateId', false);
        $this->createIndex(null, '{{%formie_pagesettings}}', 'fieldLayoutId', false);
        $this->createIndex(null, '{{%formie_pagesettings}}', 'fieldLayoutTabId', true);
        $this->createIndex(null, '{{%formie_payments}}', 'integrationId', false);
        $this->createIndex(null, '{{%formie_payments}}', 'fieldId', false);
        $this->createIndex(null, '{{%formie_payments}}', 'reference', false);
        $this->createIndex(null, '{{%formie_payments_plans}}', 'integrationId', false);
        $this->createIndex(null, '{{%formie_payments_plans}}', 'handle', true);
        $this->createIndex(null, '{{%formie_payments_plans}}', 'reference', false);
        $this->createIndex(null, '{{%formie_payments_subscriptions}}', 'integrationId', false);
        $this->createIndex(null, '{{%formie_payments_subscriptions}}', 'submissionId', false);
        $this->createIndex(null, '{{%formie_payments_subscriptions}}', 'fieldId', false);
        $this->createIndex(null, '{{%formie_payments_subscriptions}}', 'planId', false);
        $this->createIndex(null, '{{%formie_payments_subscriptions}}', 'reference', false);
        $this->createIndex(null, '{{%formie_payments_subscriptions}}', 'nextPaymentDate', false);
        $this->createIndex(null, '{{%formie_payments_subscriptions}}', 'dateExpired', false);
        $this->createIndex(null, '{{%formie_payments_subscriptions}}', 'dateExpired', false);
        $this->createIndex(null, '{{%formie_relations}}', ['sourceId', 'sourceSiteId', 'targetId'], true);
        $this->createIndex(null, '{{%formie_relations}}', ['sourceId'], false);
        $this->createIndex(null, '{{%formie_relations}}', ['targetId'], false);
        $this->createIndex(null, '{{%formie_relations}}', ['sourceSiteId'], false);
        $this->createIndex(null, '{{%formie_rows}}', 'fieldLayoutId', false);
        $this->createIndex(null, '{{%formie_rows}}', 'fieldLayoutFieldId', true);
        $this->createIndex(null, '{{%formie_stencils}}', 'templateId', false);
        $this->createIndex(null, '{{%formie_stencils}}', 'defaultStatusId', false);
        $this->createIndex(null, '{{%formie_submissions}}', 'formId', false);
        $this->createIndex(null, '{{%formie_submissions}}', 'statusId', false);
        $this->createIndex(null, '{{%formie_submissions}}', 'userId', false);
        $this->createIndex(null, '{{%formie_syncfields}}', ['syncId', 'fieldId'], true);
    }

    public function addForeignKeys(): void
    {
        $this->addForeignKey(null, '{{%formie_forms}}', ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_forms}}', ['templateId'], '{{%formie_formtemplates}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%formie_forms}}', ['defaultStatusId'], '{{%formie_statuses}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%formie_forms}}', ['submitActionEntryId'], '{{%entries}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%formie_forms}}', ['fieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_formtemplates}}', ['fieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_nested}}', ['fieldId'], '{{%fields}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_nested}}', ['fieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_nestedfieldrows}}', ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_nestedfieldrows}}', ['ownerId'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_nestedfieldrows}}', ['fieldId'], '{{%fields}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_notifications}}', ['formId'], '{{%formie_forms}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_notifications}}', ['templateId'], '{{%formie_emailtemplates}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%formie_notifications}}', ['pdfTemplateId'], '{{%formie_pdftemplates}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%formie_pagesettings}}', ['fieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_pagesettings}}', ['fieldLayoutTabId'], '{{%fieldlayouttabs}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_payments}}', ['submissionId'], '{{%formie_submissions}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_payments}}', ['subscriptionId'], '{{%formie_payments_subscriptions}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_payments}}', ['fieldId'], '{{%fields}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_payments}}', ['integrationId'], '{{%formie_integrations}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_payments_plans}}', ['integrationId'], '{{%formie_integrations}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_payments_subscriptions}}', ['integrationId'], '{{%formie_integrations}}', ['id'], 'RESTRICT', null);
        $this->addForeignKey(null, '{{%formie_payments_subscriptions}}', ['submissionId'], '{{%formie_submissions}}', ['id'], 'RESTRICT', null);
        $this->addForeignKey(null, '{{%formie_payments_subscriptions}}', ['fieldId'], '{{%fields}}', ['id'], 'RESTRICT', null);
        $this->addForeignKey(null, '{{%formie_payments_subscriptions}}', ['planId'], '{{%formie_payments_plans}}', ['id'], 'RESTRICT', null);
        $this->addForeignKey(null, '{{%formie_relations}}', ['sourceId'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_relations}}', ['sourceSiteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%formie_relations}}', ['targetId'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_rows}}', ['fieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_rows}}', ['fieldLayoutFieldId'], '{{%fieldlayoutfields}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_sentnotifications}}', ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_sentnotifications}}', ['formId'], '{{%formie_forms}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_sentnotifications}}', ['submissionId'], '{{%formie_submissions}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_sentnotifications}}', ['notificationId'], '{{%formie_notifications}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_stencils}}', ['templateId'], '{{%formie_formtemplates}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%formie_stencils}}', ['defaultStatusId'], '{{%formie_statuses}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%formie_submissions}}', ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_submissions}}', ['formId'], '{{%formie_forms}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_submissions}}', ['statusId'], '{{%formie_statuses}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%formie_submissions}}', ['userId'], '{{%users}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%formie_syncfields}}', ['syncId'], '{{%formie_syncs}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_syncfields}}', ['fieldId'], '{{%fields}}', ['id'], 'CASCADE', null);
    }

    public function removeTables(): void
    {
        $tables = [
            'formie_emailtemplates',
            'formie_forms',
            'formie_formtemplates',
            'formie_integrations',
            'formie_nested',
            'formie_nestedfieldrows',
            'formie_notifications',
            'formie_pagesettings',
            'formie_payments',
            'formie_payments_plans',
            'formie_payments_subscriptions',
            'formie_pdftemplates',
            'formie_relations',
            'formie_rows',
            'formie_sentnotifications',
            'formie_statuses',
            'formie_stencils',
            'formie_submissions',
            'formie_syncfields',
            'formie_syncs',
            'formie_tokens',
        ];

        foreach ($tables as $table) {
            $this->dropTableIfExists('{{%' . $table . '}}');
        }
    }

    public function removeContent(): void
    {
        // Delete Sent Notification Elements
        $this->delete('{{%elements}}', ['type' => SentNotification::class]);

        // Delete Form Submission Elements
        $this->delete('{{%elements}}', ['type' => Submission::class]);

        // Delete Form Elements
        $this->delete('{{%elements}}', ['type' => Form::class]);

        // Delete NestedFieldRow Elements
        $this->delete('{{%elements}}', ['type' => NestedFieldRow::class]);
    }

    public function dropProjectConfig(): void
    {
        Craft::$app->projectConfig->remove('formie');
    }

    public function insertDefaultData(): void
    {
        $projectConfig = Craft::$app->projectConfig;

        // Don't make the same config changes twice
        $installed = ($projectConfig->get('plugins.formie', true) !== null);
        $configExists = ($projectConfig->get('formie', true) !== null);

        if (!$installed && !$configExists) {
            $this->_defaultStatuses();
            $this->_defaultStencils();
        }

        // If the config data exists, but we're re-installing, apply it
        if (!$installed && $configExists) {
            $allowAdminChanges = Craft::$app->getConfig()->getGeneral()->allowAdminChanges;

            if (!$allowAdminChanges) {
                return;
            }

            $statuses = $projectConfig->get(Statuses::CONFIG_STATUSES_KEY, true) ?? [];

            foreach ($statuses as $statusUid => $statusData) {
                $projectConfig->processConfigChanges(Statuses::CONFIG_STATUSES_KEY . '.' . $statusUid, true);
            }

            $stencils = $projectConfig->get(Stencils::CONFIG_STENCILS_KEY, true) ?? [];

            foreach ($stencils as $stencilUid => $stencilData) {
                $projectConfig->processConfigChanges(Stencils::CONFIG_STENCILS_KEY . '.' . $stencilUid, true);
            }
        }
    }

    protected function dropForeignKeys(): void
    {
        $tables = [
            'formie_emailtemplates',
            'formie_forms',
            'formie_formtemplates',
            'formie_integrations',
            'formie_nested',
            'formie_nestedfieldrows',
            'formie_notifications',
            'formie_pagesettings',
            'formie_payments',
            'formie_payments_plans',
            'formie_payments_subscriptions',
            'formie_pdftemplates',
            'formie_relations',
            'formie_rows',
            'formie_sentnotifications',
            'formie_statuses',
            'formie_stencils',
            'formie_submissions',
            'formie_syncfields',
            'formie_syncs',
            'formie_tokens',
        ];

        foreach ($tables as $table) {
            if ($this->db->tableExists('{{%' . $table . '}}')) {
                MigrationHelper::dropAllForeignKeysOnTable('{{%' . $table . '}}', $this);
            }
        }
    }

    private function _defaultStatuses(): void
    {
        $statuses = [
            [
                'name' => 'New',
                'handle' => 'new',
                'color' => 'green',
                'sortOrder' => 1,
                'isDefault' => 1,
            ],
        ];

        foreach ($statuses as $status) {
            $orderStatus = new Status($status);
            Formie::$plugin->getStatuses()->saveStatus($orderStatus);
        }
    }

    private function _defaultStencils(): void
    {
        $stencils = [
            [
                'name' => Craft::t('formie', 'Contact Form'),
                'handle' => 'contactForm',
                'file' => Craft::getAlias('@verbb/formie/migrations/stencils/contact-form.json'),
            ],
        ];

        foreach ($stencils as $stencilInfo) {
            $data = Json::decodeIfJson(file_get_contents($stencilInfo['file']));

            $stencil = new Stencil();
            $stencil->name = $stencilInfo['name'];
            $stencil->handle = $stencilInfo['handle'];
            $stencil->data = new StencilData($data);

            Formie::$plugin->getStencils()->saveStencil($stencil);
        }
    }
}
