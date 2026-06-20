<?php
namespace verbb\formie\services;

use verbb\formie\cache\FormLookupCache;
use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Payment as PaymentIntegration;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\HandleHelper;
use verbb\formie\helpers\References;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\Variables;
use verbb\formie\models\FormLayout;
use verbb\formie\models\FormSettings;
use verbb\formie\models\FormTemplate;
use verbb\formie\records\Form as FormRecord;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\base\NestedElementInterface;
use craft\db\Query;
use craft\helpers\Console;
use craft\helpers\Db;
use craft\helpers\DateTimeHelper;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;

use Throwable;

class Forms extends Component
{
    // Properties
    // =========================================================================

    private ?FormLookupCache $_formLookupCache = null;


    // Public Methods
    // =========================================================================

    public function getFormById(int $id, int $siteId = null): ?Form
    {
        if (!$id) {
            return null;
        }

        $cacheKey = $this->_getFormLookupKey($id, $siteId);
        $cache = $this->_getFormLookupCache();

        if (array_key_exists($cacheKey, $cache->formsById)) {
            return $this->_resolveCachedForm($cache->formsById[$cacheKey], $siteId);
        }

        $form = Form::find()->id($id)->siteId($siteId)->one();

        if (!$form) {
            return $cache->formsById[$cacheKey] = null;
        }

        return $this->_cacheFormLookup($form, $siteId);
    }

    public function getFormByHandle(string $handle, int $siteId = null): ?Form
    {
        if ($handle === '') {
            return null;
        }

        $normalizedHandle = strtolower($handle);
        $cacheKey = $this->_getFormLookupKey($normalizedHandle, $siteId);
        $cache = $this->_getFormLookupCache();

        if (array_key_exists($cacheKey, $cache->formsByHandle)) {
            return $this->_resolveCachedForm($cache->formsByHandle[$cacheKey], $siteId);
        }

        $form = Form::find()->handle($handle)->siteId($siteId)->one();

        if (!$form) {
            return $cache->formsByHandle[$cacheKey] = null;
        }

        return $this->_cacheFormLookup($form, $siteId);
    }

    public function getFormByUid(string $uid, int $siteId = null): ?Form
    {
        if ($uid === '') {
            return null;
        }

        $normalizedUid = strtolower($uid);
        $cacheKey = $this->_getFormLookupKey($normalizedUid, $siteId);
        $cache = $this->_getFormLookupCache();

        if (array_key_exists($cacheKey, $cache->formsByUid)) {
            return $this->_resolveCachedForm($cache->formsByUid[$cacheKey], $siteId);
        }

        $form = Form::find()->uid($uid)->siteId($siteId)->one();

        if (!$form) {
            return $cache->formsByUid[$cacheKey] = null;
        }

        return $this->_cacheFormLookup($form, $siteId);
    }

    public function getFormByLayoutId(int $layoutId, int $siteId = null): ?Form
    {
        if (!$layoutId) {
            return null;
        }

        $cacheKey = $this->_getFormLayoutCacheKey($layoutId, $siteId);
        $cache = $this->_getFormLookupCache();

        if (array_key_exists($cacheKey, $cache->formsByLayoutId)) {
            return $cache->formsByLayoutId[$cacheKey];
        }

        $formId = FormRecord::find()
            ->select(['id'])
            ->where(['layoutId' => $layoutId])
            ->scalar();

        if ($formId) {
            $form = $this->getFormById((int)$formId, $siteId);

            if ($form) {
                return $cache->formsByLayoutId[$cacheKey] = $form;
            }
        }

        foreach ($this->getAllForms() as $form) {
            $formLayoutId = (int)($form->layoutId ?? 0);

            if (!$formLayoutId) {
                $formLayoutId = (int)($form->getFormLayout()->id ?? 0);
            }

            if ($formLayoutId !== $layoutId) {
                continue;
            }

            if ($siteId !== null && (int)$form->siteId !== $siteId) {
                continue;
            }

            return $cache->formsByLayoutId[$cacheKey] = $form;
        }

        return $cache->formsByLayoutId[$cacheKey] = null;
    }

