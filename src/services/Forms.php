<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\base\NestedFieldTrait;
use verbb\formie\base\FormField;
use verbb\formie\elements\Form;
use verbb\formie\migrations\CreateFormContentTable;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\FormSettings;
use verbb\formie\records\Form as FormRecord;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\DateTimeHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\MigrationHelper;
use craft\helpers\StringHelper;

use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use Throwable;

class Forms extends Component
{
    // Private Properties
    // =========================================================================

    private $_uniqueFormAndFieldHandles = [];


    // Public Methods
    // =========================================================================

    /**
     * Returns a form by it's ID.
     *
     * @param int $formId
     * @param int|null $siteId
     * @return Form|null
     */
    public function getFormById(int $formId, int $siteId = null)
    {
        $query = Form::find()->id($formId)->siteId($siteId);
        return $query->one();
    }

    /**
     * Returns a form by it's handle.
     *
     * @param string $handle
     * @param int|null $siteId
     * @return Form|null
     */
    public function getFormByHandle(string $handle, int $siteId = null)
    {
        $query = Form::find()->handle($handle)->siteId($siteId);
        return $query->one();
    }

    /**
     * @inheritDoc
     */
    public function getFormRecord($formId)
    {
        $result = $this->_createFormsQuery($formId)->one();

        return ($result) ? new Form($result) : null;
    }

