<?php
namespace verbb\formie\migrations;

use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\models\FormStatus;
use verbb\formie\services\FormStatuses;

use Craft;
use craft\db\Migration;
use craft\services\ElementSources;
use craft\services\ProjectConfig;

class m260636_000000_form_statuses extends Migration
{
    // Constants
    // =========================================================================

    private const LEGACY_SUBMISSION_STATUSES_TABLE = '{{%formie_statuses}}';


    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->_renameSubmissionStatusesTable();
        $this->_createFormStatusesTable();
        $this->_seedDefaultFormStatuses();
        $this->_backfillFormStatusIds();
        $this->_removeFormStatusSidebarSources();
        $this->_alignDefaultFormStatusColors();

        return true;
    }

    public function safeDown(): bool
    {
        if ($this->db->columnExists(Table::FORMIE_FORMS, 'formStatusId')) {
            $this->dropForeignKeyIfExists(Table::FORMIE_FORMS, ['formStatusId']);
            $this->dropIndexIfExists(Table::FORMIE_FORMS, 'formStatusId');
            $this->dropColumn(Table::FORMIE_FORMS, 'formStatusId');
        }

        $this->dropTableIfExists(Table::FORMIE_FORM_STATUSES);

        if ($this->db->tableExists(Table::FORMIE_SUBMISSION_STATUSES)
            && !$this->db->tableExists(self::LEGACY_SUBMISSION_STATUSES_TABLE)) {
            $this->renameTable(Table::FORMIE_SUBMISSION_STATUSES, self::LEGACY_SUBMISSION_STATUSES_TABLE);
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _renameSubmissionStatusesTable(): void
    {
        if (!$this->db->tableExists(self::LEGACY_SUBMISSION_STATUSES_TABLE)) {
            return;
        }

        if ($this->db->tableExists(Table::FORMIE_SUBMISSION_STATUSES)) {
            return;
        }

        $this->renameTable(self::LEGACY_SUBMISSION_STATUSES_TABLE, Table::FORMIE_SUBMISSION_STATUSES);
    }

    private function _createFormStatusesTable(): void
    {
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

        if (!$this->db->columnExists(Table::FORMIE_FORMS, 'formStatusId')) {
            $this->addColumn(Table::FORMIE_FORMS, 'formStatusId', $this->integer()->after('groupId'));
            $this->createIndex(null, Table::FORMIE_FORMS, 'formStatusId', false);
            $this->addForeignKey(null, Table::FORMIE_FORMS, ['formStatusId'], Table::FORMIE_FORM_STATUSES, ['id'], 'SET NULL', null);
        }
    }

    private function _seedDefaultFormStatuses(): void
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $existing = $projectConfig->get(FormStatuses::CONFIG_FORM_STATUSES_KEY, true) ?? [];

        if ($existing) {
            foreach ($existing as $statusUid => $statusData) {
                $projectConfig->processConfigChanges(FormStatuses::CONFIG_FORM_STATUSES_KEY . '.' . $statusUid, true);
            }

            return;
        }

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

    private function _backfillFormStatusIds(): void
    {
        if (!$this->db->columnExists(Table::FORMIE_FORMS, 'formStatusId')) {
            return;
        }

        $defaultStatusId = Formie::$plugin->getFormStatuses()->getDefaultStatus()?->id;

        if (!$defaultStatusId) {
            return;
        }

        $this->update(Table::FORMIE_FORMS, ['formStatusId' => $defaultStatusId], ['formStatusId' => null]);
    }

    private function _removeFormStatusSidebarSources(): void
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $path = ProjectConfig::PATH_ELEMENT_SOURCES . '.' . Form::class;
        $sources = $projectConfig->get($path) ?? [];

        if (!$sources) {
            return;
        }

        $filtered = array_values(array_filter(
            $sources,
            fn(array $source) => !$this->_isFormStatusSidebarSource($source),
        ));

        if ($filtered === $sources) {
            return;
        }

        $projectConfig->set($path, $filtered ?: null);
    }

    private function _alignDefaultFormStatusColors(): void
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $path = FormStatuses::CONFIG_FORM_STATUSES_KEY;
        $statuses = $projectConfig->get($path, true) ?? [];

        foreach ($statuses as $uid => $status) {
            if (($status['handle'] ?? null) !== 'active' || ($status['color'] ?? null) !== 'green') {
                continue;
            }

            $projectConfig->set("$path.$uid.color", 'turquoise', 'Align default active form status color with Craft');
        }
    }

    private function _isFormStatusSidebarSource(array $source): bool
    {
        if (str_starts_with($source['key'] ?? '', 'formStatus:')) {
            return true;
        }

        if (($source['type'] ?? null) === ElementSources::TYPE_HEADING
            && ($source['heading'] ?? '') === Craft::t('formie', 'Form Statuses')) {
            return true;
        }

        return isset($source['criteria']['formStatusId']);
    }
}