    public function getAllForms(): array
    {
        $cache = $this->_getFormLookupCache();

        if ($cache->allForms !== null) {
            return $cache->allForms;
        }

        if ($cache->allFormsWithLayouts !== null) {
            return $cache->allForms = $cache->allFormsWithLayouts;
        }

        $forms = Form::find()->all();
        $this->_primeForms($forms);

        return $cache->allForms = $forms;
    }

    public function getAllFormsWithLayouts(): array
    {
        $cache = $this->_getFormLookupCache();

        if ($cache->allFormsWithLayouts !== null) {
            return $cache->allFormsWithLayouts;
        }

        $forms = $cache->allForms ?? Form::find()->all();
        $this->_primeForms($forms);
        $cache->allForms = $forms;

        // This variant exists for callers that need the full form + layout graph. Hydrating layouts in
        // one pass keeps repeated form->getFormLayout()/getFields() access on a shared request-local graph
        // instead of lazy-loading each layout separately.
        $layoutIds = array_values(array_unique(array_filter(array_map(static fn(Form $form): int => (int)$form->layoutId, $forms))));
        $layoutsById = $layoutIds ? Formie::$plugin->getFields()->getLayoutsByIds($layoutIds) : [];

        foreach ($forms as $form) {
            $layoutId = (int)$form->layoutId;

            if ($layoutId && isset($layoutsById[$layoutId])) {
                // Attach the hydrated layout to the element so later callers reuse the shared
                // request-local object graph instead of lazy-loading the same layout again.
                $form->setFormLayout($layoutsById[$layoutId]);
            }
        }

        return $cache->allFormsWithLayouts = $forms;
    }

    public function invalidateFormCaches(): void
    {
        $this->_formLookupCache?->reset();
    }

    public function buildFormFromPost(): Form
    {
        $request = Craft::$app->getRequest();
        $formId = $request->getParam('id');
        $siteId = $request->getParam('siteId');

        if ($formId) {
            $form = Craft::$app->getElements()->getElementById($formId, Form::class, $siteId);

            if (!$form) {
                throw new Exception("No form found for ID: $formId");
            }

            return $this->_populateFormFromPost($form);
        }

        return $this->_populateFormFromPost(new Form());
    }

    public function buildStencilFormFromPost(): Form
    {
        return $this->_populateFormFromPost(new Form(), applyDefaultStencil: false);
    }

