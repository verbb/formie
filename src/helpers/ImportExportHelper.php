<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\fields;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\Plugin;
use verbb\formie\models\EmailTemplate;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\FormSettings;
use verbb\formie\models\FormTemplate;
use verbb\formie\models\Notification;
use verbb\formie\models\PdfTemplate;
use verbb\formie\records\EmailTemplate as EmailTemplateRecord;
use verbb\formie\records\Form as FormRecord;
use verbb\formie\records\FormTemplate as FormTemplateRecord;
use verbb\formie\records\Notification as NotificationRecord;
use verbb\formie\records\PdfTemplate as PdfTemplateRecord;

use Craft;
use craft\elements\Entry;
use craft\helpers\Json;
use craft\db\Query;

use yii\base\Exception;

class ImportExportHelper
{
    // Static Methods
    // =========================================================================

    public static function generateFormExport(Form $formElement): array
    {
        if ($formElement->id && Formie::$plugin->getFormSiteOverrides()->isEnabled()) {
            $sourceSiteId = Formie::$plugin->getFormSiteOverrides()->getSourceSiteId($formElement);
            $canonicalForm = Formie::$plugin->getForms()->getFormById((int)$formElement->id, $sourceSiteId);

            if ($canonicalForm) {
                $formElement = $canonicalForm;
            }
        }

        $formId = $formElement->id;

        // Get form
        $data = FormRecord::find()
            ->select(['*'])
            ->where(['id' => $formId])
            ->asArray()
            ->one();

        // Remove attributes we won't need
        foreach (['id', 'formFieldLayout', 'layoutId', 'dateCreated', 'dateUpdated', 'uid', 'sourceSiteId'] as $key) {
            ArrayHelper::remove($data, $key);
        }

        // Add the title for the form
        $data['title'] = Formie::$plugin->getFormSiteOverrides()->resolveCanonicalFormTitle($formElement);

        // Get form template
        $formTemplateId = ArrayHelper::remove($data, 'templateId');

        if ($formTemplateId) {
            $data['formTemplate'] = FormTemplateRecord::find()
                ->select(['*'])
                ->where(['id' => $formTemplateId])
                ->asArray()
                ->one();

            // Remove attributes we won't need
            foreach (['id', 'dateDeleted', 'dateCreated', 'dateUpdated', 'uid'] as $key) {
                ArrayHelper::remove($data['formTemplate'], $key);
            }
        }

        // Get notifications
        $data['notifications'] = NotificationRecord::find()
            ->select(['*'])
            ->where(['formId' => $formId])
            ->asArray()
            ->all();

        // Get email + pdf templates
        foreach ($data['notifications'] as $i => $notification) {
            foreach (['id', 'formId', 'dateCreated', 'dateUpdated'] as $key) {
                ArrayHelper::remove($notification, $key);
            }

            // Get templates
            $emailTemplateId = ArrayHelper::remove($notification, 'templateId');
            $pdfTemplateId = ArrayHelper::remove($notification, 'pdfTemplateId');

            if ($emailTemplateId) {
                $notification['emailTemplate'] = EmailTemplateRecord::find()
                    ->select(['*'])
                    ->where(['id' => $emailTemplateId])
                    ->asArray()
                    ->one();

                // Remove attributes we won't need
                foreach (['id', 'dateDeleted', 'dateCreated', 'dateUpdated'] as $key) {
                    ArrayHelper::remove($notification['emailTemplate'], $key);
                }
            }

            if ($pdfTemplateId) {
                $notification['pdfTemplate'] = PdfTemplateRecord::find()
                    ->select(['*'])
                    ->where(['id' => $pdfTemplateId])
                    ->asArray()
                    ->one();

                // Remove attributes we won't need
                foreach (['id', 'dateDeleted', 'dateCreated', 'dateUpdated'] as $key) {
                    ArrayHelper::remove($notification['pdfTemplate'], $key);
                }
            }

            $data['notifications'][$i] = $notification;
        }

        // Get pages/rows/fields
        $pages = [];

        foreach ($formElement->getPages() as $page) {
            $pageData = $page->toArray();
            $pageData['settings'] = $page->getSettings();

            // Remove some attributes
            foreach (['id', 'formId', 'layoutId', 'sortOrder', 'dateCreated', 'dateUpdated', 'uid'] as $key) {
                ArrayHelper::remove($pageData, $key);
            }

            // Get all field settings for all pages/rows (supports nested fields)
            self::getFieldInfoForExport($page->getRows(), $pageData);

            $pages[] = $pageData;
        }

        $data['pages'] = $pages;

        // Also save any custom fields' content
        if ($fieldLayout = $formElement->getFieldLayout()) {
            foreach ($fieldLayout->getCustomFields() as $customField) {
                $fieldValue = $formElement->getFieldValue($customField->handle);
                $data['customFields'][$customField->handle] = $customField->serializeValue($fieldValue, $formElement);
            }
        }

        $siteOverrideExport = self::_exportSiteOverrides($formElement);

        if ($siteOverrideExport !== []) {
            $data = array_merge($data, $siteOverrideExport);
        }

        // Handy to keep track of which version of export logic this is, for importing between systems
        $data['exportVersion'] = 'v4';

        return $data;
    }

