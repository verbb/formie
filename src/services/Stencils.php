<?php
namespace verbb\formie\services;

use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\events\StencilEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\DbSchema;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\Stencil;
use verbb\formie\records\Stencil as StencilRecord;

use Craft;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\Json;

use yii\base\Component;
use Throwable;

class Stencils extends Component
{
    // Constants
    // =========================================================================

    public const SCOPE_PROJECT = 'project';
    public const SCOPE_SITE = 'site';

    public const EVENT_BEFORE_SAVE_STENCIL = 'beforeSaveStencil';
    public const EVENT_AFTER_SAVE_STENCIL = 'afterSaveStencil';
    public const EVENT_BEFORE_DELETE_STENCIL = 'beforeDeleteStencil';
    public const EVENT_BEFORE_APPLY_STENCIL_DELETE = 'beforeApplyStencilDelete';
    public const EVENT_AFTER_DELETE_STENCIL = 'afterDeleteStencil';
    public const CONFIG_STENCILS_KEY = 'formie.stencils';


    // Properties
    // =========================================================================

    private ?array $_stencils = null;


    // Public Methods
    // =========================================================================

    public static function resolveScopeForNew(?string $requestedScope = null): string
    {
        if ($requestedScope === self::SCOPE_PROJECT && Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            return self::SCOPE_PROJECT;
        }

        return self::SCOPE_SITE;
    }

    public function getAllStencils(bool $withTrashed = false): array
    {
        if (!DbSchema::tableExists(Table::FORMIE_STENCILS)) {
            return [];
        }

        if ($this->_stencils !== null && !$withTrashed) {
            return $this->_stencils;
        }

        $results = $this->_createStencilsQuery($withTrashed)->all();
        $stencils = [];

        foreach ($results as $row) {
            $stencils[] = new Stencil($row);
        }

        if (!$withTrashed) {
            $this->_stencils = $stencils;
        }

        return $stencils;
    }

    public function getStencilArray(): array
    {
        $stencils = [];

        foreach ($this->getAllStencils() as $stencil) {
            $stencils[] = [
                'value' => $stencil->id,
                'label' => $stencil->name,
            ];
        }

        return $stencils;
    }

    public function getStencilById(int $id): ?Stencil
    {
        return ArrayHelper::firstWhere($this->getAllStencils(), 'id', $id);
    }

    public function getStencilByHandle(string $handle): ?Stencil
    {
        return ArrayHelper::firstWhere($this->getAllStencils(), 'handle', $handle, false);
    }

    public function getStencilByUid(string $uid): ?Stencil
    {
        return ArrayHelper::firstWhere($this->getAllStencils(), 'uid', $uid, false);
    }