    public function getFormBuilderVariables(Form $form, ?int $activeSiteId = null): array
    {
        $user = Craft::$app->getUser()->getIdentity();
        $permissions = Formie::$plugin->getPermissions();
        $siteOverrides = Formie::$plugin->getFormSiteOverrides();
        $activeSiteId ??= $siteOverrides->getActiveSiteId();
        $canonicalForm = $this->_getCanonicalFormForBuilder((int)$form->id) ?? $form;
        $canonicalNotifications = Formie::$plugin->getNotifications()->getFormNotifications($canonicalForm);
        $canonicalNotificationsConfig = Formie::$plugin->getNotifications()->getNotificationsConfig($canonicalNotifications);

        $activeSiteId = $siteOverrides->resolveBuilderActiveSiteId($canonicalForm, $activeSiteId);
        $canonicalData = [
            'id' => $canonicalForm->id,
            'uid' => $canonicalForm->uid,
            'title' => $siteOverrides->resolveCanonicalFormTitle($canonicalForm),
            'handle' => $canonicalForm->handle,
            'isStencil' => false,
            'layoutId' => $canonicalForm->layoutId,
            'templateId' => $canonicalForm->templateId,
            'groupId' => $canonicalForm->groupId,
            'submitActionEntry' =>  array_filter([
                array_filter([
                    'id' => $canonicalForm->submitActionEntryId,
                    'siteId' => $canonicalForm->submitActionEntrySiteId,
                ]),
            ]),
            'defaultStatusId' => $canonicalForm->defaultStatusId,
            'dataRetention' => $canonicalForm->dataRetention,
            'dataRetentionValue' => $canonicalForm->dataRetentionValue,
            'userDeletedAction' => $canonicalForm->userDeletedAction,
            'fileUploadsAction' => $canonicalForm->fileUploadsAction,
            'dateCreated' => $canonicalForm->dateCreated->format('Y-m-d H:i:s'),
            'dateUpdated' => $canonicalForm->dateUpdated->format('Y-m-d H:i:s'),
            'createdById' => $canonicalForm->createdById,
            'updatedById' => $canonicalForm->updatedById,
            'settings' => $canonicalForm->settings,
            'notifications' => $canonicalNotificationsConfig,
            'integrations' => Formie::$plugin->getIntegrations()->getIntegrationSummariesForForm(),
            'pages' => $canonicalForm->getFormLayout()->getFormBuilderConfig(),
        ];
        $displayData = $siteOverrides->applyToBuilderData($canonicalData, $activeSiteId);

        $viewSubmissionsUrl = null;
        $submissions = Submission::find()->formId($form->id)->limit(1)->exists();

        if ($submissions && $permissions->canViewSubmissions($user, $form)) {
            $viewSubmissionsUrl = UrlHelper::cpUrl('formie/submissions/' . $form->handle, [
                'source' => 'form:' . $form->id,
            ]);
        }

        $tabs = [
            [
                'handle' => 'fields',
                'label' => Craft::t('formie', 'Fields'),
                'content' => $form->defineFieldsSchema(),
                'props' => [
                    'padded' => false,
                ],
            ],
        ];

        if ($permissions->canViewSubmissions($user, $form)) {
            $tabs[] = [
                'handle' => 'results',
                'label' => Craft::t('formie', 'Results'),
                'content' => $form->defineResultsSchema(),
                'if' => 'formBuilder.hasQuestionnaireFields',
            ];
        }

        if ($permissions->canShowFormBuilderTab($user, $form, 'formie-showFormAppearance')) {
            $tabs[] = [
                'handle' => 'appearance',
                'label' => Craft::t('formie', 'Appearance'),
                'content' => $form->defineFormBuilderAppearanceSchema(),
            ];
        }

        if ($permissions->canShowFormBuilderTab($user, $form, 'formie-showFormBehavior')) {
            $tabs[] = [
                'handle' => 'behaviour',
                'label' => Craft::t('formie', 'Behaviour'),
                'content' => $form->defineBehaviourSchema(),
            ];
        }

        if ($permissions->canShowFormBuilderTab($user, $form, 'formie-showNotifications')) {
            $tabs[] = [
                'handle' => 'notifications',
                'label' => Craft::t('formie', 'Email Notifications'),
                'content' => $form->defineNotificationsSchema(),
            ];
        }

        if ($permissions->canShowFormBuilderTab($user, $form, 'formie-showFormIntegrations')) {
            $tabs[] = [
                'handle' => 'integrations',
                'label' => Craft::t('formie', 'Integrations'),
                'content' => $form->defineIntegrationsSchema(),
                'props' => [
                    'padded' => false,
                ],
            ];
        }

        if ($permissions->canShowFormBuilderTab($user, $form, 'formie-showFormUsage')) {
            $tabs[] = [
                'handle' => 'usage',
                'label' => Craft::t('formie', 'Usage'),
                'content' => $form->defineUsageSchema(),
            ];
        }

        if ($permissions->canShowFormBuilderTab($user, $form, 'formie-showFormSettings')) {
            $tabs[] = [
                'handle' => 'settings',
                'label' => Craft::t('formie', 'Settings'),
                'content' => $form->defineFormBuilderSettingsSchema(),
            ];
        }

        $tabSchema = array_merge(...array_map(function($tab) {
            $content = $tab['content'] ?? [];
            if (!is_array($content)) {
                return [];
            }
            if (array_is_list($content)) {
                return $content;
            }
            return [$content];
        }, $tabs));

        $compiledSchema = SchemaHelper::compileSchema([
            [
                '$cmp' => 'FormBuilderTabs',
                'schema' => $tabSchema,
                'children' => [
                    [
                        '$cmp' => 'FormBuilderTabList',
                        'children' => array_map(function($tab) {
                            $node = [
                                '$cmp' => 'FormBuilderTabTrigger',
                                'props' => [
                                    'value' => $tab['handle'],
                                ],
                                'children' => $tab['label'],
                            ];

                            if (!empty($tab['if'])) {
                                $node['if'] = $tab['if'];
                            }

                            return $node;
                        }, $tabs),
                    ],
                    ...array_map(function($tab) {
                        $node = [
                            '$cmp' => 'FormBuilderTabContent',
                            'props' => array_merge([
                                'value' => $tab['handle'],
                            ], $tab['props'] ?? []),
                            'children' => $tab['content'],
                        ];

                        if (!empty($tab['if'])) {
                            $node['if'] = $tab['if'];
                        }

                        return $node;
                    }, $tabs),
                ],
            ],
        ]);

        return [
            'activeTab' => 'fields',
            'allowAdminChanges' => (bool)Craft::$app->getConfig()->getGeneral()->allowAdminChanges,
            'baseUrl' => $form->getCpEditUrl(),
            'tabLabels' => array_column($tabs, 'label', 'handle'),
            'paymentIntegrations' => $this->_getPaymentIntegrationMetadata(),
            'allowedSubmitMethods' => Formie::$plugin->getSettings()->allowedSubmitMethods,
            'templateFieldLayoutInfo' => $this->_getTemplateFieldLayoutInfo(),
            'fieldTypeGroups' => Formie::$plugin->getFields()->getFormBuilderFieldTypes([], $form),
            'hasSubmissions' => (bool)$submissions,
            'viewSubmissionsUrl' => $viewSubmissionsUrl,
            ...Variables::getFormBuilderVariableConfig(),
            'reservedHandles' => Formie::$plugin->getFields()->getReservedHandles(),
            'formHandles' => $form->getBuilderHandleNames(),
            'maxFormHandleLength' => HandleHelper::getMaxFormHandle(),
            'maxFieldHandleLength' => HandleHelper::getMaxFieldHandle(),
            'formMeta' => $form->getFormMetaDetails(),
            'canonicalData' => $canonicalData,
            'translatableProperties' => Formie::$plugin->getFormSiteOverrides()->getBuilderTranslatableConfig(),
            'multiSite' => $siteOverrides->getBuilderMultiSiteConfig($form, $activeSiteId),
            'data' => $displayData,
            'pageSettingsSchema' => $form->definePageSettingsSchema(),
            'pageButtonSettingsSchema' => $form->definePageButtonSettingsSchema(),
            'clientEventTemplates' => Formie::$plugin->getClientEventTemplates()->getBuilderConfig(),
            'schema' => $compiledSchema['schema'],
            'schemaIndex' => $compiledSchema,
        ];
    }