    public static function createFormFromImport(array $data, ?Form $form = null): Form
    {
        $existingForm = $form;
        $existingFields = [];

        // Store the fields on an existing form, so we can retain their IDs later
        if ($existingForm) {
            $existingFields = self::buildFieldMap($existingForm->getFields());

            // Reset the form layout so it's from scratch
            $form->setFormLayout(new FieldLayout());
        }

        if (!$form) {
            $form = new Form();
        }

        // Grab all the extra bits from the export that need to be handles separately
        $exportVersion = ArrayHelper::remove($data, 'exportVersion');
        $settings = Json::decodeIfJson(ArrayHelper::remove($data, 'settings'));
        $pages = ArrayHelper::remove($data, 'pages');
        $formTemplate = ArrayHelper::remove($data, 'formTemplate');
        $notifications = ArrayHelper::remove($data, 'notifications');
        $sourceSiteHandle = ArrayHelper::remove($data, 'sourceSiteHandle');
        ArrayHelper::remove($data, 'siteOverrides');
        ArrayHelper::remove($data, 'fieldSiteOverrides');
        ArrayHelper::remove($data, 'sourceSiteId');

        // Handle Formie v2 exports
        unset($data['fieldLayoutId']);

        // Handle base form
        $form->setAttributes($data, false);

        if ($sourceSiteHandle) {
            $sourceSiteId = self::_resolveSiteIdByHandle((string)$sourceSiteHandle);

            if ($sourceSiteId) {
                $form->sourceSiteId = $sourceSiteId;
            }
        }

        // Handle any custom field
        $customFields = $data['customFields'] ?? [];

        // Filter out any custom field values for fields that don't exist
        $customFields = array_filter($customFields, function($value, $key) {
            return Craft::$app->getFields()->getFieldByHandle($key);
        }, ARRAY_FILTER_USE_BOTH);

        $form->setFieldValues($customFields);

        // Handle form settings
        $form->settings = new FormSettings();
        $form->settings->setAttributes($settings, false);

        // Check if there is an entry selected as the redirect action. If not found, will cause a fatal error
        if ($form->submitActionEntryId) {
            $entry = Entry::find()->id($form->submitActionEntryId)->one();

            if (!$entry) {
                $form->submitActionEntryId = null;
            }
        }

        // Ensure that the default status exists, just in case there's a project config mismatch
        if ($form->defaultStatusId) {
            $status = Formie::$plugin->getSubmissionStatuses()->getStatusById($form->defaultStatusId);

            if (!$status) {
                $form->defaultStatusId = Formie::$plugin->getSubmissionStatuses()->getDefaultStatus()->id;
            }
        }

        // Traverse import data and update field IDs
        foreach ($pages as &$page) {
            self::updateFieldIdsInImport($page, $existingFields);
        }

        // Ensure the pages/rows/fields are prepped properly
        self::prepFieldsForImport($pages);

        // Handle field layout and pages
        $form->getFormLayout()->setPages($pages);

        // Handle for template
        if ($formTemplate) {
            $template = Formie::$plugin->getFormTemplates()->getTemplateByHandle($formTemplate['handle']);

            if (!$template) {
                $template = new FormTemplate();
                $template->setAttributes($formTemplate, false);
            }

            $form->setTemplate($template);
        }

        if ($notifications !== null) {
            $allNotifications = [];

            foreach ($notifications as $notificationData) {
                $emailTemplate = ArrayHelper::remove($notificationData, 'emailTemplate');
                $pdfTemplate = ArrayHelper::remove($notificationData, 'pdfTemplate');

                // Find or create the notification, based on the form and notification handle
                $notification = Formie::$plugin->getNotifications()->getFormNotificationByHandle($form, $notificationData['handle']) ?? new Notification();

                $notification->setAttributes($notificationData, false);

                if ($emailTemplate) {
                    $template = Formie::$plugin->getEmailTemplates()->getTemplateByHandle($emailTemplate['handle']);

                    if (!$template) {
                        $template = new EmailTemplate();
                        $template->setAttributes($emailTemplate, false);
                    }

                    $notification->setTemplate($template);
                }

                if ($pdfTemplate) {
                    $template = Formie::$plugin->getPdfTemplates()->getTemplateByHandle($pdfTemplate['handle']);

                    if (!$template) {
                        $template = new PdfTemplate();
                        $template->setAttributes($pdfTemplate, false);
                    }

                    $notification->setPdfTemplate($template);
                }

                $allNotifications[] = $notification;
            }

            $form->setNotifications($allNotifications);
        }

        return $form;
    }