    public function saveStencil(Stencil $stencil, bool $runValidation = true): bool
    {
        $isNewStencil = !$stencil->id;

        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_STENCIL)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_STENCIL, new StencilEvent([
                'stencil' => $stencil,
                'isNew' => $isNewStencil,
            ]));
        }

        if ($runValidation && !$stencil->validate()) {
            Formie::info('Stencil not saved due to validation error.');

            return false;
        }

        if (!$stencil->canEdit()) {
            $stencil->addError('name', Craft::t('formie', 'This stencil cannot be edited in the current environment.'));

            return false;
        }

        if ($isNewStencil) {
            Formie::$plugin->getFormDefaults()->applyCaptchaDefaultsToIntegrations($stencil->data->settings->integrations);
        }

        if ($existingStencil = $this->_findConflictingStencil($stencil)) {
            $stencil->addError('handle', Craft::t('formie', 'That handle is already in use'));

            return false;
        }

        if ($stencil->isSiteScope()) {
            return $this->_saveSiteStencil($stencil, $isNewStencil);
        }

        return $this->_saveProjectStencil($stencil, $isNewStencil);
    }

    public function handleChangedStencil(ConfigEvent $event): void
    {
        if (!DbSchema::tableExists(Table::FORMIE_STENCILS)) {
            return;
        }

        $stencilUid = $event->tokenMatches[0];
        $data = $event->newValue;

        $projectConfig = Craft::$app->getProjectConfig();
        $formTemplates = $projectConfig->get(FormTemplates::CONFIG_TEMPLATES_KEY, true) ?? [];
        $emailTemplates = $projectConfig->get(EmailTemplates::CONFIG_TEMPLATES_KEY, true) ?? [];
        $statuses = $projectConfig->get(Statuses::CONFIG_STATUSES_KEY, true) ?? [];

        foreach ($formTemplates as $formTemplateUid => $formTemplateData) {
            $projectConfig->processConfigChanges(FormTemplates::CONFIG_TEMPLATES_KEY . '.' . $formTemplateUid);
        }

        foreach ($emailTemplates as $emailTemplateUid => $emailTemplateData) {
            $projectConfig->processConfigChanges(EmailTemplates::CONFIG_TEMPLATES_KEY . '.' . $emailTemplateUid);
        }

        foreach ($statuses as $statusUid => $statusData) {
            $projectConfig->processConfigChanges(Statuses::CONFIG_STATUSES_KEY . '.' . $statusUid);
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $stencilRecord = $this->_getStencilsRecord($stencilUid);
            $isNewStencil = $stencilRecord->getIsNewRecord();

            $stencilRecord->name = $data['name'];
            $stencilRecord->handle = $data['handle'];
            $stencilRecord->data = $data['data'];
            $stencilRecord->uid = $stencilUid;
            $stencilRecord->scope = self::SCOPE_PROJECT;

            $submitActionEntryUid = $data['submitActionEntry'] ?? null;
            $defaultStatusUid = $data['defaultStatus'] ?? null;
            $templateUid = $data['template'] ?? null;

            if ($defaultStatusUid) {
                $defaultStatus = Formie::$plugin->getStatuses()->getStatusByUid($defaultStatusUid);

                if ($defaultStatus) {
                    $stencilRecord->defaultStatusId = $defaultStatus->id;
                }
            }

            if ($submitActionEntryUid) {
                $submitActionEntry = Craft::$app->getElements()->getElementByUid($submitActionEntryUid);

                if ($submitActionEntry) {
                    $stencilRecord->submitActionEntryId = $submitActionEntry->id;
                }
            }

            if ($templateUid) {
                $template = Formie::$plugin->getFormTemplates()->getTemplateByUid($templateUid);

                if ($template) {
                    $stencilRecord->templateId = $template->id;
                }
            }

            if ($wasTrashed = (bool)$stencilRecord->dateDeleted) {
                $stencilRecord->restore();
            } else {
                $stencilRecord->save(false);
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $this->_clearStencilCache();

        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_STENCIL)) {
            $this->trigger(self::EVENT_AFTER_SAVE_STENCIL, new StencilEvent([
                'stencil' => $this->getStencilById($stencilRecord->id),
                'isNew' => $isNewStencil,
            ]));
        }
    }

    public function deleteStencilById(int $id): bool
    {
        $stencil = $this->getStencilById($id);

        if (!$stencil) {
            return false;
        }

        return $this->deleteStencil($stencil);
    }

    public function deleteStencil(Stencil $stencil): bool
    {
        if (!$stencil->canDelete()) {
            return false;
        }

        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_STENCIL)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_STENCIL, new StencilEvent([
                'stencil' => $stencil,
            ]));
        }

        if ($stencil->isSiteScope()) {
            return $this->_deleteSiteStencil($stencil);
        }

        Craft::$app->getProjectConfig()->remove(self::CONFIG_STENCILS_KEY . '.' . $stencil->uid);

        return true;
    }

    public function handleDeletedStencil(ConfigEvent $event): void
    {
        if (!DbSchema::tableExists(Table::FORMIE_STENCILS)) {
            return;
        }

        $stencilUid = $event->tokenMatches[0];

        $stencil = $this->getStencilByUid($stencilUid);

        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_STENCIL_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_STENCIL_DELETE, new StencilEvent([
                'stencil' => $stencil,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $stencilRecord = $this->_getStencilsRecord($stencilUid);
            $stencilRecord->softDelete();
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $this->_clearStencilCache();

        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_STENCIL)) {
            $this->trigger(self::EVENT_AFTER_DELETE_STENCIL, new StencilEvent([
                'stencil' => $stencil,
            ]));
        }
    }


    // Private Methods
    // =========================================================================

    private function _saveProjectStencil(Stencil $stencil, bool $isNewStencil): bool
    {
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $stencil->addError('name', Craft::t('formie', 'Project stencils cannot be saved when admin changes are disabled.'));

            return false;
        }

        if ($isNewStencil) {
            $stencilUid = StringHelper::UUID();
            $stencil->scope = self::SCOPE_PROJECT;
        } else {
            $stencilUid = Db::uidById(Table::FORMIE_STENCILS, $stencil->id);
        }

        $configData = $stencil->dateDeleted ? null : $stencil->getConfig();
        $configPath = self::CONFIG_STENCILS_KEY . '.' . $stencilUid;
        Craft::$app->getProjectConfig()->set($configPath, $configData);

        if ($isNewStencil) {
            $stencil->id = Db::idByUid(Table::FORMIE_STENCILS, $stencilUid);
            $stencil->uid = $stencilUid;
        }

        $this->_clearStencilCache();

        return true;
    }

    private function _saveSiteStencil(Stencil $stencil, bool $isNewStencil): bool
    {
        $stencil->scope = self::SCOPE_SITE;
        $config = $stencil->getConfig();

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            if ($isNewStencil) {
                $stencilRecord = new StencilRecord();
                $stencilRecord->uid = StringHelper::UUID();
            } else {
                $stencilRecord = StencilRecord::findOne($stencil->id);

                if (!$stencilRecord) {
                    throw new \Exception('Invalid stencil ID: ' . $stencil->id);
                }
            }

            $stencilRecord->name = $config['name'];
            $stencilRecord->handle = $config['handle'];
            $stencilRecord->scope = self::SCOPE_SITE;
            $stencilRecord->data = Json::encode($config['data']);

            if ($defaultStatusUid = $config['defaultStatus'] ?? null) {
                $defaultStatus = Formie::$plugin->getStatuses()->getStatusByUid($defaultStatusUid);
                $stencilRecord->defaultStatusId = $defaultStatus?->id;
            } else {
                $stencilRecord->defaultStatusId = $stencil->defaultStatusId;
            }

            if ($templateUid = $config['template'] ?? null) {
                $template = Formie::$plugin->getFormTemplates()->getTemplateByUid($templateUid);
                $stencilRecord->templateId = $template?->id;
            } else {
                $stencilRecord->templateId = $stencil->templateId;
            }

            if ($submitActionEntryUid = $config['submitActionEntry'] ?? null) {
                $submitActionEntry = Craft::$app->getElements()->getElementByUid($submitActionEntryUid);
                $stencilRecord->submitActionEntryId = $submitActionEntry?->id;
                $stencilRecord->submitActionEntrySiteId = $submitActionEntry?->siteId;
            } else {
                $stencilRecord->submitActionEntryId = $stencil->submitActionEntryId;
                $stencilRecord->submitActionEntrySiteId = $stencil->submitActionEntrySiteId;
            }

            $stencilRecord->save(false);

            $stencil->id = $stencilRecord->id;
            $stencil->uid = $stencilRecord->uid;

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $this->_clearStencilCache();

        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_STENCIL)) {
            $this->trigger(self::EVENT_AFTER_SAVE_STENCIL, new StencilEvent([
                'stencil' => $this->getStencilById($stencil->id),
                'isNew' => $isNewStencil,
            ]));
        }

        return true;
    }

    private function _deleteSiteStencil(Stencil $stencil): bool
    {
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $stencilRecord = StencilRecord::findOne($stencil->id);

            if (!$stencilRecord || $stencilRecord->scope !== self::SCOPE_SITE) {
                $transaction->rollBack();

                return false;
            }

            $stencilRecord->softDelete();
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $this->_clearStencilCache();

        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_STENCIL)) {
            $this->trigger(self::EVENT_AFTER_DELETE_STENCIL, new StencilEvent([
                'stencil' => $stencil,
            ]));
        }

        return true;
    }

    private function _findConflictingStencil(Stencil $stencil): ?Stencil
    {
        $existingStencil = $this->getStencilByHandle($stencil->handle);

        if ($existingStencil && (!$stencil->id || $stencil->id != $existingStencil->id)) {
            return $existingStencil;
        }

        return null;
    }

    private function _clearStencilCache(): void
    {
        $this->_stencils = null;
    }

    private function _createStencilsQuery(bool $withTrashed = false): Query
    {
        $select = [
            'id',
            'name',
            'handle',
            'data',
            'templateId',
            'submitActionEntryId',
            'defaultStatusId',
            'dateDeleted',
            'uid',
        ];

        if (DbSchema::columnExists(Table::FORMIE_STENCILS, 'scope')) {
            array_splice($select, 3, 0, ['scope']);
        }

        $orderBy = DbSchema::columnExists(Table::FORMIE_STENCILS, 'scope')
            ? ['scope' => SORT_ASC, 'name' => SORT_ASC]
            : ['name' => SORT_ASC];

        $query = (new Query())
            ->select($select)
            ->orderBy($orderBy)
            ->from([Table::FORMIE_STENCILS]);

        if (!$withTrashed) {
            $query->where(['dateDeleted' => null]);
        }

        return $query;
    }

    private function _getStencilsRecord(string $uid): StencilRecord
    {
        /** @var StencilRecord $stencil */
        if ($stencil = StencilRecord::findWithTrashed()->where(['uid' => $uid])->one()) {
            return $stencil;
        }

        return new StencilRecord();
    }
}