    public function getFormUsage(Form $form): array
    {
        $elements = [];
        $settings = Formie::$plugin->getSettings();
        $includeDrafts = $settings->includeDraftElementUsage;
        $includeRevisions = $settings->includeRevisionElementUsage;

        if (!$form) {
            return $elements;
        }

        $query = (new Query())
            ->select([
                'elements.id',
                'elements.type',
                'relations.fieldId',
                'relations.sourceSiteId AS siteId',
            ])
            ->from(['relations' => Table::RELATIONS])
            ->innerJoin(['elements' => Table::ELEMENTS], '[[elements.id]] = [[relations.sourceId]]')
            ->where(['relations.targetId' => $form->id]);

        if (!$includeDrafts) {
            $query->andWhere(['elements.draftId' => null]);
        }

        if (!$includeRevisions) {
            $query->andWhere(['elements.revisionId' => null]);
        }

        foreach ($query->all() as $info) {
            $elementId = (int)$info['id'];
            $elementType = $info['type'];
            $relationSiteId = $info['siteId'] !== null ? (int)$info['siteId'] : null;
            $fieldId = (int)$info['fieldId'];

            if (isset($this->_getFormLookupCache()->fieldsById[$fieldId])) {
                $field = $this->_getFormLookupCache()->fieldsById[$fieldId];
            } else {
                $field = Craft::$app->getFields()->getFieldById($fieldId);
                $this->_getFormLookupCache()->fieldsById[$fieldId] = $field;
            }

            foreach ($this->_resolveFormUsageSiteIds($elementId, $relationSiteId, $elementType) as $siteId) {
                $cacheKey = $elementId . '_' . $siteId;

                if (isset($this->_getFormLookupCache()->elementsByIdAndSite[$cacheKey])) {
                    $element = $this->_getFormLookupCache()->elementsByIdAndSite[$cacheKey];
                } else {
                    $element = $this->_getFormUsageElement($elementId, $elementType, $siteId);
                    $this->_getFormLookupCache()->elementsByIdAndSite[$cacheKey] = $element;
                }

                if (!$element) {
                    continue;
                }

                if (isset($elements[$element->id . '_' . $element->siteId])) {
                    continue;
                }

                $nestedElements = [];
                $this->_handleNestedElement($element, $field, 0, $nestedElements);

                // Sort descending by level and reassign levels.
                usort($nestedElements, function ($a, $b) {
                    return $b['level'] <=> $a['level'];
                });

                foreach ($nestedElements as $i => $nestedElement) {
                    $nestedElement['level'] = $i;
                    $elements[$nestedElement['element']->id . '_' . $nestedElement['site']->id] = $nestedElement;
                }
            }
        }

        return array_values($elements);
    }