    public static function importFormFromJson($json, $formAction = "update"): Form
    {

        // Check if this is multiple forms exports (from Forms index) - just use the one
        if (isset($json[0])) {
            $json = $json[0];
        }
        
        // Find an existing form with the same handle
        $existingForm = null;
        $formHandle = $json['handle'] ?? null;

        if ($formHandle) {
            $existingForm = Formie::$plugin->getForms()->getFormByHandle($formHandle);
        }

        $siteOverrides = ArrayHelper::remove($json, 'siteOverrides');
        $fieldSiteOverrides = ArrayHelper::remove($json, 'fieldSiteOverrides');

        // When creating a new form, change the handle
        if ($formAction === 'create') {
            $formHandles = (new Query())
                ->select(['handle'])
                ->from(Table::FORMIE_FORMS)
                ->column();

            $json['handle'] = HandleHelper::getUniqueHandle($formHandles, $json['handle']);
        }

        if ($formAction === 'update') {
            // Update the form (force)
            $form = self::createFormFromImport($json, $existingForm);
        } else {
            // Create the form element, ready to go
            $form = self::createFormFromImport($json);
        }

        // Because we also export the UID for forms, we need to check if we're importing a new form, but we've
        // found a form with the same UID. If this happens, then the original form will be overwritten
        if ($formAction === 'create') {
            // Is there already a form that exists with this UID? Then we need to assign a new one.
            // See discussion https://github.com/verbb/formie/discussions/1696 and actual issue https://github.com/verbb/formie/issues/1725
            $existingForm = Formie::$plugin->getForms()->getFormByHandle($form->handle);

            if ($existingForm) {
                $form->uid = StringHelper::UUID();
            }
        }

         Craft::$app->getElements()->saveElement($form);

         self::_importSiteOverrides(
             $form,
             $siteOverrides ?? null,
             $fieldSiteOverrides ?? null,
         );

         return $form;
    
    }


    // Private Methods
    // =========================================================================

    private static function getFieldInfoForExport(array $rows, array &$pageData): void
    {
        foreach ($rows as $rowId => $row) {
            foreach ($row['fields'] as $fieldId => $field) {
                $settings = array_merge([
                    'label' => $field->label,
                    'handle' => $field->handle,
                    'instructions' => $field->getSettings()['instructions'] ?? [],
                    'required' => $field->required,
                ], $field->settings);

                ArrayHelper::remove($settings, 'formId');
                ArrayHelper::remove($settings, 'nestedLayoutId');

                $pageData['rows'][$rowId]['fields'][$fieldId] = [
                    'type' => get_class($field),
                    'settings' => $settings,
                ];

                // Handle nested fields
                if ($field instanceof ParentFieldInterface) {
                    self::getFieldInfoForExport($field->getRows(), $pageData['rows'][$rowId]['fields'][$fieldId]['settings']);
                }
            }
        }
    }

