<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\fields;
use verbb\formie\fields\Recipients;
use verbb\formie\models\EmailTemplate;
use verbb\formie\models\FormGroup;
use verbb\formie\models\FormSettings;
use verbb\formie\models\FormStatus;
use verbb\formie\models\FormTemplate;
use verbb\formie\models\ImportResult;
use verbb\formie\models\LayoutSaveContext;
use verbb\formie\models\MissingIntegration;
use verbb\formie\models\Notification;
use verbb\formie\models\PdfTemplate;
use verbb\formie\models\SubmissionStatus;
use verbb\formie\records\EmailTemplate as EmailTemplateRecord;
use verbb\formie\records\Form as FormRecord;
use verbb\formie\records\FormTemplate as FormTemplateRecord;
use verbb\formie\records\Notification as NotificationRecord;
use verbb\formie\records\PdfTemplate as PdfTemplateRecord;

use Craft;
use craft\db\Query;
use craft\elements\Entry;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\validators\HandleValidator;

use yii\base\Exception;

use Throwable;

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
            foreach (['id', 'dateDeleted', 'dateCreated', 'dateUpdated'] as $key) {
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

        $data['pages'] = (new FormSerializer())->serializeLayout($formElement->getFormLayout());

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
        $data['schemaVersion'] = FormSerializer::SCHEMA_VERSION;
        $data['formieVersion'] = Formie::$plugin->getVersion();
        $data['dependencies'] = self::getDependencyDocuments($data);

        foreach (['group' => $formElement->getGroup(), 'submissionStatus' => $formElement->getDefaultStatus(), 'formStatus' => $formElement->getFormStatusModel()] as $kind => $resource) {
            if ($resource) {
                $data['dependencies'][] = ['kind' => $kind, 'config' => array_intersect_key($resource->getAttributes(), array_flip(['uid', 'name', 'handle', 'description', 'color']))];
            }
        }
        unset($data['groupId'], $data['defaultStatusId'], $data['formStatusId']);

        return $data;
    }

    public static function createFormFromImport(array $data, ?Form $form = null, ?FormSerializer $serializer = null): Form
    {
        $serializer ??= new FormSerializer();
        $data = self::_applyDependencyDocuments($data);
        $data = $serializer->prepareImport($data, $form);

        if (!$form) {
            $form = new Form();
        }

        // Grab all the extra bits from the export that need to be handles separately
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
        $form->setAttributes(array_intersect_key($data, array_flip(FormDocumentSchema::FORM)), false);

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
        $settings = array_intersect_key($settings ?? [], array_flip(FormDocumentSchema::FORM_SETTINGS));
        $settings['integrations'] = Formie::$plugin->getIntegrations()->filterAllIntegrationFormSettings($settings['integrations'] ?? [], true);
        $form->settings->setAttributes($settings, false);

        // Check if there is an entry selected as the redirect action. If not found, will cause a fatal error
        if ($form->redirectEntryId) {
            $entry = Entry::find()->id($form->redirectEntryId)->one();

            if (!$entry) {
                $form->redirectEntryId = null;
            }
        }

        // Ensure that the default status exists, just in case there's a project config mismatch
        if ($form->defaultStatusId) {
            $status = Formie::$plugin->getSubmissionStatuses()->getStatusById($form->defaultStatusId);

            if (!$status) {
                $form->defaultStatusId = Formie::$plugin->getSubmissionStatuses()->getDefaultStatus()->id;
            }
        }

        // Handle field layout and pages
        $form->getFormLayout()->setPages($pages);

        // Handle for template
        if ($formTemplate) {
            $template = self::_resolveResource('form', $formTemplate);

            if (!$template) {
                $template = new FormTemplate();
                $template->setAttributes(array_intersect_key($formTemplate, array_flip(FormDocumentSchema::FORM_TEMPLATE)), false);
            }

            $form->setTemplate($template);
        }

        if ($notifications !== null) {
            $allNotifications = [];

            foreach ($notifications as $notificationData) {
                $emailTemplate = ArrayHelper::remove($notificationData, 'emailTemplate');
                $pdfTemplate = ArrayHelper::remove($notificationData, 'pdfTemplate');

                if (isset($notificationData['handle']) && !preg_match('/^' . HandleValidator::$handlePattern . '$/', $notificationData['handle'])) {
                    // Legacy exports may suffix handles with invalid characters (e.g. ".1").
                    $notificationData['handle'] = StringHelper::toHandle($notificationData['name'] ?? $notificationData['handle']);
                }

                // Find or create the notification, based on the form and notification handle
                $notification = Formie::$plugin->getNotifications()->getFormNotificationByHandle($form, $notificationData['handle']) ?? new Notification();

                $notification->setAttributes(array_intersect_key($notificationData, array_flip(FormDocumentSchema::NOTIFICATION)), false);

                if ($emailTemplate) {
                    $template = self::_resolveResource('email', $emailTemplate);

                    if (!$template) {
                        $template = new EmailTemplate();
                        $template->setAttributes(array_intersect_key($emailTemplate, array_flip(FormDocumentSchema::EMAIL_TEMPLATE)), false);
                    }

                    $notification->setTemplate($template);
                }

                if ($pdfTemplate) {
                    $template = self::_resolveResource('pdf', $pdfTemplate);

                    if (!$template) {
                        $template = new PdfTemplate();
                        $template->setAttributes(array_intersect_key($pdfTemplate, array_flip(FormDocumentSchema::PDF_TEMPLATE)), false);
                    }

                    $notification->setPdfTemplate($template);
                }

                $allNotifications[] = $notification;
            }

            $form->setNotifications($allNotifications);
        }

        return $form;
    }

    public static function createFromImport(array $data): ImportResult
    {
        return self::_import($data, null);
    }

    public static function updateFromImport(array $data, Form $form): ImportResult
    {
        return self::_import($data, $form);
    }

    public static function importFormFromJson($json, $formAction = 'update'): Form
    {
        $json = $json[0] ?? $json;
        $form = $formAction === 'update' ? Formie::$plugin->getForms()->getFormByHandle($json['handle'] ?? '') : null;

        return ($form ? self::updateFromImport($json, $form) : self::createFromImport($json))->form;
    }

    public static function getDependencyDocuments(array $data): array
    {
        if (array_key_exists('dependencies', $data)) {
            if (!is_array($data['dependencies'])) {
                throw new Exception('Invalid form dependency manifest.');
            }

            foreach ($data['dependencies'] as $dependency) {
                if (!is_array($dependency) || !in_array($dependency['kind'] ?? null, ['form', 'email', 'pdf', 'group', 'submissionStatus', 'formStatus'], true) || !is_array($dependency['config'] ?? null)) {
                    throw new Exception('Invalid form dependency.');
                }
            }

            return $data['dependencies'];
        }
        $dependencies = [];

        if (!empty($data['formTemplate'])) {
            $dependencies[] = ['kind' => 'form', 'config' => $data['formTemplate']];
        }

        foreach ($data['notifications'] ?? [] as $notification) {
            foreach (['email', 'pdf'] as $kind) {
                if (!empty($notification[$kind . 'Template'])) {
                    $dependencies[] = ['kind' => $kind, 'config' => $notification[$kind . 'Template']];
                }
            }
        }

        return $dependencies;
    }

    public static function planImport(array $data, ?Form $form = null): array
    {
        $requested = $data;
        $data = self::_applyDependencyDocuments($data);
        $serializer = new FormSerializer();
        $serializer->prepareImport($data, $form);
        $resources = [];

        foreach (self::getDependencyDocuments($data) as $dependency) {
            $config = $dependency['config'];
            $template = self::_resolveResource($dependency['kind'], $config);
            $resources[] = [
                'kind' => $dependency['kind'],
                'handle' => $config['handle'] ?? '',
                'action' => !$template ? 'create' : (($config['uid'] ?? null) === $template->uid ? 'reuseUid' : 'reuseHandle'),
            ];
        }
        $warnings = $serializer->warnings;

        if (!empty($requested['formTemplateUid']) && empty($data['formTemplate'])) {
            $warnings[] = 'Unavailable form template dependency: ' . $requested['formTemplateUid'];
        }

        foreach ($requested['notifications'] ?? [] as $key => $notification) {
            foreach (['email', 'pdf'] as $kind) {
                if (!empty($notification[$kind . 'TemplateUid']) && empty($data['notifications'][$key][$kind . 'Template'])) {
                    $warnings[] = 'Unavailable ' . $kind . ' template dependency: ' . $notification[$kind . 'TemplateUid'];
                }
            }
        }
        $settings = Json::decodeIfJson($data['settings'] ?? []) ?: [];

        foreach ($settings['integrations'] ?? [] as $handle => $config) {
            $integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle($handle)
                ?? Formie::$plugin->getIntegrations()->getCaptchaByHandle($handle);

            if (!$integration || $integration instanceof MissingIntegration) {
                $warnings[] = "Unavailable integration: $handle. Settings retained.";
                $resources[] = ['kind' => 'integration', 'handle' => $handle, 'action' => 'missing'];
            }
        }

        foreach ($resources as $resource) {
            if ($resource['action'] === 'reuseHandle') {
                $warnings[] = 'Legacy handle fallback: ' . $resource['kind'] . ' resource ' . $resource['handle'];
            }
        }

        foreach (['groupId', 'defaultStatusId', 'formStatusId'] as $key) {
            if (!empty($data[$key])) {
                $warnings[] = "Legacy $key is environment-specific; the current form or destination default is retained.";
            }
        }

        $sites = array_unique(array_filter([
            $data['sourceSiteHandle'] ?? null,
            ...array_keys($data['siteOverrides'] ?? []),
            ...array_keys($data['fieldSiteOverrides'] ?? []),
        ]));

        foreach ($sites as $handle) {
            if (!self::_resolveSiteIdByHandle($handle)) {
                $warnings[] = "Unavailable site: $handle. Site overrides cannot be applied.";
                $resources[] = ['kind' => 'site', 'handle' => $handle, 'action' => 'missing'];
            }
        }

        if ($form && isset($data['notifications'])) {
            $incomingHandles = array_column($data['notifications'] ?? [], 'handle');
            $serializer->changes['removedNotifications'] = array_values(array_filter(
                array_map(fn($notification) => $notification->handle, $form->getNotifications()),
                fn($handle) => !in_array($handle, $incomingHandles, true),
            ));
        }

        return ['warnings' => $warnings, 'missingTypes' => array_values(array_unique($serializer->missingTypes)), 'dependencies' => $resources, 'changes' => $serializer->changes];
    }

    private static function _applyDependencyDocuments(array $data): array
    {
        $dependencies = self::getDependencyDocuments($data);
        $resolve = static function(string $kind, mixed $reference) use ($dependencies): ?array {
            foreach ($dependencies as $dependency) {
                if ($dependency['kind'] !== $kind) {
                    continue;
                }
                $config = $dependency['config'];
                $key = is_array($reference) ? ($reference['uid'] ?? $reference['handle'] ?? null) : $reference;

                if ($key === null || $key === ($config['uid'] ?? null) || $key === ($config['handle'] ?? null)) {
                    return $config;
                }
            }

            return is_array($reference) ? $reference : null;
        };
        $data['formTemplate'] = $resolve('form', $data['formTemplate'] ?? $data['formTemplateUid'] ?? null);

        foreach ($data['notifications'] ?? [] as $key => $notification) {
            foreach (['email', 'pdf'] as $kind) {
                $reference = $notification[$kind . 'Template'] ?? $notification[$kind . 'TemplateUid'] ?? null;

                if ($reference !== null) {
                    $data['notifications'][$key][$kind . 'Template'] = $resolve($kind, $reference);
                }
            }
        }

        return $data;
    }

    private static function _resolveResource(string $kind, array $config): mixed
    {
        $service = self::_resourceService($kind);
        $method = match ($kind) {
            'group' => 'getGroupBy', 'submissionStatus', 'formStatus' => 'getStatusBy', default => 'getTemplateBy'
        };

        return (!empty($config['uid']) ? $service->{$method . 'Uid'}($config['uid']) : null)
            ?? (!empty($config['handle']) ? $service->{$method . 'Handle'}($config['handle']) : null);
    }

    private static function _resourceService(string $kind): mixed
    {
        return match ($kind) {
            'form' => Formie::$plugin->getFormTemplates(),
            'email' => Formie::$plugin->getEmailTemplates(),
            'pdf' => Formie::$plugin->getPdfTemplates(),
            'group' => Formie::$plugin->getFormGroups(),
            'submissionStatus' => Formie::$plugin->getSubmissionStatuses(),
            'formStatus' => Formie::$plugin->getFormStatuses(),
        };
    }

    private static function _applyPortableResources(Form $form, array $data): void
    {
        foreach (self::getDependencyDocuments($data) as $dependency) {
            $kind = $dependency['kind'];
            $property = match ($kind) {
                'group' => 'groupId', 'submissionStatus' => 'defaultStatusId', 'formStatus' => 'formStatusId', default => null
            };

            if (!$property) {
                continue;
            }
            $resource = self::_resolveResource($kind, $dependency['config']);

            if (!$resource) {
                $class = match ($kind) {
                    'group' => FormGroup::class, 'submissionStatus' => SubmissionStatus::class, 'formStatus' => FormStatus::class
                };
                $keys = $kind === 'group' ? ['name', 'handle'] : ['name', 'handle', 'description', 'color'];
                $resource = new $class(array_intersect_key($dependency['config'], array_flip($keys)));
                $save = $kind === 'group' ? 'saveGroup' : 'saveStatus';

                if (!self::_resourceService($kind)->$save($resource)) {
                    throw new Exception('Unable to import dependency: ' . Json::encode($resource->getErrors()));
                }
            }
            $form->$property = $resource->id;
        }
    }

    private static function _import(array $data, ?Form $existing): ImportResult
    {
        $plan = self::planImport($data, $existing);
        $serializer = new FormSerializer();
        $projectConfig = Craft::$app->getProjectConfig();
        $writeYaml = $projectConfig->writeYamlAutomatically;

        if ($writeYaml) {
            $projectConfig->flush();
        }
        // Dependencies share the database transaction; defer their YAML writes until it commits.
        $projectConfig->writeYamlAutomatically = false;
        $transaction = Craft::$app->getDb()->beginTransaction();
        $previousRelaxation = Recipients::$relaxLegacyOptionValidation;

        try {
            if (!$existing) {
                $handles = (new Query())->select('handle')->from(Table::FORMIE_FORMS)->column();
                $data['handle'] = HandleHelper::getUniqueHandle($handles, $data['handle'] ?? 'importedForm');
            }
            $form = self::createFormFromImport($data, $existing, $serializer);
            self::_applyPortableResources($form, $data);
            $form->layoutSaveContext = new LayoutSaveContext($existing ? 'updateImport' : 'createImport');
            $form->layoutSaveContext->trusted = false;
            $form->layoutSaveContext->remaps = $serializer->remaps;
            Recipients::$relaxLegacyOptionValidation = true;

            if (!Craft::$app->getElements()->saveElement($form)) {
                throw new Exception('Unable to import form: ' . Json::encode($form->getErrors()));
            }
            $siteOverrides = $data['siteOverrides'] ?? null;
            $fieldOverrides = $data['fieldSiteOverrides'] ?? null;
            $serializer->remap($siteOverrides);
            $serializer->remap($fieldOverrides);
            self::_importSiteOverrides($form, $siteOverrides, $fieldOverrides);
            $projectConfig->saveModifiedConfigData();
            $transaction->commit();

            return new ImportResult($form, $plan['warnings'], $plan['missingTypes'], $plan['dependencies'], array_merge($plan['changes'], $serializer->changes), $serializer->remaps);
        } catch (Throwable $e) {
            $transaction->rollBack();
            $projectConfig->reset();

            foreach (['form', 'email', 'pdf', 'group', 'submissionStatus', 'formStatus'] as $kind) {
                self::_resourceService($kind)->invalidateCaches();
            }
            Formie::$plugin->getForms()->invalidateFormCaches();
            Formie::$plugin->getFields()->resetFieldRegistryCache();
            throw $e;
        } finally {
            $projectConfig->writeYamlAutomatically = $writeYaml;

            if ($writeYaml) {
                $projectConfig->writeYamlFiles();
            }
            Recipients::$relaxLegacyOptionValidation = $previousRelaxation;
        }
    }

    private static function _buildFieldMap(array $fields, string $prefix = ''): array
    {
        $fieldMap = [];

        foreach ($fields as $field) {
            $key = $prefix ? "$prefix.{$field->handle}" : $field->handle;
            $fieldMap[$key] = $field;

            // Check for nested fields
            if ($field instanceof ParentFieldInterface) {
                $nestedFields = $field->getFields();
                $fieldMap = array_merge($fieldMap, self::_buildFieldMap($nestedFields, $key));
            }
        }

        return $fieldMap;
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

    private static function _importSiteOverrides(Form $form, ?array $siteOverrides, ?array $fieldSiteOverrides, array $importReferences = []): void
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
        $importedFields = self::_buildFieldMap($form->getFields());
        $fieldInstanceReferenceMap = [];

        foreach (array_keys($fieldReferenceMap) as $reference) {
            $fieldInstanceReferenceMap[$reference] = $reference;
        }

        foreach ($importReferences as $reference => $path) {
            if (isset($importedFields[$path])) {
                $fieldReferenceMap[$reference] = (int)$importedFields[$path]->definitionId;
                $fieldInstanceReferenceMap[$reference] = (string)$importedFields[$path]->reference;
            }
        }

        if (is_array($siteOverrides)) {
            foreach ($siteOverrides as $siteHandle => $overrides) {
                if (!is_array($overrides)) {
                    continue;
                }

                $siteId = self::_resolveSiteIdByHandle((string)$siteHandle);

                if (!$siteId || $siteOverridesService->isSourceSiteForForm((int)$form->id, $siteId)) {
                    continue;
                }

                $payload = self::_remapFormOverrideKeysForImport($overrides, $pageKeyMap, $fieldInstanceReferenceMap);

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
        $fieldId = (int)($field->definitionId ?: 0);

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

    private static function _remapFormOverrideKeysForImport(array $overrides, array $pageKeyMap, array $fieldInstanceReferenceMap): array
    {
        if (isset($overrides['pages']) && is_array($overrides['pages'])) {
            $overrides['pages'] = self::_remapKeyedOverrideSection($overrides['pages'], $pageKeyMap);
        }

        if (isset($overrides['fieldInstanceOverrides']) && is_array($overrides['fieldInstanceOverrides'])) {
            $overrides['fieldInstanceOverrides'] = self::_remapKeyedOverrideSection(
                $overrides['fieldInstanceOverrides'],
                $fieldInstanceReferenceMap,
            );
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