    // Private Methods
    // =========================================================================

    private function _resolveFormUsageSiteIds(int $elementId, ?int $relationSiteId, string $elementType): array
    {
        if ($relationSiteId && $this->_getFormUsageElement($elementId, $elementType, $relationSiteId)) {
            return [$relationSiteId];
        }

        return $this->_getElementSiteIds($elementId);
    }

    private function _getElementSiteIds(int $elementId): array
    {
        $siteIds = (new Query())
            ->select(['siteId'])
            ->from([Table::ELEMENTS_SITES])
            ->where(['elementId' => $elementId])
            ->column();

        return array_map('intval', $siteIds);
    }

    private function _getFormUsageElement(int $elementId, string $elementType, int $siteId): ?ElementInterface
    {
        $elementsService = Craft::$app->getElements();
        $element = $elementsService->getElementById($elementId, $elementType, $siteId);

        if ($element) {
            return $element;
        }

        /** @var ElementInterface|null $element */
        $element = $elementsService->createElementQuery($elementType)
            ->id($elementId)
            ->siteId($siteId)
            ->status(null)
            ->drafts(null)
            ->provisionalDrafts(null)
            ->revisions(null)
            ->one();

        return $element;
    }

    private function _populateFormFromPost(Form $form, bool $applyDefaultStencil = true): Form
    {
        $request = Craft::$app->getRequest();
        $bodyParams = $request->getBodyParams();
        $isNewForm = !$form->id;

        if ($bodyParams) {
            $this->_normalizeBuilderFieldReferences($bodyParams);
            $this->_stripBuilderPrivateKeys($bodyParams);
            $request->setBodyParams($bodyParams);
        }

        $form->title = $request->getParam('title', $form->title);
        $form->handle = $request->getParam('handle', $form->handle);
        $form->templateId = StringHelper::toId($request->getParam('templateId', $form->templateId));
        $form->groupId = StringHelper::toId($request->getParam('groupId', $form->groupId));
        $form->defaultStatusId = StringHelper::toId($request->getParam('defaultStatusId', $form->defaultStatusId));
        $form->userDeletedAction = $request->getParam('userDeletedAction', $form->userDeletedAction);
        $form->fileUploadsAction = $request->getParam('fileUploadsAction', $form->fileUploadsAction);
        $form->dataRetention = $request->getParam('dataRetention', $form->dataRetention);
        $form->dataRetentionValue = $request->getParam('dataRetentionValue', $form->dataRetentionValue);
        $form->submitActionEntryId = $request->getParam('submitActionEntryId.id');
        $form->submitActionEntrySiteId = $request->getParam('submitActionEntryId.siteId');

        // Populate the form builder layout (pages/rows/fields)
        if ($pages = $request->getParam('pages')) {
            $form->getFormLayout()->setPages(Json::decodeIfJson($pages));
        }

        // Merge in any new settings, while retaining existing ones. Important for users with permissions.
        if ($newSettings = $request->getParam('settings')) {
            // Retain any integration form settings before wiping them
            $oldIntegrationSettings = $form->settings->integrations ?? [];
            $newIntegrationSettings = $newSettings['integrations'] ?? [];
            $newSettings['integrations'] = array_merge($oldIntegrationSettings, $newIntegrationSettings);

            $form->settings->setAttributes($newSettings, false);
        }

        // Set the notifications
        $form->setNotifications(Formie::$plugin->getNotifications()->buildNotificationsFromPost());

        // Set custom field values
        $form->setFieldValuesFromRequest('fields');

        if ($isNewForm) {
            Formie::$plugin->getFormDefaults()->applyToNewForm($form, $bodyParams);
        }

        // Apply a chosen stencil, which will override a few things above
        if ($stencilId = $request->getParam('applyStencilId')) {
            if ($stencil = Formie::$plugin->getStencils()->getStencilById($stencilId)) {
                $stencil->applyStencilToForm($form, true);
            }
        } else if ($isNewForm && $applyDefaultStencil) {
            Formie::$plugin->getFormDefaults()->applyDefaultStencil($form);
        }

        return $form;
    }

