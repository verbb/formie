<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\elements\SentNotification;
use verbb\formie\helpers\Table;
use verbb\formie\models\Status;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use verbb\formie\services\CaptchaProviders;
use verbb\formie\services\FormGroups;
use verbb\formie\services\Reports;
use verbb\formie\services\ScheduledReports;
use verbb\formie\services\SpamProtection;
use verbb\formie\services\Statuses;
use verbb\formie\services\Stencils;

use Craft;
use craft\db\Migration;
use craft\helpers\Json;
use craft\helpers\MigrationHelper;

use verbb\auth\Auth;

class Install extends Migration
{
    // Public Methods
    // =========================================================================

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
            'enableFormSubmitExpiration' => $this->boolean()->notNull()->defaultValue(false),
            'formSubmitExpiration' => $this->integer()->notNull()->defaultValue(86400),
            'enableSuspiciousTextDetection' => $this->boolean()->notNull()->defaultValue(false),
            'suspiciousTextAllowedTerms' => $this->mediumText()->after('enableSuspiciousTextDetection'),
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
            'customSettings' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_PAYMENTS);
        $this->createTable(Table::FORMIE_PAYMENTS, [
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
            'redirectUrl' => $this->text(),
            'note' => $this->mediumText(),
            'response' => $this->text(),
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

        $this->archiveTableIfExists(Table::FORMIE_STATUSES);
        $this->createTable(Table::FORMIE_STATUSES, [
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
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_SUBMISSION_DRAFTS);
        $this->createTable(Table::FORMIE_SUBMISSION_DRAFTS, [
            'id' => $this->primaryKey(),
            'storageKey' => $this->string(255)->notNull(),
            'value' => $this->mediumText(),
            'dateExpires' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::FORMIE_SUBMISSION_RESUME_TOKENS);
        $this->createTable(Table::FORMIE_SUBMISSION_RESUME_TOKENS, [
            'id' => $this->primaryKey(),
            'token' => $this->string(128)->notNull(),
            'storageKey' => $this->string(255)->notNull(),
            'formId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'submissionId' => $this->integer(),
            'capabilities' => $this->text(),
            'issuedAt' => $this->integer(),
            'expiresAt' => $this->integer(),
            'revokedAt' => $this->integer(),
            'dateExpires' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    public function createIndexes(): void
    {
        $this->createIndex(null, Table::FORMIE_FIELD_LAYOUT_PAGES, 'layoutId', false);
        $this->createIndex(null, Table::FORMIE_FIELD_LAYOUT_ROWS, 'layoutId', false);
        $this->createIndex(null, Table::FORMIE_FIELD_LAYOUT_ROWS, 'pageId', false);
        $this->createIndex(null, Table::FORMIE_FIELDS, 'handle', false);
        $this->createIndex(null, Table::FORMIE_FORM_FIELDS, 'fieldId', false);
        $this->createIndex(null, Table::FORMIE_FORM_FIELDS, 'layoutId', false);
        $this->createIndex(null, Table::FORMIE_FORM_FIELDS, 'pageId', false);
        $this->createIndex(null, Table::FORMIE_FORM_FIELDS, 'rowId', false);
        $this->createIndex(null, Table::FORMIE_FORM_FIELDS, 'reference', true);
        $this->createIndex(null, Table::FORMIE_FORMS, 'layoutId', false);
        $this->createIndex(null, Table::FORMIE_FORMS, 'templateId', false);
        $this->createIndex(null, Table::FORMIE_FORMS, 'groupId', false);
        $this->createIndex(null, Table::FORMIE_FORMS, 'defaultStatusId', false);
        $this->createIndex(null, Table::FORMIE_FORMS, 'submitActionEntryId', false);
        $this->createIndex(null, Table::FORMIE_FORMS, 'submitActionEntrySiteId', false);
        $this->createIndex(null, Table::FORMIE_FORMS, 'createdById', false);
        $this->createIndex(null, Table::FORMIE_FORMS, 'updatedById', false);
        $this->createIndex(null, Table::FORMIE_FORM_TEMPLATES, 'fieldLayoutId', false);
        $this->createIndex(null, Table::FORMIE_NOTIFICATIONS, 'formId', false);
        $this->createIndex(null, Table::FORMIE_NOTIFICATIONS, 'templateId', false);
        $this->createIndex(null, Table::FORMIE_PAYMENTS, 'integrationId', false);
        $this->createIndex(null, Table::FORMIE_PAYMENTS, 'fieldId', false);
        $this->createIndex(null, Table::FORMIE_PAYMENTS, 'reference', false);
        $this->createIndex(null, Table::FORMIE_PAYMENT_PLANS, 'integrationId', false);
        $this->createIndex(null, Table::FORMIE_PAYMENT_PLANS, 'handle', true);
        $this->createIndex(null, Table::FORMIE_PAYMENT_PLANS, 'reference', false);
        $this->createIndex(null, Table::FORMIE_SUBSCRIPTIONS, 'integrationId', false);
        $this->createIndex(null, Table::FORMIE_SUBSCRIPTIONS, 'submissionId', false);
        $this->createIndex(null, Table::FORMIE_SUBSCRIPTIONS, 'fieldId', false);
        $this->createIndex(null, Table::FORMIE_SUBSCRIPTIONS, 'planId', false);
        $this->createIndex(null, Table::FORMIE_SUBSCRIPTIONS, 'reference', false);
        $this->createIndex(null, Table::FORMIE_SUBSCRIPTIONS, 'nextPaymentDate', false);
        $this->createIndex(null, Table::FORMIE_SUBSCRIPTIONS, 'dateExpired', false);
        $this->createIndex(null, Table::FORMIE_SUBSCRIPTIONS, 'dateExpired', false);
        $this->createIndex(null, Table::FORMIE_RELATIONS, ['sourceId', 'sourceSiteId', 'targetId'], true);
        $this->createIndex(null, Table::FORMIE_RELATIONS, ['sourceId'], false);
        $this->createIndex(null, Table::FORMIE_RELATIONS, ['targetId'], false);
        $this->createIndex(null, Table::FORMIE_RELATIONS, ['sourceSiteId'], false);
        $this->createIndex(null, Table::FORMIE_STENCILS, 'templateId', false);
        $this->createIndex(null, Table::FORMIE_STENCILS, 'defaultStatusId', false);
        $this->createIndex(null, Table::FORMIE_SUBMISSIONS, 'formId', false);
        $this->createIndex(null, Table::FORMIE_SUBMISSIONS, 'statusId', false);
        $this->createIndex(null, Table::FORMIE_SUBMISSIONS, 'userId', false);
        $this->createIndex(null, Table::FORMIE_SUBMISSION_QUIZ_RESULTS, 'submissionId', true);
        $this->createIndex(null, Table::FORMIE_SUBMISSION_WORKFLOW, 'submissionId', false);
        $this->createIndex(null, Table::FORMIE_SUBMISSION_WORKFLOW, ['submissionId', 'stage', 'idempotencyKey'], true);
        $this->createIndex(null, Table::FORMIE_PENDING_UPLOADS, 'assetId', true);
        $this->createIndex(null, Table::FORMIE_PENDING_UPLOADS, 'submissionId', false);
        $this->createIndex(null, Table::FORMIE_PENDING_UPLOADS, ['isFinalized', 'dateUpdated'], false);
        $this->createIndex(null, Table::FORMIE_SUBMISSION_DRAFTS, 'storageKey', true);
        $this->createIndex(null, Table::FORMIE_SUBMISSION_DRAFTS, 'dateExpires', false);
        $this->createIndex(null, Table::FORMIE_SUBMISSION_RESUME_TOKENS, 'token', true);
        $this->createIndex(null, Table::FORMIE_SUBMISSION_RESUME_TOKENS, 'storageKey', false);
        $this->createIndex(null, Table::FORMIE_SUBMISSION_RESUME_TOKENS, 'dateExpires', false);
    }

    public function addForeignKeys(): void
    {
        $this->addForeignKey(null, Table::FORMIE_FIELD_LAYOUT_PAGES, ['layoutId'], Table::FORMIE_FIELD_LAYOUTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FIELD_LAYOUT_ROWS, ['layoutId'], Table::FORMIE_FIELD_LAYOUTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FIELD_LAYOUT_ROWS, ['pageId'], Table::FORMIE_FIELD_LAYOUT_PAGES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FORM_FIELDS, ['fieldId'], Table::FORMIE_FIELDS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FORM_FIELDS, ['layoutId'], Table::FORMIE_FIELD_LAYOUTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FORM_FIELDS, ['pageId'], Table::FORMIE_FIELD_LAYOUT_PAGES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FORM_FIELDS, ['rowId'], Table::FORMIE_FIELD_LAYOUT_ROWS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FORMS, ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_FORMS, ['layoutId'], Table::FORMIE_FIELD_LAYOUTS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_FORMS, ['templateId'], Table::FORMIE_FORM_TEMPLATES, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_FORMS, ['groupId'], Table::FORMIE_FORM_GROUPS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_FORMS, ['defaultStatusId'], Table::FORMIE_STATUSES, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_FORMS, ['submitActionEntryId'], '{{%entries}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_FORMS, ['createdById'], '{{%users}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_FORMS, ['updatedById'], '{{%users}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_FORM_TEMPLATES, ['fieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_NOTIFICATIONS, ['formId'], Table::FORMIE_FORMS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_NOTIFICATIONS, ['templateId'], Table::FORMIE_EMAIL_TEMPLATES, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_NOTIFICATIONS, ['pdfTemplateId'], Table::FORMIE_PDF_TEMPLATES, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_PAYMENTS, ['submissionId'], Table::FORMIE_SUBMISSIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_PAYMENTS, ['subscriptionId'], Table::FORMIE_SUBSCRIPTIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_PAYMENTS, ['fieldId'], Table::FORMIE_FORM_FIELDS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_PAYMENTS, ['integrationId'], Table::FORMIE_INTEGRATIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_PAYMENT_PLANS, ['integrationId'], Table::FORMIE_INTEGRATIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_SUBSCRIPTIONS, ['integrationId'], Table::FORMIE_INTEGRATIONS, ['id'], 'RESTRICT', null);
        $this->addForeignKey(null, Table::FORMIE_SUBSCRIPTIONS, ['submissionId'], Table::FORMIE_SUBMISSIONS, ['id'], 'RESTRICT', null);
        $this->addForeignKey(null, Table::FORMIE_SUBSCRIPTIONS, ['fieldId'], Table::FORMIE_FORM_FIELDS, ['id'], 'RESTRICT', null);
        $this->addForeignKey(null, Table::FORMIE_SUBSCRIPTIONS, ['planId'], Table::FORMIE_PAYMENT_PLANS, ['id'], 'RESTRICT', null);
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
        $this->addForeignKey(null, Table::FORMIE_STENCILS, ['defaultStatusId'], Table::FORMIE_STATUSES, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSIONS, ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSIONS, ['formId'], Table::FORMIE_FORMS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSIONS, ['statusId'], Table::FORMIE_STATUSES, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSIONS, ['userId'], '{{%users}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSION_QUIZ_RESULTS, ['submissionId'], Table::FORMIE_SUBMISSIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSION_WORKFLOW, ['submissionId'], Table::FORMIE_SUBMISSIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_PENDING_UPLOADS, ['assetId'], '{{%assets}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_PENDING_UPLOADS, ['submissionId'], Table::FORMIE_SUBMISSIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSION_RESUME_TOKENS, ['formId'], Table::FORMIE_FORMS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSION_RESUME_TOKENS, ['siteId'], '{{%sites}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FORMIE_SUBMISSION_RESUME_TOKENS, ['submissionId'], Table::FORMIE_SUBMISSIONS, ['id'], 'SET NULL', null);
    }

    public function removeTables(): void
    {
        $tables = [
            'formie_emailtemplates',
            'formie_fieldlayout_pages',
            'formie_fieldlayout_rows',
            'formie_fieldlayouts',
            'formie_fields',
            'formie_forms',
            'formie_formgroups',
            'formie_formtemplates',
            'formie_integrations',
            'formie_notifications',
            'formie_payments',
            'formie_payments_plans',
            'formie_payments_subscriptions',
            'formie_pdftemplates',
            'formie_relations',
            'formie_sentnotifications',
            'formie_statuses',
            'formie_stencils',
            'formie_submissions',
            'formie_submission_quiz_results',
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
            $this->_defaultStatuses();
            $this->_defaultStencils();
        }

        // If the config data exists, but we're re-installing, apply it.
        // Sync project config into the database regardless of allowAdminChanges — that setting
        // blocks writes *to* project config YAML, not applying existing YAML to the DB.
        if (!$installed && $configExists) {
            $statuses = $projectConfig->get(Statuses::CONFIG_STATUSES_KEY, true) ?? [];

            foreach ($statuses as $statusUid => $statusData) {
                $projectConfig->processConfigChanges(Statuses::CONFIG_STATUSES_KEY . '.' . $statusUid, true);
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

    protected function afterUp(): void
    {
        $this->insertDefaultData();
        
        parent::afterUp();
    }

    protected function dropForeignKeys(): void
    {
        $tables = [
            'formie_emailtemplates',
            'formie_fieldlayout_pages',
            'formie_fieldlayout_rows',
            'formie_fieldlayouts',
            'formie_fields',
            'formie_forms',
            'formie_formgroups',
            'formie_formtemplates',
            'formie_integrations',
            'formie_notifications',
            'formie_payments',
            'formie_payments_plans',
            'formie_payments_subscriptions',
            'formie_pdftemplates',
            'formie_relations',
            'formie_sentnotifications',
            'formie_statuses',
            'formie_stencils',
            'formie_submissions',
            'formie_submission_quiz_results',
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