    private static function prepFieldsForImport(array &$pages): void
    {
        foreach ($pages as $pageKey => &$page) {
            // Handle Formie v2 exports
            unset($page['userCondition'], $page['elementCondition']);

            if (isset($page['rows'])) {
                foreach ($page['rows'] as $rowKey => &$row) {
                    if (isset($row['fields'])) {
                        foreach ($row['fields'] as $fieldKey => &$field) {
                            $type = $field['type'] ?? '';

                            // Handle Formie v2 exports
                            unset($field['settings']['isNested']);

                            if (isset($field['label'])) {
                                $field['settings']['label'] = $field['label'];
                            }

                            if (isset($field['handle'])) {
                                $field['settings']['handle'] = $field['handle'];
                            }

                            // This will throw an error for Commerce, where the extended class doesn't exist.
                            // Which unfortunately means we can't use `class_exists()` because it's the extended
                            // class that doesn't exist, and that can't be caught for some reason.
                            if (in_array($type, [fields\Products::class, fields\Variants::class]) && !Plugin::isPluginInstalledAndEnabled('commerce')) {
                                unset($row['fields'][$fieldKey]);
                            } else if (!class_exists($type)) {
                                // Check if the class doesn't exist
                                unset($row['fields'][$fieldKey]);
                            }

                            // Check for nested fields
                            // Handle Formie v2 exports
                            $nestedRows = $field['rows'] ?? $field['settings']['rows'] ?? [];

                            if ($nestedRows) {
                                // Create a new variable, so we can use our recursive function
                                $nestedPages = [['rows' => $nestedRows]];

                                self::prepFieldsForImport($nestedPages);

                                $field['settings']['rows'] = $nestedPages[0]['rows'];
                            }
                        }
                    }
                }

                // Cleanup any isolated fields
                $page['rows'] = array_filter($page['rows']);
            }
        }
    }

    private static function buildFieldMap(array $fields, string $prefix = ''): array
    {
        $fieldMap = [];

        foreach ($fields as $field) {
            $key = $prefix ? "$prefix.{$field->handle}" : $field->handle;
            $fieldMap[$key] = $field;

            // Check for nested fields
            if ($field instanceof ParentFieldInterface) {
                $nestedFields = $field->getFields();
                $fieldMap = array_merge($fieldMap, self::buildFieldMap($nestedFields, $key));
            }
        }

        return $fieldMap;
    }

    private static function updateFieldIdsInImport(array &$data, array $existingFields, string $prefix = ''): void
    {
        if (isset($data['rows'])) {
            foreach ($data['rows'] as &$row) {
                if (isset($row['fields'])) {
                    foreach ($row['fields'] as &$field) {
                        // Support legacy handling
                        $handle = $field['handle'] ?? $field['settings']['handle'] ?? null;
                        $rows = $field['rows'] ?? $field['settings']['rows'] ?? null;

                        $key = $prefix ? "$prefix.{$handle}" : $handle;

                        if (isset($existingFields[$key])) {
                            $field['id'] = $existingFields[$key]->id;
                        }

                        // Recursively handle nested fields
                        if ($rows && is_array($rows)) {
                            self::updateFieldIdsInImport($field['settings'], $existingFields, $key);
                        }
                    }
                }
            }
        }
    }