    private function _normalizeBuilderFieldReferences(array &$bodyParams): void
    {
        if (!array_key_exists('pages', $bodyParams)) {
            return;
        }

        $pages = Json::decodeIfJson($bodyParams['pages']);
        if (!is_array($pages)) {
            return;
        }

        $existingReferences = $this->_getExistingFieldReferenceIndex();
        $assignedReferences = [];
        $referenceMap = [];
        $newFieldCounter = 0;

        foreach ($pages as &$page) {
            if (is_array($page) && isset($page['rows'])) {
                $this->_normalizeBuilderRowReferences($page['rows'], $existingReferences, $assignedReferences, $referenceMap, $newFieldCounter);
            }
        }
        unset($page);

        $bodyParams['pages'] = $pages;

        if ($referenceMap !== []) {
            $this->_remapBuilderFieldReferenceTokens($bodyParams, $referenceMap);
        }
    }

    private function _stripBuilderPrivateKeys(array &$value): void
    {
        if (!is_array($value)) {
            return;
        }

        if (array_is_list($value)) {
            foreach ($value as &$item) {
                if (is_array($item)) {
                    $this->_stripBuilderPrivateKeys($item);
                }
            }
            unset($item);

            return;
        }

        foreach (array_keys($value) as $key) {
            if ($key === 'errors' || (is_string($key) && str_starts_with($key, '_'))) {
                unset($value[$key]);
            }
        }

        foreach ($value as &$item) {
            if (is_array($item)) {
                $this->_stripBuilderPrivateKeys($item);
            }
        }
        unset($item);
    }

    private function _normalizeBuilderRowReferences(mixed &$rows, array $existingReferences, array &$assignedReferences, array &$referenceMap, int &$newFieldCounter): void
    {
        if (!is_array($rows)) {
            return;
        }

        foreach ($rows as &$row) {
            if (!is_array($row) || !isset($row['fields']) || !is_array($row['fields'])) {
                continue;
            }

            foreach ($row['fields'] as &$field) {
                if (!is_array($field)) {
                    continue;
                }

                $fieldId = (int)($field['id'] ?? 0);
                $fieldKey = $fieldId ? 'id:' . $fieldId : 'new:' . ++$newFieldCounter;
                $originalReference = trim((string)($field['reference'] ?? ''));
                $reference = $originalReference ?: StringHelper::UUID();

                while ($this->_fieldReferenceConflicts($reference, $fieldId, $fieldKey, $existingReferences, $assignedReferences)) {
                    $reference = StringHelper::UUID();
                }

                $field['reference'] = $reference;
                $assignedReferences[$reference] = $fieldKey;

                if ($originalReference !== '' && $originalReference !== $reference) {
                    $referenceMap[$originalReference] = $reference;
                }

                if (isset($field['rows'])) {
                    $this->_normalizeBuilderRowReferences($field['rows'], $existingReferences, $assignedReferences, $referenceMap, $newFieldCounter);

                    if (isset($field['settings']) && is_array($field['settings'])) {
                        $field['settings']['rows'] = $field['rows'];
                    }
                } else if (isset($field['settings']['rows'])) {
                    $this->_normalizeBuilderRowReferences($field['settings']['rows'], $existingReferences, $assignedReferences, $referenceMap, $newFieldCounter);
                }
            }
            unset($field);
        }
        unset($row);
    }

