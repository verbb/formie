<?php
namespace verbb\formie\services;

use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\events\StencilEvent;
use verbb\formie\models\Notification;
use verbb\formie\models\Stencil;
use verbb\formie\records\Stencil as StencilRecord;

use Craft;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\helpers\DateTimeHelper;

use yii\base\Component;

use Exception;
use Throwable;
use yii\web\ServerErrorHttpException;
use yii\base\NotSupportedException;
use yii\base\ErrorException;

class Stencils extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_STENCIL = 'beforeSaveStencil';
    public const EVENT_AFTER_SAVE_STENCIL = 'afterSaveStencil';
    public const EVENT_BEFORE_DELETE_STENCIL = 'beforeDeleteStencil';
    public const EVENT_BEFORE_APPLY_STENCIL_DELETE = 'beforeApplyStencilDelete';
    public const EVENT_AFTER_DELETE_STENCIL = 'afterDeleteStencil';
    public const CONFIG_STENCILS_KEY = 'formie.stencils';


    // Private Properties
    // =========================================================================

    /**
     * @var Stencil[]
     */
    private ?array $_stencils = null;


    // Public Methods
    // =========================================================================

    /**
     * Returns all stencils.
     *
     * @param bool $withTrashed
     * @return Stencil[]
     */
    public function getAllStencils(bool $withTrashed = false): array
    {
        // Get the caches items if we have them cached, and the request is for non-trashed items
        if ($this->_stencils !== null) {
            return $this->_stencils;
        }

        $results = $this->_createStencilsQuery($withTrashed)->all();
        $stencils = [];

        foreach ($results as $row) {
            $stencils[] = new Stencil($row);
        }

        return $this->_stencils = $stencils;
    }

    /**
     * Returns the stencils as an array for using in a select box.
     *
     * @return array
     */
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

    /**
     * Gets a single stencil by its ID.
     *
     * @param int $id
     * @return Stencil|null
     */
    public function getStencilById(int $id): ?Stencil
    {
        return ArrayHelper::firstWhere($this->getAllStencils(), 'id', $id);
    }

    /**
     * Gets a single stencil by its handle.
     *
     * @param string $handle
     * @return Stencil|null
     */
    public function getStencilByHandle(string $handle): ?Stencil
    {
        return ArrayHelper::firstWhere($this->getAllStencils(), 'handle', $handle, false);
    }

    /**
     * Returns a stencil identified by its UID.
     *
     * @param string $uid
     * @return Stencil|null
     */
    public function getStencilByUid(string $uid): ?Stencil
    {
        return ArrayHelper::firstWhere($this->getAllStencils(), 'uid', $uid, false);
    }

    /**
     * Saves the stencil.
     *
     * @param Stencil $stencil
     * @param bool $runValidation
     * @return bool
     * @throws Throwable
     */
    public function saveStencil(Stencil $stencil, bool $runValidation = true): bool
    {
        $isNewStencil = !$stencil->id;

        // Fire a 'beforeSaveStatus' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_STENCIL)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_STENCIL, new StencilEvent([
                'stencil' => $stencil,
                'isNew' => $isNewStencil,
            ]));
        }

        if ($runValidation && !$stencil->validate()) {
            Formie::log('Stencil not saved due to validation error.');

            return false;
        }

        if ($isNewStencil) {
            $stencilUid = StringHelper::UUID();
        } else {
            $stencilUid = Db::uidById('{{%formie_stencils}}', $stencil->id);
        }

        // Make sure no stencils that are not archived share the handle
        $existingStencil = $this->getStencilByHandle($stencil->handle);

        if ($existingStencil && (!$stencil->id || $stencil->id != $existingStencil->id)) {
            $stencil->addError('handle', Craft::t('formie', 'That handle is already in use'));
            return false;
        }

        $projectConfig = Craft::$app->getProjectConfig();

        if ($stencil->dateDeleted) {
            $configData = null;
        } else {
            $configData = $stencil->getConfig();
        }

        // For new stencils, check for any globally enabled captchas and set as enabled
        if ($isNewStencil) {
            $captchas = Formie::$plugin->getIntegrations()->getAllCaptchas();

            foreach ($captchas as $captcha) {
                if ($captcha->getEnabled()) {
                    $configData['data']['settings']['integrations'][$captcha->handle]['enabled'] = true;
                }
            }
        }

        $configPath = self::CONFIG_STENCILS_KEY . '.' . $stencilUid;
        $projectConfig->set($configPath, $configData);

        if ($isNewStencil) {
            $stencil->id = Db::idByUid('{{%formie_stencils}}', $stencilUid);
        }

        return true;
    }

    /**
     * Handle stencil change.
     *
     * @param ConfigEvent $event
     * @throws Throwable
     */
    public function handleChangedStencil(ConfigEvent $event): void
    {
        $stencilUid = $event->tokenMatches[0];
        $data = $event->newValue;

        // Ensure template configs are applied first
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

            // Handle UIDs for templates/statuses
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

            // Save the status
            $stencilRecord->save(false);
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Fire an 'afterSaveStatus' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_STENCIL)) {
            $this->trigger(self::EVENT_AFTER_SAVE_STENCIL, new StencilEvent([
                'stencil' => $this->getStencilById($stencilRecord->id),
                'isNew' => $isNewStencil,
            ]));
        }
    }

    /**
     * Delete a stencil by its id.
     *
     * @param int $id
     * @return bool
     * @throws Throwable
     */
    public function deleteStencilById(int $id): bool
    {
        $stencil = $this->getStencilById($id);

        if (!$stencil) {
            return false;
        }

        return $this->deleteStencil($stencil);
    }

    /**
     * Deletes a stencil.
     *
     * @param Stencil $stencil The stencil
     * @return bool Whether the stencil was deleted successfully
     */
    public function deleteStencil(Stencil $stencil): bool
    {
        // Fire a 'beforeDeleteStencil' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_STENCIL)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_STENCIL, new StencilEvent([
                'stencil' => $stencil,
            ]));
        }

        Craft::$app->getProjectConfig()->remove(self::CONFIG_STENCILS_KEY . '.' . $stencil->uid);
        return true;
    }

    /**
     * Handle stencil being deleted
     *
     * @param ConfigEvent $event
     * @throws Throwable
     */
    public function handleDeletedStencil(ConfigEvent $event): void
    {
        $stencilUid = $event->tokenMatches[0];

        $stencil = $this->getStencilByUid($stencilUid);

        // Fire a 'beforeApplyStatusDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_STENCIL_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_STENCIL_DELETE, new StencilEvent([
                'stencil' => $stencil,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $stencilRecord = $this->_getStencilsRecord($stencilUid);

            // Save the stencil
            $stencilRecord->softDelete();

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Fire an 'afterDeleteStatus' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_STENCIL)) {
            $this->trigger(self::EVENT_AFTER_DELETE_STENCIL, new StencilEvent([
                'stencil' => $stencil,
            ]));
        }
    }

    /**
     * Applies a stencil to a form.
     *
     * @param Form $form
     * @param Stencil $stencil
     * @throws ErrorException
     * @throws \yii\base\Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function applyStencil(Form $form, Stencil $stencil): void
    {
        $form->settings = $stencil->data->settings;
        $form->userDeletedAction = $stencil->data->userDeletedAction;
        $form->fileUploadsAction = $stencil->data->fileUploadsAction;
        $form->dataRetention = $stencil->data->dataRetention;
        $form->dataRetentionValue = $stencil->data->dataRetentionValue;

        $form->setTemplate($stencil->getTemplate());
        $form->setDefaultStatus($stencil->getDefaultStatus());

        $fieldLayout = Formie::$plugin->getForms()->buildFieldLayout($stencil->data->pages, Form::class);
        $form->setFormFieldLayout($fieldLayout);

        $notifications = [];
        foreach ($stencil->data->notifications as $notificationData) {
            if (isset($notificationData['hasError'])) {
                unset($notificationData['hasError']);
            }

            if (isset($notificationData['errors'])) {
                unset($notificationData['errors']);
            }

            // Ensure we deal with any potentially out-of-date stencil data, as we add new settings
            // Could also probably add this to a migration to be extra safe.
            $notificationData['recipients'] = $notificationData['recipients'] ?? 'email';

            $notifications[] = new Notification($notificationData);
        }

        $form->setNotifications($notifications);
    }


    // Private Methods
    // =========================================================================

    /**
     * Returns a Query object prepped for retrieving stencils.
     *
     * @param bool $withTrashed
     * @return Query
     */
    private function _createStencilsQuery(bool $withTrashed = false): Query
    {
        $query = (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'data',
                'templateId',
                'submitActionEntryId',
                'defaultStatusId',
                'dateDeleted',
                'uid',
            ])
            ->orderBy('name ASC')
            ->from(['{{%formie_stencils}}']);

        if (!$withTrashed) {
            $query->where(['dateDeleted' => null]);
        }

        return $query;
    }

    /**
     * Gets a stencil record by uid.
     *
     * @param string $uid
     * @return StencilRecord
     */
    private function _getStencilsRecord(string $uid): StencilRecord
    {
        /** @var StencilRecord $stencil */
        if ($stencil = StencilRecord::findWithTrashed()->where(['uid' => $uid])->one()) {
            return $stencil;
        }

        return new StencilRecord();
    }
}