    /**
     * Saves a form.
     *
     * @param Form $form
     * @param bool $runValidation
     * @return bool
     * @throws Throwable
     */
    public function saveForm(Form $form, bool $runValidation = true): bool
    {
        if ($runValidation && !$form->validate()) {
            Formie::log('Form not saved due to validation error.');

            return false;
        }

        $isNewForm = !$form->id;

        // Make sure it's got a UUID
        if ($isNewForm) {
            if (empty($this->uid)) {
                $form->uid = StringHelper::UUID();
            }
        } else if (!$form->uid) {
            $form->uid = Db::uidById('{{%formie_forms}}', $form->id);
        }

        if (!$isNewForm) {
            $form->oldHandle = $this->getOldHandle($form);
        }

        $fieldsService = Craft::$app->getFields();
        $contentService = Craft::$app->getContent();
        $transaction = Craft::$app->db->beginTransaction();

        try {
            // Prep the fields for save
            $fieldLayout = $form->getFormFieldLayout();
            
            foreach ($fieldLayout->getFields() as $field) {
                $field->context = $form->getFormFieldContext();
                $fieldsService->prepFieldForSave($field);
            }

            $allFields = Formie::$plugin->getFields()->getAllFields();
            $allFieldIds = ArrayHelper::getColumn($fieldLayout->getFields(), 'id');
            $syncsService = Formie::$plugin->getSyncs();

            // Get the original field context for later.
            $originalFieldContext = $contentService->fieldContext;
            $originalContentTable = $contentService->contentTable;

            // Create the content table name first since the form will need it
            $contentTable = $this->defineContentTableName($form);
            $oldContentTable = $this->defineContentTableName($form, true);
            $form->fieldContentTable = $contentTable;

            // Set the field context.
            $contentService->fieldContext = $form->getFormFieldContext();
            $contentService->contentTable = $form->fieldContentTable;

            // Create table or rename if necessary.
            if (!Craft::$app->getDb()->tableExists($contentTable)) {
                if ($oldContentTable && Craft::$app->db->tableExists($oldContentTable)) {
                    MigrationHelper::renameTable($oldContentTable, $contentTable);
                } else {
                    $this->_createContentTable($contentTable);
                }
            }

            // Delete deleted fields.
            foreach ($allFields as $field) {
                /* @var FormField $field */
                if ($field->context === $form->getFormFieldContext() && !in_array($field->id, $allFieldIds)) {
                    $fieldsService->deleteField($field);
                }
            }

            // Save fields and syncs.
            foreach ($fieldLayout->getFields() as $field) {
                $refId = null;

                if ($field->getIsRef()) {
                    $refId = $field->id;
                }

                /* @var FormField $field */
                $fieldsService->saveField($field);

                if ($refId) {
                    $toField = $syncsService->parseSyncId($refId);

                    if ($toField && $sync = $syncsService->createSync($field, $toField)) {
                        $syncsService->saveSync($sync);
                    }
                }

                $syncsService->syncField($field);
            }

            // Validate any enabled integrations
            $integrations = Formie::$plugin->getIntegrations()->getAllEnabledIntegrationsForForm($form);

            foreach ($integrations as $integration) {
                $integration->setScenario(Integration::SCENARIO_FORM);

                if (!$integration->validate()) {
                    // Add any errors to the form's settings - maybe move this to the form settings model?
                    $form->settings->integrations[$integration->handle]['errors'] = $integration->getErrors();

                    return false;
                }
            }

            // For new forms, check for any globally enabled captchas and set as enabled
            if ($isNewForm) {
                $captchas = Formie::$plugin->getIntegrations()->getAllCaptchas();

                foreach ($captchas as $captcha) {
                    if ($captcha->enabled) {
                        $form->settings->integrations[$captcha->handle]['enabled'] = true;
                    }
                }
            }

            $success = $fieldsService->saveLayout($fieldLayout);

            // Set content table back to original value.
            $contentService->fieldContext = $originalFieldContext;
            $contentService->contentTable = $originalContentTable;

            // Prune empty syncs.
            Formie::$plugin->getSyncs()->pruneSyncs();

            $form->fieldLayoutId = $fieldLayout->id;
            $form->setFormFieldLayout($fieldLayout);

            if (!Craft::$app->getElements()->saveElement($form)) {
                return false;
            }

            $notificationsService = Formie::$plugin->getNotifications();
            $notifications = $form->getNotifications();

            foreach ($notifications as $notification) {
                $notification->formId = $form->id;
                $notificationsService->saveNotification($notification);
            }

            // Prune deleted notifications.
            if (!$isNewForm) {
                $allNotifications = $notificationsService->getFormNotifications($form);

                foreach ($allNotifications as $notification) {
                    if (!ArrayHelper::contains($notifications, 'id', $notification->id)) {
                        $notificationsService->deleteNotificationById($notification->id);
                    }
                }
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return true;
    }

    /**
     * Deletes the provided form.
     *
     * @param Form $form
     * @return bool
     * @throws Throwable
     * @throws NotSupportedException
     */
    public function deleteForm(Form $form)
    {
        // Clear the schema cache
        $db = Craft::$app->getDb();
        $db->getSchema()->refresh();

        // If we are deleting a trashed form, we're killing stuff for good.
        if ($form->trashed) {
            // Permanently drop the content table
            $db->createCommand()
                ->dropTableIfExists($form->fieldContentTable)
                ->execute();

            return true;
        } else {
            $transaction = $db->beginTransaction();
            try {
                // Rename the content table. This is so we can easily determine soft-deleted
                // form content tables to cleanup later, or restore
                $newContentTableName = $this->defineContentTableName($form, false, true);

                MigrationHelper::renameTable($form->fieldContentTable, $newContentTableName);

                $db->createCommand()
                    ->update('{{%formie_forms}}', ['fieldContentTable' => $newContentTableName], [
                        'id' => $form->id,
                    ])->execute();

                $form->fieldContentTable = $newContentTableName;

                if ($fieldLayout = $form->getFormFieldLayout()) {
                    Craft::$app->getFields()->deleteLayout($fieldLayout);
                }

                $transaction->commit();

                return true;
            } catch (Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
        }

        return false;
    }

    /**
     * Gets the old form handle, or null.
     *
     * @param Form $form
     * @return string|null
     */
    public function getOldHandle(Form $form)
    {
        $formRecord = FormRecord::findOne($form->id);
        return $formRecord->getOldHandle();
    }

    /**
     * Assembles a field layout for a form from the POST request.
     *
     * @param bool $duplicate
     * @return FieldLayout
     */
    public function assembleLayout($duplicate = false)
    {
        $request = Craft::$app->getRequest();
        $pagesData = $request->getBodyParam('pages');

        if (!$pagesData) {
            $fieldLayout = new FieldLayout([ 'type' => Form::class ]);
            $fieldLayout->setPages([
                new FieldLayoutPage([
                    'name' => Craft::t('site', 'Page 1'),
                    'sortOrder' => '0',
                ])
            ]);

            return $fieldLayout;
        }

        $fieldLayoutId = $request->getParam('fieldLayoutId');
        $pagesData = Json::decode($pagesData);

        $fieldLayout = $this->buildFieldLayout($pagesData, Form::class, $duplicate);
        $fieldLayout->id = $duplicate ? null : $fieldLayoutId;

        return $fieldLayout;
    }

    /**
     * Builds a form element from POST data.
     *
     * @return Form
     * @throws Throwable
     */
    public function buildFormFromPost(): Form
    {
        $request = Craft::$app->getRequest();
        $formId = $request->getParam('formId');
        $siteId = $request->getParam('siteId');
        $duplicate = $request->getParam('duplicate');

        if ($formId && !$duplicate) {
            $form = Craft::$app->getElements()->getElementById($formId, Form::class, $siteId);

            if (!$form) {
                throw new Exception('No form found with that id.');
            }
        } else {
            $form = new Form();
        }

        // In case the handle is changed, to update the content table.
        $form->title = $request->getParam('title', $form->title);
        if ($duplicate) {
            $form->title .= ' ' . Craft::t('formie', 'Copy');
        }

        $form->siteId = $siteId ?? $form->siteId;
        $form->handle = $request->getParam('handle', $form->handle);
        $form->templateId = $request->getParam('templateId', $form->templateId);
        $form->requireUser = $request->getParam('requireUser', $form->requireUser);
        $form->availability = $request->getParam('availability', $form->availability);
        $form->defaultStatusId = $request->getParam('defaultStatusId', $form->defaultStatusId);
        $form->userDeletedAction = $request->getParam('userDeletedAction', $form->userDeletedAction);
        $form->fileUploadsAction = $request->getParam('fileUploadsAction', $form->fileUploadsAction);
        $form->dataRetention = $request->getParam('dataRetention', $form->dataRetention);
        $form->dataRetentionValue = $request->getParam('dataRetentionValue', $form->dataRetentionValue);
        $form->availabilitySubmissions = $request->getParam('availabilitySubmissions', $form->availabilitySubmissions);
        $form->availabilityFrom = (($date = $request->getParam('availabilityFrom')) !== false ? (DateTimeHelper::toDateTime($date) ?: null) : $form->availabilityFrom);
        $form->availabilityTo = (($date = $request->getParam('availabilityTo')) !== false ? (DateTimeHelper::toDateTime($date) ?: null) : $form->availabilityTo);

        $entryId = $request->getParam('submitActionEntryId', $form->submitActionEntryId);
        $form->submitActionEntryId = is_array($entryId) && !empty($entryId) ? $entryId[0] : null;

        // Set the settings.
        if (!$form->settings) {
            $form->settings = new FormSettings();
        }

        // Merge in any new settings, while retaining existing ones. Important for users with permissions.
        if ($newSettings = $request->getParam('settings')) {
            // Retain any integration form settings before wiping them
            $oldIntegrationSettings = $form->settings->integrations ?? [];
            $newIntegrationSettings = $newSettings['integrations'] ?? [];
            $newSettings['integrations'] = array_merge($oldIntegrationSettings, $newIntegrationSettings);

            $form->settings->setAttributes($newSettings, false);
        }

        if ($duplicate) {
            $form->handle = $form->handle . rand();

            // Have to save to get an ID.
            // Formie::$plugin->getForms()->saveForm($form);
        }

        if ($stencilId = $request->getParam('applyStencilId')) {
            if ($stencil = Formie::$plugin->getStencils()->getStencilById($stencilId)) {
                Formie::$plugin->getStencils()->applyStencil($form, $stencil);
            }
        } else {
            // Generate and set the field layout.
            if ($layout = Formie::$plugin->getForms()->assembleLayout($duplicate)) {
                $form->setFormFieldLayout($layout);
            }

            // Set the notifications.
            $notifications = Formie::$plugin->getNotifications()->buildNotificationsFromPost();
            $form->setNotifications($notifications);
        }

        // Set custom field values.
        $form->setFieldValuesFromRequest('fields');

        return $form;
    }

    /**
     * Builds a field layout from the provided page data.
     *
     * @param array $data
     * @param string $type
     * @param bool $duplicate
     * @return FieldLayout
     */
    public function buildFieldLayout(array $data, string $type, $duplicate = false)
    {
        $pages = [];
        $fields = [];

        foreach ($data as $pageIndex => $pageData) {
            $pageFields = [];

            $rows = ArrayHelper::getValue($pageData, 'rows', []);
            foreach ($rows as $rowIndex => $rowData) {
                foreach ($rowData['fields'] as $fieldIndex => $fieldData) {
                    $settings = $fieldData['settings'];

                    ArrayHelper::remove($settings, 'label');
                    ArrayHelper::remove($settings, 'handle');
                    $required = ArrayHelper::remove($settings, 'required', false);
                    $columnWidth = ArrayHelper::remove($settings, 'columnWidth', 12);

                    $fieldId = $fieldData['id'] ?? null;

                    // Take care of new fields, particularly an issue in Postgres, setting the id to `new-4332`.
                    if ($duplicate || strpos($fieldId, 'new') === 0) {
                        $fieldId = null;
                    }

                    $field = Craft::$app->getFields()->createField([
                        'id' => $fieldId,
                        'type' => $fieldData['type'],
                        'name' => $fieldData['label'] ?? null,
                        'handle' => $fieldData['handle'] ?? null,
                        'rowId' => $fieldData['rowId'] ?? null,
                        'rowIndex' => $rowIndex,
                        'columnWidth' => $columnWidth,
                        'settings' => $settings,
                        'required' => (bool)$required,
                    ]);

                    $field->afterCreateField($fieldData);

                    if ($field instanceof NestedFieldInterface) {
                        /* @var NestedFieldInterface|NestedFieldTrait $field */
                        $field->setRows($fieldData['rows'], $duplicate);
                    }

                    $field->sortOrder = $fieldIndex;

                    $fields[] = $field;
                    $pageFields[] = $field;
                }
            }

            $page = new FieldLayoutPage();
            $page->name = urldecode($pageData['label']);
            $page->sortOrder = '' . $pageIndex;
            $page->setFields($pageFields);

            // Set page settings.
            $pageSettings = $pageData['settings'] ?? [];
            unset($pageSettings['label']);
            Craft::configure($page->settings, $pageSettings);

            $pages[] = $page;
        }

        $fieldLayout = new FieldLayout([ 'type' => $type ]);
        $fieldLayout->setPages($pages);
        $fieldLayout->setFields($fields);

        return $fieldLayout;
    }

    /**
     * Defines a unique content table name for a form.
     *
     * @param $form
     * @param bool $useOld
     * @param bool $deleted
     * @return string
     */
    public function defineContentTableName($form, bool $useOld = false, bool $deleted = false)
    {
        if ($form instanceof Form) {
            if ($useOld && (!$form->oldHandle || $form->oldHandle === $form->handle)) {
                return null;
            }

            $handle = $useOld ? $form->oldHandle : $form->handle;
        } else {
            $handle = $form;
        }

        $prefix = $deleted ? 'fmcd_' : 'fmc_';

        $baseName = $prefix . StringHelper::toLowerCase($handle);
        $db = Craft::$app->getDb();
        $i = -1;

        do {
            $i++;
            $name = '{{%' . $baseName . ($i !== 0 ? '_' . $i : '') . '}}';

        } while ($name !== $form->fieldContentTable && $db->tableExists($name));

        return $name;
    }

    /**
     * Validates a form's field settings.
     *
     * If the settings don’t validate, any validation errors will be stored on the settings model.
     *
     * @param Form $form The form
     * @return bool Whether the fields validated.
     * @throws \Exception
     */
    public function validateFormFields(Form $form): bool
    {
        $validates = true;

        $this->_uniqueFormAndFieldHandles = [];

        $uniquePageAttributes = ['name'];
        $uniqueAttributeValues = [];

        foreach ($form->getPages() as $page) {
            if (!$this->validatePage($form, $page, false)) {
                // Don't break out of the loop because we still want to get validation errors for the remaining fields.
                $validates = false;
            }

            // Do our own unique name/handle validation, since the DB-based validation can't be trusted when saving
            // multiple records at once
            foreach ($uniquePageAttributes as $attribute) {
                $value = $page->$attribute;

                if ($value && (!isset($uniqueAttributeValues[$attribute]) || !in_array($value, $uniqueAttributeValues[$attribute], true))) {
                    $uniqueAttributeValues[$attribute][] = $value;
                } else {
                    $page->addError($attribute, Craft::t('app', '{attribute} "{value}" has already been taken.', [
                        'attribute' => $page->getAttributeLabel($attribute),
                        'value' => Html::encode($value)
                    ]));

                    $validates = false;
                }
            }
        }

        return $validates;
    }

    /**
     * Validates a page and all it's fields.
     *
     * @param Form $form
     * @param FieldLayoutPage $page
     * @param bool $validateUniques
     * @return bool
     * @throws \Exception
     */
    public function validatePage(Form $form, FieldLayoutPage $page, bool $validateUniques): bool
    {
        $validates = true;

        // Can't validate multiple new rows at once so we'll need to give these temporary context to avoid false unique
        // handle validation errors, and just validate those manually. Also apply the future fieldColumnPrefix so that
        // field handle validation takes its length into account.
        $contentService = Craft::$app->getContent();
        $originalFieldContext = $contentService->fieldContext;
        $originalFieldColumnPrefix = $contentService->fieldColumnPrefix;

        $contentService->fieldContext = StringHelper::randomString(10);
        $contentService->fieldColumnPrefix = 'field_';

        foreach ($page->getFields() as $field) {
            $field->validate();

            // Make sure the block type handle + field handle combo is unique for the whole field. This prevents us from
            // worrying about content column conflicts like "a" + "b_c" == "a_b" + "c".

            /* @var FormField $field */
            if ($form->handle && $field->handle) {
                $formAndFieldHandle = $form->handle . '_' . $field->handle;

                if (in_array($formAndFieldHandle, $this->_uniqueFormAndFieldHandles, true)) {
                    // This error *might* not be entirely accurate, but it's such an edge case that it's probably better
                    // for the error to be worded for the common problem (two duplicate handles within the same block
                    // type).
                    $error = Craft::t('formie', '{attribute} "{value}" has already been taken.', [
                        'attribute' => Craft::t('formie', 'Handle'),
                        'value' => $field->handle
                    ]);

                    $field->addError('handle', $error);
                } else {
                    $this->_uniqueFormAndFieldHandles[] = $formAndFieldHandle;
                }
            }

            if ($field->hasErrors()) {
                $validates = false;
            }
        }

        $contentService->fieldContext = $originalFieldContext;
        $contentService->fieldColumnPrefix = $originalFieldColumnPrefix;

        return $validates;
    }

    /**
     * Returns true if the page or fields have any errors.
     *
     * @param Form $form
     * @return bool
     * @throws InvalidConfigException
     */
    public function pagesHasErrors(Form $form): bool
    {
        $fieldLayout = $form->getFormFieldLayout();

        if ($fieldLayout) {
            $fieldLayout->hasErrors();

            foreach ($fieldLayout->getPages() as $page) {
                if ($page->hasErrors()) {
                    return true;
                }

                foreach ($page->getFields() as $field) {
                    /* @var FormField $field */
                    if ($field->hasErrors()) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Handles the "before submit button" form hook.
     *
     * @param $context
     * @return string
     */
    public function handleBeforeSubmitHook($context)
    {
        $form = $context['form'] ?? null;
        $page = $context['page'] ?? null;

        return Formie::$plugin->getIntegrations()->getCaptchasHtmlForForm($form, $page);
    }

    /**
     * Prunes any content tables for forms that have been soft-deleted. Run via GC.
     *
     * @throws \yii\db\Exception
     */
    public function pruneContentTables($consoleInstance = null)
    {
        $db = Craft::$app->getDb();

        // Find any `fmcd_*` tables - these are content tables for soft-deleted forms
        foreach ($db->schema->getTableNames() as $tableName) {
            if (strstr($tableName, 'fmcd_')) {
                $db->createCommand()
                    ->dropTableIfExists($tableName)
                    ->execute();
            }
        }
    }


    // Form Builder
    // -------------------------------------------------------------------------

    /**
     * Returns the list of form builder tabs.
     *
     * @param Form|null $form
     * @return array
     */
    public function buildTabs($form = null)
    {
        $user = Craft::$app->getUser();

        $tabs = [];

        $tabs[] = [
            'label' => Craft::t('formie', 'Fields'),
            'value' => 'fields',
            'url' => '#tab-fields',
        ];

        if ($form && $fieldLayout = $form->getFieldLayout()) {
            foreach ($fieldLayout->getTabs() as $tab) {
                $tabSlug = StringHelper::toKebabCase($tab->name);

                $tabs[] = [
                    'label' => $tab->name,
                    'value' => "form-fields-$tabSlug",
                    'url' => "#tab-form-fields-$tabSlug",
                    'tab' => $tab,
                ];
            }
        }

        $suffix = ':' . ($form->uid ?? '');

        if ($user->checkPermission('formie-manageFormAppearance') || $user->checkPermission("formie-manageFormAppearance{$suffix}")) {
            $tabs[] = [
                'label' => Craft::t('formie', 'Appearance'),
                'value' => 'appearance',
                'url' => '#tab-appearance',
            ];
        }

        if ($user->checkPermission('formie-manageFormBehavior') || $user->checkPermission("formie-manageFormBehavior{$suffix}")) {
            $tabs[] = [
                'label' => Craft::t('formie', 'Behaviour'),
                'value' => 'behaviour',
                'url' => '#tab-behaviour',
            ];
        }

        if ($user->checkPermission('formie-manageNotifications') || $user->checkPermission("formie-manageNotifications{$suffix}")) {
            $tabs[] = [
                'label' => Craft::t('formie', 'Email Notifications'),
                'value' => 'notifications',
                'url' => '#tab-notifications',
            ];
        }

        if ($user->checkPermission('formie-manageFormIntegrations') || $user->checkPermission("formie-manageFormIntegrations{$suffix}")) {
            $tabs[] = [
                'label' => Craft::t('formie', 'Integrations'),
                'value' => 'integrations',
                'url' => '#tab-integrations',
            ];
        }

        if ($user->checkPermission('formie-manageFormSettings') || $user->checkPermission("formie-manageFormSettings{$suffix}")) {
            $tabs[] = [
                'label' => Craft::t('formie', 'Settings'),
                'value' => 'settings',
                'url' => '#tab-settings',
            ];
        }

        return $tabs;
    }

    /**
     * Returns the list of tabs for notifications.
     *
     * @return array
     */
    public function buildNotificationTabs()
    {
        $user = Craft::$app->getUser();

        $tabs = [
            [
                'label' => Craft::t('formie', 'Content'),
                'handle' => 'content',
            ],
            [
                'label' => Craft::t('formie', 'Recipients'),
                'handle' => 'recipients',
            ],
        ];

        if ($user->checkPermission('formie-manageNotificationsAdvanced')) {
            $tabs[] = [
                'label' => Craft::t('formie', 'Advanced'),
                'handle' => 'advanced',
            ];
        }

        if ($user->checkPermission('formie-manageNotificationsTemplates')) {
            $tabs[] = [
                'label' => Craft::t('formie', 'Templates'),
                'handle' => 'templates',
            ];
        }

        return $tabs;
    }


    // Private Methods
    // =========================================================================

    /**
     * Returns a Query object prepped for retrieving forms.
     *
     * @return Query
     */
    private function _createFormsQuery($formId): Query
    {
        return (new Query())
            ->select([
                'id',
                'handle',
                'fieldLayoutId',
                'fieldContentTable',
                'uid',
            ])
            ->from(['{{%formie_forms}}'])
            ->where(['id' => $formId]);
    }

    /**
     * Creates the content table for a form.
     *
     * @param string $tableName
     * @return void
     * @noinspection PhpDocMissingThrowsInspection
     */
    private function _createContentTable(string $tableName)
    {
        $migration = new CreateFormContentTable([
            'tableName' => $tableName
        ]);

        ob_start();
        $migration->up();
        ob_end_clean();
    }
}