    private function _fieldReferenceConflicts(string $reference, int $fieldId, string $fieldKey, array $existingReferences, array $assignedReferences): bool
    {
        if ($reference === '') {
            return true;
        }

        $existingFieldId = (int)($existingReferences[$reference] ?? 0);
        if ($existingFieldId && (!$fieldId || $existingFieldId !== $fieldId)) {
            return true;
        }

        return isset($assignedReferences[$reference]) && $assignedReferences[$reference] !== $fieldKey;
    }

    private function _getExistingFieldReferenceIndex(): array
    {
        $rows = (new Query())
            ->select(['id', 'reference'])
            ->from(Table::FORMIE_FORM_FIELDS)
            ->where(['not', ['reference' => null]])
            ->all();

        $references = [];
        foreach ($rows as $row) {
            $reference = trim((string)($row['reference'] ?? ''));
            if ($reference !== '') {
                $references[$reference] = (int)($row['id'] ?? 0);
            }
        }

        return $references;
    }

    private function _remapBuilderFieldReferenceTokens(mixed &$value, array $referenceMap): void
    {
        if (is_string($value)) {
            $value = preg_replace_callback('/\{field:[^}]+\}/', function(array $matches) use ($referenceMap) {
                return References::remapFieldReferenceToken($matches[0], $referenceMap);
            }, $value);
            return;
        }

        if (!is_array($value)) {
            return;
        }

        foreach ($value as &$nestedValue) {
            $this->_remapBuilderFieldReferenceTokens($nestedValue, $referenceMap);
        }
        unset($nestedValue);
    }

    private function _getPaymentIntegrationMetadata(): array
    {
        $integrations = Formie::$plugin->getIntegrations()->getAllIntegrationsForType(Integration::TYPE_PAYMENT);
        $metadata = [];

        foreach ($integrations as $integration) {
            if (!($integration instanceof PaymentIntegration)) {
                continue;
            }

            if (!$integration->getEnabled()) {
                continue;
            }

            $handle = trim((string)$integration->getHandle());
            if ($handle !== '') {
                $metadata[] = [
                    'handle' => $handle,
                    'requiresAjaxSubmission' => (bool)$integration->requiresAjaxSubmission(),
                ];
            }
        }

        return $metadata;
    }

    private function _getTemplateFieldLayoutInfo(): array
    {
        $info = [];
        $templates = Formie::$plugin->getFormTemplates()->getAllTemplates();

        foreach ($templates as $template) {
            $templateId = (int)($template->id ?? 0);

            if (!$templateId) {
                continue;
            }

            $info[$templateId] = [
                'hasFields' => $this->_templateHasCustomFields($template),
            ];
        }

        return $info;
    }

    private function _templateHasCustomFields(FormTemplate $template): bool
    {
        $fieldLayout = $template->getFieldLayout();

        if (!$fieldLayout) {
            return false;
        }
        
        return count($fieldLayout->getCustomFields()) > 0;
    }

    private function _handleNestedElement(ElementInterface $element, ?FieldInterface $field, int $level, array &$accumulator = []): void
    {
        try {
            $accumulator[] = [
                'element' => $element,
                'site' => $element->site,
                'field' => $field,
                'level' => $level,
                'elementType' => $element::displayName(),
                'status' => StringHelper::toTitleCase($element->getStatus()),
                'isRevision' => $element->getIsRevision(),
                'isDraft' => $element->getIsDraft(),
            ];

            if ($element instanceof NestedElementInterface && $element->ownerId) {
                try {
                    // Retrieve (or cache) the owner element using per-site cache key.
                    $ownerId = $element->ownerId;
                    $ownerCacheKey = $ownerId . '_' . $element->siteId;

                    if (isset($this->_getFormLookupCache()->elementsByIdAndSite[$ownerCacheKey])) {
                        $ownerElement = $this->_getFormLookupCache()->elementsByIdAndSite[$ownerCacheKey];
                    } else {
                        $ownerElement = $this->_getFormUsageElement($ownerId, $element::class, $element->siteId);
                        $this->_getFormLookupCache()->elementsByIdAndSite[$ownerCacheKey] = $ownerElement;
                    }

                    // Retrieve (or cache) the owner field using its id.
                    $fieldId = $element->fieldId;

                    if (isset($this->_getFormLookupCache()->fieldsById[$fieldId])) {
                        $ownerField = $this->_getFormLookupCache()->fieldsById[$fieldId];
                    } else {
                        $ownerField = Craft::$app->getFields()->getFieldById($fieldId);
                        $this->_getFormLookupCache()->fieldsById[$fieldId] = $ownerField;
                    }

                    if ($ownerElement) {
                        $this->_handleNestedElement($ownerElement, $ownerField, $level + 1, $accumulator);
                    }
                } catch (Throwable $e) {
                    // Skip over owner-related errors
                }
            }
        } catch (Throwable $e) {
            // Skip over
        }
    }

