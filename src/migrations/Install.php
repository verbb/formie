<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\SentNotification;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\Table;
use verbb\formie\models\FormStatus;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use verbb\formie\models\SubmissionStatus;
use verbb\formie\services\CaptchaProviders;
use verbb\formie\services\FormGroups;
use verbb\formie\services\FormStatuses;
use verbb\formie\services\Reports;
use verbb\formie\services\ScheduledReports;
use verbb\formie\services\SpamProtection;
use verbb\formie\services\Stencils;
use verbb\formie\services\SubmissionStatuses;

use Craft;
use craft\db\Migration;
use craft\helpers\Json;
use craft\helpers\MigrationHelper;

use verbb\auth\Auth;

class Install extends Migration
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        // Seed after the schema transaction on every supported Craft version.
        $this->on(self::EVENT_AFTER_UP, fn() => $this->insertDefaultData());
    }

    public function safeUp(): bool
    {
        // Ensure that the Auth module kicks off setting up tables
        Auth::getInstance()->migrator->up();

        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropProjectConfig();
        $this->dropForeignKeys();
        $this->removeTables();
        $this->removeContent();

        // Delete all tokens for this plugin
        Auth::getInstance()->getTokens()->deleteTokensByOwner('formie');

        return true;
    }

    public function createTables(): void
    {
        $this->archiveTableIfExists(Table::FORMIE_EMAIL_TEMPLATES);
        $this->createTable(Table::FORMIE_EMAIL_TEMPLATES, [
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

        $this->archiveTableIfExists(Table::FORMIE_FIELD_LAYOUT_PAGES);
        $this->createTable(Table::FORMIE_FIELD_LAYOUT_PAGES, [
            'id' => $this->primaryKey(),
            'layoutId' => $this->integer()->notNull(),
            'label' => $this->text()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'settings' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_FIELD_LAYOUT_ROWS);
        $this->createTable(Table::FORMIE_FIELD_LAYOUT_ROWS, [
            'id' => $this->primaryKey(),
            'layoutId' => $this->integer()->notNull(),
            'pageId' => $this->integer()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_FIELD_LAYOUTS);
        $this->createTable(Table::FORMIE_FIELD_LAYOUTS, [
            'id' => $this->primaryKey(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_FIELDS);
        $this->createTable(Table::FORMIE_FIELDS, [
            'id' => $this->primaryKey(),
            'type' => $this->string()->notNull(),
            'label' => $this->text()->notNull(),
            'handle' => $this->string(64)->notNull(),
            'settings' => $this->mediumText(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_FIELD_SITE_OVERRIDES);
        $this->createTable(Table::FORMIE_FIELD_SITE_OVERRIDES, [
            'id' => $this->primaryKey(),
            'fieldId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'overrides' => $this->json()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_FORM_FIELDS);
        $this->createTable(Table::FORMIE_FORM_FIELDS, [
            'id' => $this->primaryKey(),
            'fieldId' => $this->integer()->notNull(),
            'layoutId' => $this->integer()->notNull(),
            'pageId' => $this->integer()->notNull(),
            'rowId' => $this->integer()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'settings' => $this->mediumText(),
            'reference' => $this->string(36),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_FORM_GROUPS);
        $this->createTable(Table::FORMIE_FORM_GROUPS, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string(64)->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateDeleted' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_FORMS);
        $this->createTable(Table::FORMIE_FORMS, [
            'id' => $this->primaryKey(),
            'handle' => $this->string(64)->notNull(),
            'settings' => $this->mediumText(),
            'layoutId' => $this->integer(),
            'templateId' => $this->integer(),
            'groupId' => $this->integer(),
            'formStatusId' => $this->integer(),
            'sourceSiteId' => $this->integer(),
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
            'createdById' => $this->integer(),
            'updatedById' => $this->integer(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_FORM_SITE_OVERRIDES);
        $this->createTable(Table::FORMIE_FORM_SITE_OVERRIDES, [
            'id' => $this->primaryKey(),
            'formId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'overrides' => $this->json()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_FORM_TEMPLATES);
        $this->createTable(Table::FORMIE_FORM_TEMPLATES, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string(64)->notNull(),
            'template' => $this->string(),
            'useCustomTemplates' => $this->boolean()->defaultValue(true),
            'outputCss' => $this->boolean()->defaultValue(true),
            'outputJs' => $this->boolean()->defaultValue(true),
            'outputCssLocation' => $this->string(),
            'outputJsLocation' => $this->string(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'fieldLayoutId' => $this->integer(),
            'dateDeleted' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_INTEGRATIONS);
        $this->createTable(Table::FORMIE_INTEGRATIONS, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string(64)->notNull(),
            'scope' => $this->string(16)->notNull()->defaultValue('project'),
            'type' => $this->string()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'enabled' => $this->string()->notNull()->defaultValue('true'),
            'settings' => $this->mediumText(),
            'cache' => $this->longText(),
            'dateDeleted' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_CAPTCHA_PROVIDERS);
        $this->createTable(Table::FORMIE_CAPTCHA_PROVIDERS, [
            'id' => $this->primaryKey(),
            'handle' => $this->string(64)->notNull(),
            'type' => $this->string()->notNull(),
            'scope' => $this->string(16)->notNull()->defaultValue('project'),
            'enabled' => $this->string()->notNull()->defaultValue('false'),
            'saveSpam' => $this->boolean(),
            'settings' => $this->mediumText(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_SPAM_SETTINGS);
        $this->createTable(Table::FORMIE_SPAM_SETTINGS, [
            'id' => $this->primaryKey(),
            'scope' => $this->string(16)->notNull()->defaultValue('project'),
            'saveSpam' => $this->boolean()->notNull()->defaultValue(true),
            'spamLimit' => $this->integer()->notNull()->defaultValue(500),
            'spamEmailNotifications' => $this->boolean()->notNull()->defaultValue(false),
            'spamBehaviour' => $this->string()->notNull()->defaultValue('showSuccess'),
            'spamBehaviourMessage' => $this->text(),
            'spamKeywords' => $this->mediumText(),
            'enableHoneypot' => $this->boolean()->notNull()->defaultValue(true),
            'honeypotFieldName' => $this->string()->notNull()->defaultValue('formieHoneypot'),
            'enableMinimumSubmitTime' => $this->boolean()->notNull()->defaultValue(true),
            'minimumSubmitTime' => $this->integer()->notNull()->defaultValue(3),
            'enableReplayProtection' => $this->boolean()->notNull()->defaultValue(true),
            'enableBlockedEmailDomains' => $this->boolean()->notNull()->defaultValue(false),
            'blockedEmailDomains' => $this->mediumText(),
            'enableBlockFreeEmailDomains' => $this->boolean()->notNull()->defaultValue(false),
            'enableAllowedEmailDomains' => $this->boolean()->notNull()->defaultValue(false),
            'allowedEmailDomains' => $this->mediumText(),
            'enableFormSubmitExpiration' => $this->boolean()->notNull()->defaultValue(false),
            'formSubmitExpiration' => $this->integer()->notNull()->defaultValue(86400),
            'enableSuspiciousTextDetection' => $this->boolean()->notNull()->defaultValue(false),
            'suspiciousTextAllowedTerms' => $this->mediumText(),
            'enableMaximumLinks' => $this->boolean()->notNull()->defaultValue(false),
            'maximumLinks' => $this->integer()->notNull()->defaultValue(10),
            'enableGlobalSubmissionThrottling' => $this->boolean()->notNull()->defaultValue(false),
            'globalSubmissionThrottleLimit' => $this->integer()->notNull()->defaultValue(50),
            'globalSubmissionThrottleWindowSeconds' => $this->integer()->notNull()->defaultValue(60),
            'enableIpSubmissionThrottling' => $this->boolean()->notNull()->defaultValue(false),
            'ipSubmissionThrottleMinutes' => $this->integer()->notNull()->defaultValue(5),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_NOTIFICATIONS);
        $this->createTable(Table::FORMIE_NOTIFICATIONS, [
            'id' => $this->primaryKey(),
            'formId' => $this->integer()->notNull(),
            'templateId' => $this->integer(),
            'pdfTemplateId' => $this->integer(),
            'name' => $this->text()->notNull(),
            'handle' => $this->string(64)->notNull(),
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
            'dispatchTiming' => $this->string(32)->notNull()->defaultValue('default'),
            'customSettings' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_PAYMENTS);
        $this->createTable(Table::FORMIE_PAYMENTS, [
            'id' => $this->primaryKey(),
            'integrationId' => $this->integer(),
            'submissionId' => $this->integer(),
            'fieldId' => $this->integer(),
            'subscriptionId' => $this->integer(),
            'amount' => $this->string(80),
            'currency' => $this->string(),
            'status' => $this->string(32)->notNull(),
            'reference' => $this->string(),
            'code' => $this->string(),
            'message' => $this->text(),
            'redirectUrl' => $this->text(),
            'note' => $this->mediumText(),
            'response' => $this->text(),
            'version' => $this->integer()->notNull()->defaultValue(0),
            'history' => $this->mediumText(),
            'scope' => $this->text(),
            'idempotencyKey' => $this->string(80),
            'lastReconciledAt' => $this->bigInteger(),
            'nextReconcileAt' => $this->bigInteger(),
            'reconciliationAttempts' => $this->integer()->notNull()->defaultValue(0),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_PAYMENT_PLANS);
        $this->createTable(Table::FORMIE_PAYMENT_PLANS, [
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

        $this->archiveTableIfExists(Table::FORMIE_SUBSCRIPTIONS);
        $this->createTable(Table::FORMIE_SUBSCRIPTIONS, [
            'id' => $this->primaryKey(),
            'integrationId' => $this->integer(),
            'submissionId' => $this->integer(),
            'fieldId' => $this->integer(),
            'planId' => $this->integer(),
            'reference' => $this->string(),
            'subscriptionData' => $this->text(),
            'trialDays' => $this->integer()->notNull(),
            'providerStatus' => $this->string(80),
            'startedAt' => $this->dateTime(),
            'trialStartsAt' => $this->dateTime(),
            'trialEndsAt' => $this->dateTime(),
            'currentPeriodStartsAt' => $this->dateTime(),
            'currentPeriodEndsAt' => $this->dateTime(),
            'nextPaymentAt' => $this->dateTime(),
            'pausedAt' => $this->dateTime(),
            'cancelAt' => $this->dateTime(),
            'cancelledAt' => $this->dateTime(),
            'endedAt' => $this->dateTime(),
            'cancellationMode' => $this->string(32),
            'version' => $this->integer()->notNull()->defaultValue(0),
            'history' => $this->mediumText(),
            'scope' => $this->text(),
            'idempotencyKey' => $this->string(80),
            'status' => $this->string(32)->notNull()->defaultValue('unknown'),
            'archivedAt' => $this->dateTime(),
            'providerUpdatedAt' => $this->bigInteger(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_WEBHOOK_RECEIPTS);
        $this->createTable(Table::FORMIE_WEBHOOK_RECEIPTS, [
            'id' => $this->primaryKey(),
            'identity' => $this->char(64)->notNull(),
            'integrationId' => $this->integer()->notNull(),
            'integrationUid' => $this->uid()->notNull(),
            'accountFingerprint' => $this->char(64)->notNull(),
            'environment' => $this->string(80)->notNull(),
            'eventId' => $this->string(255)->notNull(),
            'eventType' => $this->string(255)->notNull(),
            'resourceType' => $this->string(255),
            'resourceReference' => $this->string(255),
            'providerCreatedAt' => $this->bigInteger(),
            'bodyHash' => $this->char(64)->notNull(),
            'eventHash' => $this->char(64)->notNull(),
            'history' => $this->mediumText()->notNull(),
            'body' => $this->mediumText()->notNull(),
            'headers' => $this->text()->notNull(),
            'payload' => $this->mediumText()->notNull(),
            'display' => $this->mediumText()->notNull(),
            'status' => $this->string(32)->notNull(),
            'attempts' => $this->integer()->notNull()->defaultValue(0),
            'error' => $this->text(),
            'receivedAt' => $this->dateTime()->notNull(),
            'verifiedAt' => $this->dateTime()->notNull(),
            'scheduledAt' => $this->dateTime(),
            'startedAt' => $this->dateTime(),
            'nextAttemptAt' => $this->dateTime(),
            'processedAt' => $this->dateTime(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_PAYMENT_CAPABILITIES);
        $this->createTable(Table::FORMIE_PAYMENT_CAPABILITIES, [
            'id' => $this->primaryKey(),
            'tokenHash' => $this->char(64)->notNull(),
            'purpose' => $this->string(32)->notNull(),
            'resourceId' => $this->integer()->notNull(),
            'scope' => $this->text()->notNull(),
            'expiresAt' => $this->integer()->notNull(),
            'revokedAt' => $this->integer(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_PDF_TEMPLATES);
        $this->createTable(Table::FORMIE_PDF_TEMPLATES, [
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

        $this->archiveTableIfExists(Table::FORMIE_RELATIONS);
        $this->createTable(Table::FORMIE_RELATIONS, [
            'id' => $this->primaryKey(),
            'type' => $this->string(255)->notNull(),
            'sourceId' => $this->integer()->notNull(),
            'sourceSiteId' => $this->integer(),
            'targetId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_REPORTS);
        $this->createTable(Table::FORMIE_REPORTS, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string(64)->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateDeleted' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_SCHEDULED_REPORTS);
        $this->createTable(Table::FORMIE_SCHEDULED_REPORTS, [
            'id' => $this->primaryKey(),
            'reportId' => $this->integer()->notNull(),
            'name' => $this->string()->notNull(),
            'enabled' => $this->boolean()->notNull()->defaultValue(true),
            'lastSentAt' => $this->dateTime(),
            'dateDeleted' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_REPORT_EXPORTS);
        $this->createTable(Table::FORMIE_REPORT_EXPORTS, [
            'id' => $this->primaryKey(),
            'reportId' => $this->integer()->notNull(),
            'userId' => $this->integer(),
            'scheduledReportId' => $this->integer(),
            'source' => $this->string(32)->notNull()->defaultValue('interactive'),
            'status' => $this->string(32)->notNull()->defaultValue('pending'),
            'format' => $this->string(16)->notNull()->defaultValue('csv'),
            'context' => $this->text(),
            'filename' => $this->string(),
            'filePath' => $this->string(),
            'fileSize' => $this->bigInteger()->unsigned(),
            'downloadTokenHash' => $this->char(64),
            'downloadUrl' => $this->text(),
            'notifyEmail' => $this->string(),
            'error' => $this->text(),
            'dateExpires' => $this->dateTime(),
            'dateDownloaded' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_SENT_NOTIFICATIONS);
        $this->createTable(Table::FORMIE_SENT_NOTIFICATIONS, [
            'id' => $this->primaryKey(),
            'title' => $this->string(),
            'formId' => $this->integer(),
            'submissionId' => $this->integer(),
            'notificationId' => $this->integer(),
            'subject' => $this->text(),
            'to' => $this->text(),
            'cc' => $this->text(),
            'bcc' => $this->text(),
            'replyTo' => $this->text(),
            'replyToName' => $this->text(),
            'from' => $this->text(),
            'fromName' => $this->text(),
            'sender' => $this->text(),
            'body' => $this->mediumText(),
            'htmlBody' => $this->mediumText(),
            'info' => $this->text(),
            'success' => $this->boolean(),
            'message' => $this->text(),
            'dateCreated' => $this->dateTime(),
            'dateUpdated' => $this->dateTime(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_SUBMISSION_STATUSES);
        $this->createTable(Table::FORMIE_SUBMISSION_STATUSES, [
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

        $this->archiveTableIfExists(Table::FORMIE_FORM_STATUSES);
        $this->createTable(Table::FORMIE_FORM_STATUSES, [
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

        $this->archiveTableIfExists(Table::FORMIE_STENCILS);
        $this->createTable(Table::FORMIE_STENCILS, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string(64)->notNull(),
            'scope' => $this->string(16)->notNull()->defaultValue('project'),
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

        $this->archiveTableIfExists(Table::FORMIE_SUBMISSIONS);
        $this->createTable(Table::FORMIE_SUBMISSIONS, [
            'id' => $this->primaryKey(),
            'content' => $this->json(),
            'formId' => $this->integer()->notNull(),
            'statusId' => $this->integer(),
            'userId' => $this->integer(),
            'updatedById' => $this->integer(),
            'isIncomplete' => $this->boolean()->defaultValue(false),
            'isSpam' => $this->boolean()->defaultValue(false),
            'spamReason' => $this->text(),
            'spamClass' => $this->string(),
            'snapshot' => $this->text(),
            'ipAddress' => $this->string(),
            'signatureAccessKey' => $this->string(64),
            'legacySignatureAccess' => $this->boolean()->notNull()->defaultValue(false),
            'stateVersion' => $this->integer()->notNull()->defaultValue(0),
            'metadata' => $this->json(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable(Table::FORMIE_SUBMISSION_QUIZ_RESULTS, [
            'id' => $this->primaryKey(),
            'submissionId' => $this->integer()->notNull(),
            'score' => $this->decimal(12, 4)->notNull()->defaultValue(0),
            'maxScore' => $this->decimal(12, 4)->notNull()->defaultValue(0),
            'percentage' => $this->decimal(8, 2)->notNull()->defaultValue(0),
            'passed' => $this->boolean()->notNull()->defaultValue(false),
            'questionResults' => $this->json(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_SUBMISSION_OPERATIONS);
        $this->createTable(Table::FORMIE_SUBMISSION_OPERATIONS, [
            'id' => $this->primaryKey(),
            'operationHash' => $this->char(64)->notNull(),
            'requestHash' => $this->char(64),
            'fingerprint' => $this->char(64)->notNull(),
            'formId' => $this->integer()->notNull(),
            'submissionId' => $this->integer(),
            'operation' => $this->string(32)->notNull(),
            'state' => $this->string(16)->notNull(),
            'outcome' => $this->mediumText(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'expiresAt' => $this->dateTime()->notNull(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_SUBMISSION_PROGRESS);
        $this->createTable(Table::FORMIE_SUBMISSION_PROGRESS, [
            'id' => $this->primaryKey(),
            'formId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'submissionId' => $this->integer(),
            'browserHash' => $this->char(64)->notNull(),
            'currentPageId' => $this->integer(),
            'content' => $this->mediumText(),
            'version' => $this->integer()->notNull()->defaultValue(0),
            'expiresAt' => $this->integer()->notNull(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_SUBMISSION_GRANTS);
        $this->createTable(Table::FORMIE_SUBMISSION_GRANTS, [
            'id' => $this->primaryKey(),
            'tokenHash' => $this->string(80),
            'bindingHash' => $this->char(64),
            'parentId' => $this->integer(),
            'progressId' => $this->integer(),
            'submissionId' => $this->integer(),
            'formId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'purpose' => $this->string(32)->notNull(),
            'expiresAt' => $this->integer()->notNull(),
            'revokedAt' => $this->integer(),
            'dateCreated' => $this->dateTime()->notNull(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_SUBMISSION_WORKFLOW);
        $this->createTable(Table::FORMIE_SUBMISSION_WORKFLOW, [
            'id' => $this->primaryKey(),
            'submissionId' => $this->integer()->notNull(),
            'stage' => $this->string(64)->notNull(),
            'idempotencyKey' => $this->string(255),
            'isDispatched' => $this->boolean()->notNull()->defaultValue(true),
            'dateDispatched' => $this->dateTime()->notNull(),
            'meta' => $this->mediumText(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_PENDING_UPLOADS);
        $this->createTable(Table::FORMIE_PENDING_UPLOADS, [
            'id' => $this->primaryKey(),
            'assetId' => $this->integer()->notNull(),
            'formId' => $this->integer(),
            'submissionId' => $this->integer(),
            'fieldUid' => $this->string(64),
            'isFinalized' => $this->boolean()->notNull()->defaultValue(false),
            'state' => $this->string(16)->notNull()->defaultValue('staged'),
            'siteId' => $this->integer(),
            'browserHash' => $this->char(64),
            'contentKey' => $this->string(255),
            'progressId' => $this->integer(),
            'expiresAt' => $this->integer(),
            'capabilities' => $this->text(),
            'promotionFolderId' => $this->integer(),
            'promotionFilename' => $this->string(255),
            'promotionSourceFolderId' => $this->integer(),
            'contentHash' => $this->char(64),
            'promotionState' => $this->string(16),
            'failureCode' => $this->string(64),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%formie_delivery_attempts}}');
        $this->createTable('{{%formie_delivery_attempts}}', [
            'id' => $this->primaryKey(),
            'uid' => $this->uid()->notNull(),
            'identity' => $this->string(64)->notNull(),
            'submissionId' => $this->integer()->notNull(),
            'formId' => $this->integer()->notNull(),
            'binding' => $this->string(255)->notNull(),
            'step' => $this->string(255)->notNull(),
            'parentUid' => $this->string(36),
            'executionUid' => $this->string(255)->notNull(),
            'execution' => $this->string(16)->notNull(),
            'status' => $this->string(16)->notNull()->defaultValue('pending'),
            'payloadHash' => $this->string(64),
            'requestKey' => $this->uid()->notNull(),
            'result' => $this->text(),
            'data' => $this->mediumText(),
            'response' => $this->mediumText(),
            'startedAt' => $this->dateTime(),
            'completedAt' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
        ]);

        $this->archiveTableIfExists('{{%formie_delivery_diagnostics}}');
        $this->createTable('{{%formie_delivery_diagnostics}}', [
            'id' => $this->primaryKey(),
            'attemptId' => $this->integer()->notNull(),
            'checkpoint' => $this->string(64)->notNull(),
            'data' => $this->text()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
        ]);

        $this->archiveTableIfExists('{{%formie_instance_configs}}');
        $this->createTable('{{%formie_instance_configs}}', [
            'id' => $this->primaryKey(),
            'tokenHash' => $this->string(64)->notNull(),
            'formId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'config' => $this->mediumText()->notNull(),
            'expiresAt' => $this->integer()->notNull(),
        ]);

        $this->archiveTableIfExists('{{%formie_integration_run_contexts}}');
        $this->createTable('{{%formie_integration_run_contexts}}', [
            'id' => $this->primaryKey(),
            'submissionId' => $this->integer()->notNull(),
            'runUid' => $this->string(255)->notNull(),
            'context' => $this->mediumText()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
        ]);

        $this->archiveTableIfExists('{{%formie_submission_dispatches}}');
        $this->createTable('{{%formie_submission_dispatches}}', [
            'id' => $this->primaryKey(),
            'submissionId' => $this->integer()->notNull(),
            'uid' => $this->string(255)->notNull(),
            'identity' => $this->string(64)->notNull(),
            'kind' => $this->string(32)->notNull(),
            'status' => $this->string(32)->notNull(),
            'submissionVersion' => $this->integer()->notNull(),
            'command' => $this->text(),
            'schedulingComplete' => $this->boolean()->notNull()->defaultValue(false),
            'failureCode' => $this->string(64),
            'scheduledAt' => $this->dateTime(),
            'startedAt' => $this->dateTime(),
            'completedAt' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
        ]);

    }

    public function createIndexes(): void
    {
        $this->createIndex(null, Table::FORMIE_FIELD_LAYOUT_PAGES, 'layoutId', false);
        $this->createIndex(null, Table::FORMIE_FIELD_LAYOUT_ROWS, 'layoutId', false);
        $this->createIndex(null, Table::FORMIE_FIELD_LAYOUT_ROWS, 'pageId', false);
        $this->createIndex(null, Table::FORMIE_FIELDS, 'handle', false);
        $this->createIndex(null, Table::FORMIE_FIELD_SITE_OVERRIDES, ['fieldId', 'siteId'], true);
        $this->createIndex(null, Table::FORMIE_FIELD_SITE_OVERRIDES, 'siteId', false);
        $this->createIndex(null, Table::FORMIE_FORM_FIELDS, 'fieldId', false);
        $this->createIndex(null, Table::FORMIE_FORM_FIELDS, 'layoutId', false);
        $this->createIndex(null, Table::FORMIE_FORM_FIELDS, 'pageId', false);
        $this->createIndex(null, Table::FORMIE_FORM_FIELDS, 'rowId', false);
        $this->createIndex(null, Table::FORMIE_FORM_FIELDS, 'reference', true);
        $this->createIndex(null, Table::FORMIE_FORMS, 'layoutId', false);
        $this->createIndex(null, Table::FORMIE_FORMS, 'templateId', false);
        $this->createIndex(null, Table::FORMIE_FORMS, 'groupId', false);
        $this->createIndex(null, Table::FORMIE_FORMS, 'formStatusId', false);
        $this->createIndex(null, Table::FORMIE_FORMS, 'sourceSiteId', false);
        $this->createIndex(null, Table::FORMIE_FORMS, 'defaultStatusId', false);
        $this->createIndex(null, Table::FORMIE_FORMS, 'submitActionEntryId', false);
        $this->createIndex(null, Table::FORMIE_FORMS, 'submitActionEntrySiteId', false);
        $this->createIndex(null, Table::FORMIE_FORMS, 'createdById', false);
        $this->createIndex(null, Table::FORMIE_FORMS, 'updatedById', false);
        $this->createIndex(null, Table::FORMIE_FORM_SITE_OVERRIDES, ['formId', 'siteId'], true);
        $this->createIndex(null, Table::FORMIE_FORM_SITE_OVERRIDES, 'siteId', false);
        $this->createIndex(null, Table::FORMIE_FORM_TEMPLATES, 'fieldLayoutId', false);
        $this->createIndex(null, Table::FORMIE_NOTIFICATIONS, 'formId', false);
        $this->createIndex(null, Table::FORMIE_NOTIFICATIONS, 'templateId', false);
        $this->createIndex(null, Table::FORMIE_PAYMENTS, 'integrationId', false);
        $this->createIndex(null, Table::FORMIE_PAYMENTS, 'fieldId', false);
        $this->createIndex(null, Table::FORMIE_PAYMENTS, 'submissionId', false);
        $this->createIndex(null, Table::FORMIE_PAYMENTS, 'reference', false);
        $this->createIndex(null, Table::FORMIE_PAYMENTS, 'idempotencyKey', true);
        $this->createIndex(null, Table::FORMIE_PAYMENT_PLANS, 'integrationId', false);
        $this->createIndex(null, Table::FORMIE_PAYMENT_PLANS, 'handle', true);
        $this->createIndex(null, Table::FORMIE_PAYMENT_PLANS, 'reference', false);
        $this->createIndex(null, Table::FORMIE_SUBSCRIPTIONS, 'integrationId', false);
        $this->createIndex(null, Table::FORMIE_SUBSCRIPTIONS, 'submissionId', false);
        $this->createIndex(null, Table::FORMIE_SUBSCRIPTIONS, 'fieldId', false);
        $this->createIndex(null, Table::FORMIE_SUBSCRIPTIONS, 'planId', false);
        $this->createIndex(null, Table::FORMIE_SUBSCRIPTIONS, 'reference', false);
        $this->createIndex(null, Table::FORMIE_SUBSCRIPTIONS, 'nextPaymentAt', false);
        $this->createIndex(null, Table::FORMIE_SUBSCRIPTIONS, 'cancelAt', false);
        $this->createIndex(null, Table::FORMIE_SUBSCRIPTIONS, 'endedAt', false);
        $this->createIndex(null, Table::FORMIE_SUBSCRIPTIONS, 'idempotencyKey', true);
        $this->createIndex(null, Table::FORMIE_WEBHOOK_RECEIPTS, 'identity', true);
        $this->createIndex(null, Table::FORMIE_WEBHOOK_RECEIPTS, ['status', 'nextAttemptAt'], false);
        $this->createIndex(null, Table::FORMIE_WEBHOOK_RECEIPTS, ['integrationUid', 'eventId'], false);
        $this->createIndex(null, Table::FORMIE_PAYMENT_CAPABILITIES, 'tokenHash', true);
        $this->createIndex(null, Table::FORMIE_RELATIONS, ['sourceId', 'sourceSiteId', 'targetId'], true);
        $this->createIndex(null, Table::FORMIE_RELATIONS, ['sourceId'], false);
        $this->createIndex(null, Table::FORMIE_RELATIONS, ['targetId'], false);
        $this->createIndex(null, Table::FORMIE_RELATIONS, ['sourceSiteId'], false);
        $this->createIndex(null, Table::FORMIE_STENCILS, 'templateId', false);
        $this->createIndex(null, Table::FORMIE_STENCILS, 'defaultStatusId', false);
        $this->createIndex(null, Table::FORMIE_SUBMISSIONS, 'formId', false);
        $this->createIndex(null, Table::FORMIE_SUBMISSIONS, 'statusId', false);
        $this->createIndex(null, Table::FORMIE_SUBMISSIONS, 'userId', false);
        $this->createIndex(null, Table::FORMIE_SUBMISSIONS, 'updatedById', false);
        $this->createIndex(null, Table::FORMIE_SUBMISSION_QUIZ_RESULTS, 'submissionId', true);
        $this->createIndex(null, Table::FORMIE_SUBMISSION_OPERATIONS, 'operationHash', true);
        $this->createIndex(null, Table::FORMIE_SUBMISSION_OPERATIONS, 'expiresAt', false);
        $this->createIndex(null, Table::FORMIE_SUBMISSION_OPERATIONS, 'requestHash', true);
        $this->createIndex(null, Table::FORMIE_SUBMISSION_PROGRESS, 'submissionId', true);
        $this->createIndex(null, Table::FORMIE_SUBMISSION_PROGRESS, 'expiresAt', false);
        $this->createIndex(null, Table::FORMIE_SUBMISSION_GRANTS, 'tokenHash', true);
        $this->createIndex(null, Table::FORMIE_SUBMISSION_GRANTS, ['formId', 'siteId', 'bindingHash'], false);
        $this->createIndex(null, Table::FORMIE_SUBMISSION_GRANTS, 'expiresAt', false);
        $this->createIndex(null, Table::FORMIE_SUBMISSION_WORKFLOW, 'submissionId', false);
        $this->createIndex(null, Table::FORMIE_SUBMISSION_WORKFLOW, ['submissionId', 'stage', 'idempotencyKey'], true);
        $this->createIndex(null, Table::FORMIE_PENDING_UPLOADS, 'assetId', true);
        $this->createIndex(null, Table::FORMIE_PENDING_UPLOADS, 'submissionId', false);
        $this->createIndex(null, Table::FORMIE_PENDING_UPLOADS, ['isFinalized', 'dateUpdated'], false);
        $this->createIndex(null, '{{%formie_delivery_attempts}}', 'uid', true);
        $this->createIndex(null, '{{%formie_delivery_attempts}}', 'identity', true);
        $this->createIndex(null, '{{%formie_delivery_attempts}}', ['submissionId', 'executionUid'], false);
        $this->createIndex(null, '{{%formie_delivery_attempts}}', 'parentUid', false);
        $this->createIndex(null, '{{%formie_delivery_diagnostics}}', ['attemptId', 'id'], false);
        $this->createIndex(null, '{{%formie_instance_configs}}', 'tokenHash', true);
        $this->createIndex(null, '{{%formie_instance_configs}}', 'expiresAt', false);
        $this->createIndex(null, '{{%formie_integration_run_contexts}}', ['submissionId', 'runUid'], true);
        $this->createIndex(null, '{{%formie_submission_dispatches}}', 'identity', true);
        $this->createIndex(null, '{{%formie_submission_dispatches}}', ['submissionId', 'uid'], true);
        $this->createIndex(null, '{{%formie_submission_dispatches}}', ['schedulingComplete', 'status', 'scheduledAt'], false);
    }

    public function addForeignKeys(): void
    {
        $this->addForeignKey(null, Table::FORMIE_FIELD_LAYOUT_PAGES, ['layoutId'], Table::FORMIE_FIELD_LAYOUTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FIELD_LAYOUT_ROWS, ['layoutId'], Table::FORMIE_FIELD_LAYOUTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FIELD_LAYOUT_ROWS, ['pageId'], Table::FORMIE_FIELD_LAYOUT_PAGES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FIELD_SITE_OVERRIDES, ['fieldId'], Table::FORMIE_FIELDS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FIELD_SITE_OVERRIDES, ['siteId'], Table::SITES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FORM_FIELDS, ['fieldId'], Table::FORMIE_FIELDS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FORM_FIELDS, ['layoutId'], Table::FORMIE_FIELD_LAYOUTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FORM_FIELDS, ['pageId'], Table::FORMIE_FIELD_LAYOUT_PAGES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FORM_FIELDS, ['rowId'], Table::FORMIE_FIELD_LAYOUT_ROWS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FORMS, ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FORMS, ['layoutId'], Table::FORMIE_FIELD_LAYOUTS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_FORMS, ['templateId'], Table::FORMIE_FORM_TEMPLATES, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_FORMS, ['groupId'], Table::FORMIE_FORM_GROUPS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_FORMS, ['formStatusId'], Table::FORMIE_FORM_STATUSES, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_FORMS, ['sourceSiteId'], '{{%sites}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_FORMS, ['defaultStatusId'], Table::FORMIE_SUBMISSION_STATUSES, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_FORMS, ['submitActionEntryId'], '{{%entries}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_FORMS, ['createdById'], '{{%users}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_FORMS, ['updatedById'], '{{%users}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_FORM_SITE_OVERRIDES, ['formId'], Table::FORMIE_FORMS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FORM_SITE_OVERRIDES, ['siteId'], Table::SITES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FORM_TEMPLATES, ['fieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_NOTIFICATIONS, ['formId'], Table::FORMIE_FORMS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_NOTIFICATIONS, ['templateId'], Table::FORMIE_EMAIL_TEMPLATES, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_NOTIFICATIONS, ['pdfTemplateId'], Table::FORMIE_PDF_TEMPLATES, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_PAYMENTS, ['submissionId'], Table::FORMIE_SUBMISSIONS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_PAYMENTS, ['subscriptionId'], Table::FORMIE_SUBSCRIPTIONS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_PAYMENTS, ['fieldId'], Table::FORMIE_FORM_FIELDS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_PAYMENTS, ['integrationId'], Table::FORMIE_INTEGRATIONS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_PAYMENT_PLANS, ['integrationId'], Table::FORMIE_INTEGRATIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_SUBSCRIPTIONS, ['integrationId'], Table::FORMIE_INTEGRATIONS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_SUBSCRIPTIONS, ['submissionId'], Table::FORMIE_SUBMISSIONS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_SUBSCRIPTIONS, ['fieldId'], Table::FORMIE_FORM_FIELDS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_SUBSCRIPTIONS, ['planId'], Table::FORMIE_PAYMENT_PLANS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_RELATIONS, ['sourceId'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_SCHEDULED_REPORTS, ['reportId'], Table::FORMIE_REPORTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_REPORT_EXPORTS, ['reportId'], Table::FORMIE_REPORTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_RELATIONS, ['sourceSiteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::FORMIE_RELATIONS, ['targetId'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_SENT_NOTIFICATIONS, ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_SENT_NOTIFICATIONS, ['formId'], Table::FORMIE_FORMS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_SENT_NOTIFICATIONS, ['submissionId'], Table::FORMIE_SUBMISSIONS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_SENT_NOTIFICATIONS, ['notificationId'], Table::FORMIE_NOTIFICATIONS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_STENCILS, ['templateId'], Table::FORMIE_FORM_TEMPLATES, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_STENCILS, ['defaultStatusId'], Table::FORMIE_SUBMISSION_STATUSES, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSIONS, ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSIONS, ['formId'], Table::FORMIE_FORMS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSIONS, ['statusId'], Table::FORMIE_SUBMISSION_STATUSES, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSIONS, ['userId'], '{{%users}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSIONS, ['updatedById'], '{{%users}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSION_QUIZ_RESULTS, ['submissionId'], Table::FORMIE_SUBMISSIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSION_OPERATIONS, ['formId'], Table::FORMIE_FORMS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSION_OPERATIONS, ['submissionId'], Table::FORMIE_SUBMISSIONS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSION_PROGRESS, ['submissionId'], Table::FORMIE_SUBMISSIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSION_PROGRESS, ['formId'], Table::FORMIE_FORMS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSION_PROGRESS, ['siteId'], '{{%sites}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSION_GRANTS, ['parentId'], Table::FORMIE_SUBMISSION_GRANTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSION_GRANTS, ['progressId'], Table::FORMIE_SUBMISSION_PROGRESS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSION_GRANTS, ['submissionId'], Table::FORMIE_SUBMISSIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSION_GRANTS, ['formId'], Table::FORMIE_FORMS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSION_GRANTS, ['siteId'], '{{%sites}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSION_WORKFLOW, ['submissionId'], Table::FORMIE_SUBMISSIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_PENDING_UPLOADS, ['assetId'], '{{%assets}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_PENDING_UPLOADS, ['submissionId'], Table::FORMIE_SUBMISSIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_delivery_attempts}}', ['submissionId'], Table::FORMIE_SUBMISSIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_delivery_diagnostics}}', ['attemptId'], '{{%formie_delivery_attempts}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_instance_configs}}', ['formId'], Table::FORMIE_FORMS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_integration_run_contexts}}', ['submissionId'], Table::FORMIE_SUBMISSIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_submission_dispatches}}', ['submissionId'], Table::FORMIE_SUBMISSIONS, ['id'], 'CASCADE', null);
    }

    public function removeTables(): void
    {
        $tables = [
            'formie_webhookreceipts',
            'formie_paymentcapabilities',
            'formie_delivery_diagnostics',
            'formie_instance_configs',
            'formie_delivery_attempts',
            'formie_integration_run_contexts',
            'formie_submission_dispatches',
            'formie_emailtemplates',
            'formie_fieldlayout_pages',
            'formie_fieldlayout_rows',
            'formie_fieldlayouts',
            'formie_fields',
            'formie_form_fields',
            'formie_field_site_overrides',
            'formie_forms',
            'formie_form_site_overrides',
            'formie_formgroups',
            'formie_formtemplates',
            'formie_integrations',
            'formie_captcha_providers',
            'formie_spam_settings',
            'formie_notifications',
            'formie_payments',
            'formie_payments_plans',
            'formie_payments_subscriptions',
            'formie_pdftemplates',
            'formie_relations',
            'formie_reports',
            'formie_scheduled_reports',
            'formie_report_exports',
            'formie_sentnotifications',
            'formie_submission_statuses',
            'formie_form_statuses',
            'formie_stencils',
            'formie_submissions',
            'formie_submission_quiz_results',
            'formie_submission_operations',
            'formie_submission_progress',
            'formie_submission_grants',
            'formie_submission_workflow',
            'formie_pending_uploads',
            'formie_submission_drafts',
            'formie_submission_resume_tokens',
        ];

        foreach ($tables as $table) {
            $this->dropTableIfExists('{{%' . $table . '}}');
        }
    }

    public function removeContent(): void
    {
        // Delete Sent Notification Elements
        $this->delete(Table::ELEMENTS, ['type' => SentNotification::class]);

        // Delete Form Submission Elements
        $this->delete(Table::ELEMENTS, ['type' => Submission::class]);

        // Delete Form Elements
        $this->delete(Table::ELEMENTS, ['type' => Form::class]);
    }

    public function dropProjectConfig(): void
    {
        Craft::$app->getProjectConfig()->remove('formie');
    }

    public function insertDefaultData(): void
    {
        Formie::$plugin->getCaptchaProviders()->seedRegistryFromLegacySettings([]);
        Formie::$plugin->getSpamProtection()->seedFromLegacySettings([]);

        $projectConfig = Craft::$app->getProjectConfig();

        // Don't make the same config changes twice
        $installed = ($projectConfig->get('plugins.formie', true) !== null);
        $configExists = ($projectConfig->get('formie', true) !== null);

        if (!$installed && !$configExists) {
            $this->_defaultSubmissionStatuses();
            $this->_defaultFormStatuses();
            $this->_defaultStencils();
        }

        // If the config data exists, but we're re-installing, apply it.
        // Sync project config into the database regardless of allowAdminChanges — that setting
        // blocks writes *to* project config YAML, not applying existing YAML to the DB.
        if (!$installed && $configExists) {
            $statuses = $projectConfig->get(SubmissionStatuses::CONFIG_SUBMISSION_STATUSES_KEY, true) ?? [];

            foreach ($statuses as $statusUid => $statusData) {
                $projectConfig->processConfigChanges(SubmissionStatuses::CONFIG_SUBMISSION_STATUSES_KEY . '.' . $statusUid, true);
            }

            $formStatuses = $projectConfig->get(FormStatuses::CONFIG_FORM_STATUSES_KEY, true) ?? [];

            foreach ($formStatuses as $statusUid => $statusData) {
                $projectConfig->processConfigChanges(FormStatuses::CONFIG_FORM_STATUSES_KEY . '.' . $statusUid, true);
            }

            $stencils = $projectConfig->get(Stencils::CONFIG_STENCILS_KEY, true) ?? [];

            foreach ($stencils as $stencilUid => $stencilData) {
                $projectConfig->processConfigChanges(Stencils::CONFIG_STENCILS_KEY . '.' . $stencilUid, true);
            }

            $formGroups = $projectConfig->get(FormGroups::CONFIG_GROUPS_KEY, true) ?? [];

            foreach ($formGroups as $formGroupUid => $formGroupData) {
                $projectConfig->processConfigChanges(FormGroups::CONFIG_GROUPS_KEY . '.' . $formGroupUid, true);
            }

            $reports = $projectConfig->get(Reports::CONFIG_REPORTS_KEY, true) ?? [];

            foreach ($reports as $reportUid => $reportData) {
                $projectConfig->processConfigChanges(Reports::CONFIG_REPORTS_KEY . '.' . $reportUid, true);
            }

            $scheduledReports = $projectConfig->get(ScheduledReports::CONFIG_SCHEDULED_REPORTS_KEY, true) ?? [];

            foreach ($scheduledReports as $scheduledReportUid => $scheduledReportData) {
                $projectConfig->processConfigChanges(ScheduledReports::CONFIG_SCHEDULED_REPORTS_KEY . '.' . $scheduledReportUid, true);
            }

            $captchaProviders = $projectConfig->get(CaptchaProviders::CONFIG_CAPTCHA_PROVIDERS_KEY, true) ?? [];

            foreach (array_keys($captchaProviders) as $handle) {
                $projectConfig->processConfigChanges(CaptchaProviders::CONFIG_CAPTCHA_PROVIDERS_KEY . '.' . $handle, true);
            }

            if ($projectConfig->get(SpamProtection::CONFIG_SPAM_SETTINGS_KEY, true) !== null) {
                $projectConfig->processConfigChanges(SpamProtection::CONFIG_SPAM_SETTINGS_KEY, true);
            }
        }
    }


    // Protected Methods
    // =========================================================================

    protected function dropForeignKeys(): void
    {
        $tables = [
            'formie_delivery_diagnostics',
            'formie_instance_configs',
            'formie_delivery_attempts',
            'formie_integration_run_contexts',
            'formie_submission_dispatches',
            'formie_emailtemplates',
            'formie_fieldlayout_pages',
            'formie_fieldlayout_rows',
            'formie_fieldlayouts',
            'formie_fields',
            'formie_form_fields',
            'formie_field_site_overrides',
            'formie_forms',
            'formie_form_site_overrides',
            'formie_formgroups',
            'formie_formtemplates',
            'formie_integrations',
            'formie_captcha_providers',
            'formie_spam_settings',
            'formie_notifications',
            'formie_payments',
            'formie_payments_plans',
            'formie_payments_subscriptions',
            'formie_pdftemplates',
            'formie_relations',
            'formie_reports',
            'formie_scheduled_reports',
            'formie_report_exports',
            'formie_sentnotifications',
            'formie_submission_statuses',
            'formie_form_statuses',
            'formie_stencils',
            'formie_submissions',
            'formie_submission_quiz_results',
            'formie_submission_operations',
            'formie_submission_progress',
            'formie_submission_grants',
            'formie_submission_workflow',
            'formie_pending_uploads',
            'formie_submission_drafts',
            'formie_submission_resume_tokens',
        ];

        foreach ($tables as $table) {
            if ($this->db->tableExists('{{%' . $table . '}}')) {
                MigrationHelper::dropAllForeignKeysOnTable('{{%' . $table . '}}', $this);
                MigrationHelper::dropAllForeignKeysToTable('{{%' . $table . '}}', $this);
            }
        }
    }


    // Private Methods
    // =========================================================================

    private function _defaultSubmissionStatuses(): void
    {
        $statuses = [
            [
                'name' => 'New',
                'handle' => 'new',
                'color' => 'turquoise',
                'sortOrder' => 1,
                'isDefault' => 1,
            ],
        ];

        foreach ($statuses as $status) {
            $submissionStatus = new SubmissionStatus($status);
            Formie::$plugin->getSubmissionStatuses()->saveStatus($submissionStatus);
        }
    }

    private function _defaultFormStatuses(): void
    {
        $statuses = [
            [
                'name' => 'Active',
                'handle' => 'active',
                'color' => 'turquoise',
                'sortOrder' => 1,
                'isDefault' => 1,
            ],
            [
                'name' => 'Draft',
                'handle' => 'draft',
                'color' => 'orange',
                'sortOrder' => 2,
                'isDefault' => 0,
            ],
            [
                'name' => 'Archived',
                'handle' => 'archived',
                'color' => 'grey',
                'sortOrder' => 3,
                'isDefault' => 0,
            ],
        ];

        foreach ($statuses as $status) {
            $formStatus = new FormStatus($status);
            Formie::$plugin->getFormStatuses()->saveStatus($formStatus);
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
            $data = Json::decode(file_get_contents($stencilInfo['file']));

            $stencil = new Stencil();
            $stencil->name = $stencilInfo['name'];
            $stencil->handle = $stencilInfo['handle'];

            Formie::$plugin->getStencils()->saveStencil($stencil);

            // Update the data after the fact and directly so we can use it before Formie is installed
            // Otherwise, the field layouts will try and validate the fields
            $this->update(Table::FORMIE_STENCILS, ['data' => Json::encode($data)], ['handle' => $stencilInfo['handle']]);
        }
    }
}