    private static function _exportSiteOverrides(Form $form): array
    {
        $siteOverridesService = Formie::$plugin->getFormSiteOverrides();

        if (!$siteOverridesService->isEnabled() || !$form->id) {
            return [];
        }

        $sourceSite = Craft::$app->getSites()->getSiteById($siteOverridesService->getSourceSiteId($form));

        if (!$sourceSite) {
            return [];
        }

        $export = [
            'sourceSiteHandle' => $sourceSite->handle,
        ];

        $formOverridesBySiteId = $siteOverridesService->getAllOverrides((int)$form->id);
        $fieldOverridesBySiteId = Formie::$plugin->getFieldSiteOverrides()->getAllForForm($form);
        $pageKeyMap = self::_buildPageExportKeyMap($form);
        $notificationKeyMap = self::_buildNotificationExportKeyMap($form);
        $fieldReferenceMap = self::_buildFieldReferenceMap($form);

        $exportedFormOverrides = [];
        $exportedFieldOverrides = [];

        foreach ($formOverridesBySiteId as $siteId => $overrides) {
            $site = Craft::$app->getSites()->getSiteById((int)$siteId);

            if (!$site) {
                continue;
            }

            $exportedFormOverrides[$site->handle] = self::_remapFormOverrideKeysForExport(
                $overrides,
                $pageKeyMap,
                $notificationKeyMap,
            );
        }

        foreach ($fieldOverridesBySiteId as $siteId => $fieldOverrides) {
            $site = Craft::$app->getSites()->getSiteById((int)$siteId);

            if (!$site) {
                continue;
            }

            $exportedFieldOverrides[$site->handle] = self::_remapFieldOverrideKeysForExport(
                $fieldOverrides,
                $fieldReferenceMap,
            );
        }

        if ($exportedFormOverrides !== []) {
            $export['siteOverrides'] = $exportedFormOverrides;
        }

        if ($exportedFieldOverrides !== []) {
            $export['fieldSiteOverrides'] = $exportedFieldOverrides;
        }

        return $export;
    }

    private static function _importSiteOverrides(Form $form, ?array $siteOverrides, ?array $fieldSiteOverrides): void
    {
        if (!$form->id) {
            return;
        }

        $siteOverridesService = Formie::$plugin->getFormSiteOverrides();

        if (!$siteOverridesService->isEnabled()) {
            return;
        }

        $pageKeyMap = self::_buildPageImportKeyMap($form);
        $fieldReferenceMap = self::_buildFieldReferenceMap($form);

        if (is_array($siteOverrides)) {
            foreach ($siteOverrides as $siteHandle => $overrides) {
                if (!is_array($overrides)) {
                    continue;
                }

                $siteId = self::_resolveSiteIdByHandle((string)$siteHandle);

                if (!$siteId || $siteOverridesService->isSourceSiteForForm((int)$form->id, $siteId)) {
                    continue;
                }

                $payload = self::_remapFormOverrideKeysForImport($overrides, $pageKeyMap);

                if ($payload === []) {
                    $siteOverridesService->deleteOverrides((int)$form->id, $siteId);
                    continue;
                }

                $siteOverridesService->saveOverrides((int)$form->id, $siteId, $payload);
            }
        }

        if (is_array($fieldSiteOverrides)) {
            foreach ($fieldSiteOverrides as $siteHandle => $fieldOverrides) {
                if (!is_array($fieldOverrides)) {
                    continue;
                }

                $siteId = self::_resolveSiteIdByHandle((string)$siteHandle);

                if (!$siteId || $siteOverridesService->isSourceSiteForForm((int)$form->id, $siteId)) {
                    continue;
                }

                $payload = self::_remapFieldOverrideKeysForImport($fieldOverrides, $fieldReferenceMap);

                if ($payload === []) {
                    continue;
                }

                Formie::$plugin->getFieldSiteOverrides()->saveOverrides($siteId, $payload);
            }
        }
    }

    private static function _resolveSiteIdByHandle(string $handle): ?int
    {
        $handle = trim($handle);

        if ($handle === '') {
            return null;
        }

        $site = Craft::$app->getSites()->getSiteByHandle($handle);

        return $site ? (int)$site->id : null;
    }

    private static function _buildFieldReferenceMap(Form $form): array
    {
        $map = [];

        foreach ($form->getFields() as $field) {
            if ($field instanceof FieldInterface) {
                self::_collectFieldReferenceMap($field, $map);
            }
        }

        return $map;
    }