    private function _getFormLookupCache(): FormLookupCache
    {
        if ($this->_formLookupCache === null) {
            $this->_formLookupCache = new FormLookupCache();
        }

        return $this->_formLookupCache;
    }

    private function _getFormLayoutCacheKey(int $layoutId, ?int $siteId): string
    {
        return $layoutId . ':' . ($siteId ?? 'default');
    }

    private function _getFormLookupKey(string|int $value, ?int $siteId): string
    {
        return $value . ':' . ($siteId ?? 'default');
    }

    /**
     * Load an unpolluted primary-site form snapshot for the CP builder.
     *
     * `actionEdit()` may hydrate the active site first, and `applyToForm()` can mutate
     * shared layout/field instances in memory. Reset caches before reading canonical data.
     */
    private function _getCanonicalFormForBuilder(int $formId): ?Form
    {
        if (!$formId) {
            return null;
        }

        $primarySiteId = Formie::$plugin->getFormSiteOverrides()->getPrimarySiteId();

        Formie::$plugin->getFields()->resetFieldRegistryCache();
        $this->invalidateFormCaches();

        return Craft::$app->getElements()->getElementById($formId, Form::class, $primarySiteId)
            ?? Form::find()->id($formId)->siteId($primarySiteId)->status(null)->one();
    }

    private function _cacheFormLookup(?Form $form, ?int $siteId = null): ?Form
    {
        if (!$form) {
            return null;
        }

        $cache = $this->_getFormLookupCache();
        $id = (int)$form->id;
        $handle = strtolower((string)$form->handle);
        $uid = strtolower((string)$form->uid);
        $resolvedSiteId = $siteId ?? ($form->siteId ? (int)$form->siteId : null);

        $cache->formsById[$this->_getFormLookupKey($id, null)] ??= $form;
        $cache->formsByHandle[$this->_getFormLookupKey($handle, null)] ??= $form;
        $cache->formsByUid[$this->_getFormLookupKey($uid, null)] ??= $form;

        if ($resolvedSiteId !== null) {
            $cache->formsById[$this->_getFormLookupKey($id, $resolvedSiteId)] ??= $form;
            $cache->formsByHandle[$this->_getFormLookupKey($handle, $resolvedSiteId)] ??= $form;
            $cache->formsByUid[$this->_getFormLookupKey($uid, $resolvedSiteId)] ??= $form;
        }

        if ($form && $resolvedSiteId !== null && Formie::$plugin->getFormSiteOverrides()->isEnabled()) {
            return Formie::$plugin->getFormSiteOverrides()->applyToForm($form, $resolvedSiteId, true);
        }

        return $form;
    }

    private function _resolveCachedForm(?Form $form, ?int $siteId): ?Form
    {
        if (!$form || $siteId === null || !Formie::$plugin->getFormSiteOverrides()->isEnabled()) {
            return $form;
        }

        return Formie::$plugin->getFormSiteOverrides()->applyToForm($form, $siteId, true);
    }

    private function _primeForms(array $forms): void
    {
        $cache = $this->_getFormLookupCache();

        foreach ($forms as $form) {
            if (!$form instanceof Form) {
                continue;
            }

            $layoutId = (int)$form->layoutId;

            if (!$layoutId) {
                $this->_cacheFormLookup($form);
                continue;
            }

            $this->_cacheFormLookup($form);

            $defaultKey = $this->_getFormLayoutCacheKey($layoutId, null);
            $siteKey = $this->_getFormLayoutCacheKey($layoutId, $form->siteId ? (int)$form->siteId : null);

            $cache->formsByLayoutId[$defaultKey] ??= $form;
            $cache->formsByLayoutId[$siteKey] ??= $form;
        }
    }
}
