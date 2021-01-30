<?php
namespace verbb\formie\migrations;

use Craft;
use craft\db\Migration;
use craft\elements\SentNotification;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\MigrationHelper;

use verbb\formie\Formie;
use verbb\formie\models\Status;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use verbb\formie\elements\Form;
use verbb\formie\services\Forms;
use verbb\formie\services\Statuses;
use verbb\formie\services\Stencils;

/**
 * Install migration.
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
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
    public function safeDown()
    {
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

    public function createTables()
    {
        $this->createTable('{{%formie_forms}}', [
            'id' => $this->primaryKey(),
            'handle' => $this->string(64)->notNull(),
            'fieldContentTable' => $this->string(64)->notNull(),
            'settings' => $this->mediumText(),
            'templateId' => $this->integer(),
            'submitActionEntryId' => $this->integer(),
            'requireUser' => $this->boolean(),
            'availability' => $this->enum('availability', ['always', 'date', 'submissions'])
                ->defaultValue('always')
                ->notNull(),
            'availabilityFrom' => $this->dateTime(),
            'availabilityTo' => $this->dateTime(),
            'availabilitySubmissions' => $this->integer(),
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

        $this->createTable('{{%formie_rows}}', [
            'id' => $this->primaryKey(),
            'fieldLayoutId' => $this->integer()->notNull(),
            'fieldLayoutFieldId' => $this->integer()->notNull(),
            'row' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%formie_pagesettings}}', [
            'id' => $this->primaryKey(),
            'fieldLayoutId' => $this->integer()->notNull(),
            'fieldLayoutTabId' => $this->integer()->notNull(),
            'settings' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%formie_nested}}', [
            'id' => $this->primaryKey(),
            'fieldId' => $this->integer()->notNull(),
            'fieldLayoutId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

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

        $this->createTable('{{%formie_syncs}}', [
            'id' => $this->primaryKey(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%formie_syncfields}}', [
            'id' => $this->primaryKey(),
            'syncId' => $this->integer()->notNull(),
            'fieldId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%formie_submissions}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'formId' => $this->integer()->notNull(),
            'statusId' => $this->integer(),
            'userId' => $this->integer(),
            'isIncomplete' => $this->boolean()->defaultValue(false),
            'isSpam' => $this->boolean()->defaultValue(false),
            'spamReason' => $this->text(),
            'ipAddress' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%formie_notifications}}', [
            'id' => $this->primaryKey(),
            'formId' => $this->integer()->notNull(),
            'templateId' => $this->integer(),
            'name' => $this->text()->notNull(),
            'enabled' => $this->boolean()->defaultValue(true),
            'subject' => $this->text(),
            'to' => $this->text(),
            'cc' => $this->text(),
            'bcc' => $this->text(),
            'replyTo' => $this->text(),
            'replyToName' => $this->text(),
            'from' => $this->text(),
            'fromName' => $this->text(),
            'content' => $this->text(),
            'attachFiles' => $this->boolean()->defaultValue(true),
            'enableConditions' => $this->boolean()->defaultValue(false),
            'conditions' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

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
            'uid' => $this->uid(),
        ]);

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

        $this->createTable('{{%formie_stencils}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string(64)->notNull(),
            'data' => $this->mediumText(),
            'templateId' => $this->integer(),
            'defaultStatusId' => $this->integer(),
            'dateDeleted' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

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

    public function createIndexes()
    {
        $this->createIndex(null, '{{%formie_forms}}', 'templateId', false);
        $this->createIndex(null, '{{%formie_forms}}', 'defaultStatusId', false);
        $this->createIndex(null, '{{%formie_forms}}', 'submitActionEntryId', false);
        $this->createIndex(null, '{{%formie_forms}}', 'fieldLayoutId', false);
        $this->createIndex(null, '{{%formie_rows}}', 'fieldLayoutId', false);
        $this->createIndex(null, '{{%formie_rows}}', 'fieldLayoutFieldId', true);
        $this->createIndex(null, '{{%formie_pagesettings}}', 'fieldLayoutId', false);
        $this->createIndex(null, '{{%formie_pagesettings}}', 'fieldLayoutTabId', true);
        $this->createIndex(null, '{{%formie_nested}}', 'fieldId', true);
        $this->createIndex(null, '{{%formie_nested}}', 'fieldLayoutId', false);
        $this->createIndex(null, '{{%formie_nestedfieldrows}}', 'ownerId', false);
        $this->createIndex(null, '{{%formie_nestedfieldrows}}', 'fieldId', false);
        $this->createIndex(null, '{{%formie_nestedfieldrows}}', 'sortOrder', false);
        $this->createIndex(null, '{{%formie_syncfields}}', ['syncId', 'fieldId'], true);
        $this->createIndex(null, '{{%formie_submissions}}', 'formId', false);
        $this->createIndex(null, '{{%formie_submissions}}', 'statusId', false);
        $this->createIndex(null, '{{%formie_submissions}}', 'userId', false);
        $this->createIndex(null, '{{%formie_stencils}}', 'templateId', false);
        $this->createIndex(null, '{{%formie_stencils}}', 'defaultStatusId', false);
        $this->createIndex(null, '{{%formie_formtemplates}}', 'fieldLayoutId', false);
        $this->createIndex(null, '{{%formie_notifications}}', 'formId', false);
        $this->createIndex(null, '{{%formie_notifications}}', 'templateId', false);
    }

    public function addForeignKeys()
    {
        $this->addForeignKey(null, '{{%formie_forms}}', ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_forms}}', ['templateId'], '{{%formie_formtemplates}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%formie_forms}}', ['defaultStatusId'], '{{%formie_statuses}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%formie_forms}}', ['submitActionEntryId'], '{{%entries}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%formie_forms}}', ['fieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_rows}}', ['fieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_rows}}', ['fieldLayoutFieldId'], '{{%fieldlayoutfields}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_pagesettings}}', ['fieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_pagesettings}}', ['fieldLayoutTabId'], '{{%fieldlayouttabs}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_nested}}', ['fieldId'], '{{%fields}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_nested}}', ['fieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_nestedfieldrows}}', ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_nestedfieldrows}}', ['ownerId'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_nestedfieldrows}}', ['fieldId'], '{{%fields}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_syncfields}}', ['syncId'], '{{%formie_syncs}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_syncfields}}', ['fieldId'], '{{%fields}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_submissions}}', ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_submissions}}', ['formId'], '{{%formie_forms}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_submissions}}', ['statusId'], '{{%formie_statuses}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%formie_submissions}}', ['userId'], '{{%users}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%formie_stencils}}', ['templateId'], '{{%formie_formtemplates}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%formie_stencils}}', ['defaultStatusId'], '{{%formie_statuses}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%formie_formtemplates}}', ['fieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_notifications}}', ['formId'], '{{%formie_forms}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_notifications}}', ['templateId'], '{{%formie_emailtemplates}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%formie_sentnotifications}}', ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_sentnotifications}}', ['formId'], '{{%formie_forms}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%formie_sentnotifications}}', ['submissionId'], '{{%formie_submissions}}', ['id'], 'CASCADE', null);
    }

    protected function dropForeignKeys()
    {
        if ($this->db->tableExists('{{%formie_rows}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%formie_rows}}', $this);
        }

        if ($this->db->tableExists('{{%formie_pagesettings}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%formie_pagesettings}}', $this);
        }

        if ($this->db->tableExists('{{%formie_nested}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%formie_nested}}', $this);
        }

        if ($this->db->tableExists('{{%formie_nestedfieldrows}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%formie_nestedfieldrows}}', $this);
        }

        if ($this->db->tableExists('{{%formie_syncfields}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%formie_syncfields}}', $this);
        }

        if ($this->db->tableExists('{{%formie_syncs}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%formie_syncs}}', $this);
        }

        if ($this->db->tableExists('{{%formie_forms}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%formie_forms}}', $this);
        }

        if ($this->db->tableExists('{{%formie_stencils}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%formie_stencils}}', $this);
        }

        if ($this->db->tableExists('{{%formie_submissions}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%formie_submissions}}', $this);
        }

        if ($this->db->tableExists('{{%formie_notifications}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%formie_notifications}}', $this);
        }

        if ($this->db->tableExists('{{%formie_statuses}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%formie_statuses}}', $this);
        }

        if ($this->db->tableExists('{{%formie_formtemplates}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%formie_formtemplates}}', $this);
        }

        if ($this->db->tableExists('{{%formie_emailtemplates}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%formie_emailtemplates}}', $this);
        }

        if ($this->db->tableExists('{{%formie_sentnotifications}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%formie_sentnotifications}}', $this);
        }
    }

    public function removeTables()
    {
        $this->dropTableIfExists('{{%formie_rows}}');
        $this->dropTableIfExists('{{%formie_pagesettings}}');
        $this->dropTableIfExists('{{%formie_nested}}');
        $this->dropTableIfExists('{{%formie_nestedfieldrows}}');
        $this->dropTableIfExists('{{%formie_syncfields}}');
        $this->dropTableIfExists('{{%formie_syncs}}');
        $this->dropTableIfExists('{{%formie_forms}}');
        $this->dropTableIfExists('{{%formie_stencils}}');
        $this->dropTableIfExists('{{%formie_submissions}}');
        $this->dropTableIfExists('{{%formie_notifications}}');
        $this->dropTableIfExists('{{%formie_statuses}}');
        $this->dropTableIfExists('{{%formie_formtemplates}}');
        $this->dropTableIfExists('{{%formie_emailtemplates}}');
        $this->dropTableIfExists('{{%formie_tokens}}');
        $this->dropTableIfExists('{{%formie_integrations}}');
        $this->dropTableIfExists('{{%formie_sentnotifications}}');
    }

    public function removeContent()
    {
        // Delete Sent Notification Elements
        $this->delete('{{%elements}}', ['type' => SentNotification::class]);
    }

    public function dropProjectConfig()
    {
        Craft::$app->projectConfig->remove('formie');
    }

    public function insertDefaultData()
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

    private function _defaultStatuses()
    {
        $statuses = [
            [
                'name' => 'New',
                'handle' => 'new',
                'color' => 'green',
                'sortOrder' => 1,
                'isDefault' => 1
            ],
        ];

        foreach ($statuses as $status) {
            $orderStatus = new Status($status);
            Formie::getInstance()->getStatuses()->saveStatus($orderStatus);
        }
    }

    private function _defaultStencils()
    {
        $stencils = [
            [
                'name' => Craft::t('formie', 'Contact Form'),
                'handle' => 'contactForm',
                'file' => Craft::getAlias('@verbb/formie/migrations/stencils/contact-form.json'),
            ]
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