    private static function _collectFieldReferenceMap(FieldInterface $field, array &$map): void
    {
        $reference = trim((string)$field->reference);
        $fieldId = (int)($field->fieldId ?: 0);

        if ($reference !== '' && $fieldId) {
            $map[$reference] = $fieldId;
        }

        if (!$field instanceof ParentFieldInterface) {
            return;
        }

        foreach ($field->getFieldLayout()->getPages() as $page) {
            foreach ($page->getRows() as $row) {
                foreach ($row->getFields() as $nestedField) {
                    if ($nestedField instanceof FieldInterface) {
                        self::_collectFieldReferenceMap($nestedField, $map);
                    }
                }
            }
        }
    }

    private static function _buildPageExportKeyMap(Form $form): array
    {
        $map = [];

        foreach ($form->getPages() as $page) {
            $handle = trim((string)($page->getHandle() ?? ''));

            if ($handle === '') {
                continue;
            }

            $uid = trim((string)$page->uid);
            $id = trim((string)$page->id);

            if ($uid !== '') {
                $map[$uid] = $handle;
            }

            if ($id !== '') {
                $map[$id] = $handle;
            }

            $map[$handle] = $handle;
        }

        return $map;
    }

    private static function _buildPageImportKeyMap(Form $form): array
    {
        $map = [];

        foreach ($form->getPages() as $page) {
            $handle = trim((string)($page->getHandle() ?? ''));
            $uid = trim((string)$page->uid);

            if ($handle !== '' && $uid !== '') {
                $map[$handle] = $uid;
            }
        }

        return $map;
    }

    private static function _buildNotificationExportKeyMap(Form $form): array
    {
        $map = [];

        foreach ($form->getNotifications() as $notification) {
            $handle = trim((string)($notification->handle ?? ''));
            $uid = trim((string)$notification->uid);

            if ($handle === '') {
                continue;
            }

            $map[$handle] = $handle;

            if ($uid !== '') {
                $map[$uid] = $handle;
            }
        }

        return $map;
    }

    private static function _remapFormOverrideKeysForExport(array $overrides, array $pageKeyMap, array $notificationKeyMap): array
    {
        if (isset($overrides['pages']) && is_array($overrides['pages'])) {
            $overrides['pages'] = self::_remapKeyedOverrideSection($overrides['pages'], $pageKeyMap);
        }

        if (isset($overrides['notifications']) && is_array($overrides['notifications'])) {
            $overrides['notifications'] = self::_remapKeyedOverrideSection($overrides['notifications'], $notificationKeyMap);
        }

        return $overrides;
    }

    private static function _remapFormOverrideKeysForImport(array $overrides, array $pageKeyMap): array
    {
        if (isset($overrides['pages']) && is_array($overrides['pages'])) {
            $overrides['pages'] = self::_remapKeyedOverrideSection($overrides['pages'], $pageKeyMap);
        }

        return Formie::$plugin->getFormSiteOverrides()->normalizeOverrides($overrides);
    }

    private static function _remapFieldOverrideKeysForExport(array $fieldOverrides, array $fieldReferenceMap): array
    {
        $referenceByFieldId = array_flip($fieldReferenceMap);
        $export = [];

        foreach ($fieldOverrides as $fieldId => $override) {
            if (!is_array($override)) {
                continue;
            }

            $reference = $referenceByFieldId[(int)$fieldId] ?? null;

            if (!$reference) {
                continue;
            }

            $export[$reference] = $override;
        }

        return $export;
    }

    private static function _remapFieldOverrideKeysForImport(array $fieldOverrides, array $fieldReferenceMap): array
    {
        $import = [];

        foreach ($fieldOverrides as $reference => $override) {
            if (!is_array($override)) {
                continue;
            }

            $fieldId = $fieldReferenceMap[(string)$reference] ?? null;

            if (!$fieldId) {
                continue;
            }

            $import[(int)$fieldId] = $override;
        }

        return $import;
    }

    private static function _remapKeyedOverrideSection(array $section, array $keyMap): array
    {
        $remapped = [];

        foreach ($section as $key => $value) {
            $stableKey = $keyMap[(string)$key] ?? (string)$key;

            if (isset($remapped[$stableKey]) && is_array($remapped[$stableKey]) && is_array($value)) {
                $remapped[$stableKey] = array_replace($remapped[$stableKey], $value);
            } else {
                $remapped[$stableKey] = $value;
            }
        }

        return $remapped;
    }
}
