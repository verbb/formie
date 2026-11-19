<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Payment as PaymentIntegration;
use verbb\formie\cache\FormLookupCache;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\gql\SchemaSnapshot;
use verbb\formie\helpers\FormSerializer;
use verbb\formie\helpers\HandleHelper;
use verbb\formie\helpers\IntegrationSecrets;
use verbb\formie\helpers\References;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\Variables;
use verbb\formie\integrations\elements\User as UserIntegration;
use verbb\formie\models\FormTemplate;
use verbb\formie\models\LayoutSaveContext;
use verbb\formie\models\MissingIntegration;
use verbb\formie\models\StencilData;
use verbb\formie\records\Form as FormRecord;
use verbb\formie\references\ReferenceSlot;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\base\NestedElementInterface;
use craft\db\Query;
use craft\elements\User as UserElement;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\errors\GqlException;
use craft\events\PopulateElementEvent;
use craft\helpers\Cp;
use craft\helpers\Json;
use craft\helpers\Typecast;
use craft\helpers\UrlHelper;
use craft\models\GqlSchema;

use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

use Throwable;

class Forms extends Component
{
    // Properties
    // =========================================================================

    private array $_formLookupCaches = [];
    private array $_gqlSnapshots = [];


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

    public function getAllForms(bool $forSchema = false): array
    {
        $cache = $this->_getFormLookupCache($forSchema);

        if ($cache->allForms !== null) {
            return $cache->allForms;
        }

        if ($cache->allFormsWithLayouts !== null) {
            return $cache->allForms = $cache->allFormsWithLayouts;
        }

        $query = Form::find();

        if ($forSchema) {
            // Shared permission and GraphQL definitions must include every site's forms.
            $query->withoutCpIndexScope()->site('*')->unique();
        }

        $forms = $query->all();

        if (!$forSchema) {
            $this->_primeForms($forms);
        }

        return $cache->allForms = $forms;
    }

    public function getAllFormsWithLayouts(bool $forSchema = false): array
    {
        $cache = $this->_getFormLookupCache($forSchema);

        if ($cache->allFormsWithLayouts !== null) {
            return $cache->allFormsWithLayouts;
        }

        $forms = $this->getAllForms($forSchema);

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
        $this->_formLookupCaches = [];
        $this->_gqlSnapshots = [];
    }

    public function getGqlSchemaSnapshot(): SchemaSnapshot
    {
        try {
            $schema = Craft::$app->getGql()->getActiveSchema();
        } catch (GqlException) {
            return new SchemaSnapshot(new GqlSchema(['scope' => []]));
        }

        $key = hash('sha256', Json::encode($schema->scope));

        return $this->_gqlSnapshots[$key] ??= new SchemaSnapshot($schema);
    }

    public function duplicateForm(Form $form, array $attributes = []): Form
    {
        return Craft::$app->getElements()->duplicateElement($form, array_merge($form->getDuplicateAttributes(), $attributes));
    }

    public function buildFormFromPost(bool $allowNotificationChanges = false): Form
    {
        $request = Craft::$app->getRequest();
        $formId = $request->getParam('id');
        $siteId = $request->getParam('siteId');

        if ($formId) {
            $form = Craft::$app->getElements()->getElementById($formId, Form::class, $siteId);

            if (!$form) {
                throw new Exception("No form found for ID: $formId");
            }

            return $this->_populateFormFromPost($form, allowNotificationChanges: $allowNotificationChanges);
        }

        return $this->_populateFormFromPost(new Form(), allowNotificationChanges: $allowNotificationChanges);
    }

    public function buildStencilFormFromPost(?array $oldIntegrationPolicySettings = null): Form
    {
        return $this->_populateFormFromPost(new Form([
            'builderEntityType' => Form::BUILDER_ENTITY_TYPE_STENCIL,
        ]), applyDefaultStencil: false, oldIntegrationPolicySettings: $oldIntegrationPolicySettings);
    }

    public function getFormBuilderVariables(Form $form, ?int $activeSiteId = null): array
    {
        $user = Craft::$app->getUser()->getIdentity();
        $permissions = Formie::$plugin->getPermissions();
        $siteOverrides = Formie::$plugin->getFormSiteOverrides();
        $isStencilBuilder = $form->getBuilderEntityType() === Form::BUILDER_ENTITY_TYPE_STENCIL;
        $activeSiteId ??= $siteOverrides->getActiveSiteId();

        // Stencils have an independent ID namespace and no per-site storage. Treating
        // their IDs as Form element IDs can hydrate unrelated element or form data.
        $canonicalForm = $isStencilBuilder
            ? $form
            : ($this->_getCanonicalFormForBuilder((int)$form->id) ?? $form);
        $canonicalNotifications = $isStencilBuilder
            ? ($canonicalForm->getNotifications() ?? [])
            : Formie::$plugin->getNotifications()->getFormNotifications($canonicalForm);
        $canonicalNotificationsConfig = Formie::$plugin->getNotifications()->getNotificationsConfig($canonicalNotifications);

        if (!$isStencilBuilder) {
            $activeSiteId = $siteOverrides->resolveBuilderActiveSiteId($canonicalForm, $activeSiteId);
        }

        $canonicalData = [
            'id' => $canonicalForm->id,
            'uid' => $canonicalForm->uid,
            'title' => $isStencilBuilder
                ? (string)$canonicalForm->title
                : $siteOverrides->resolveCanonicalFormTitle($canonicalForm),
            'handle' => $canonicalForm->handle,
            'isStencil' => $isStencilBuilder,
            'layoutId' => $canonicalForm->layoutId,
            'templateId' => $canonicalForm->templateId,
            'groupId' => $canonicalForm->groupId,
            'formStatusId' => $canonicalForm->getFormStatusModel()?->id,
            'redirectEntry' =>  array_filter([
                array_filter([
                    'id' => $canonicalForm->redirectEntryId,
                    'siteId' => $canonicalForm->redirectEntrySiteId,
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
            'integrations' => Formie::$plugin->getIntegrations()->getGroupedBuilderIntegrationSummaries(),
            'pages' => $canonicalForm->getFormLayout()->getFormBuilderConfig(),
        ];
        $displayData = $isStencilBuilder
            ? $canonicalData
            : $siteOverrides->applyToBuilderData($canonicalData, $activeSiteId);

        $viewSubmissionsUrl = null;
        $submissions = !$isStencilBuilder && Submission::find()->formId($form->id)->limit(1)->exists();
        $showFieldHandles = (bool)($user?->admin && $user->getPreference('showFieldHandles'));

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
            'enableMultiPageForms' => Formie::$plugin->getSettings()->enableMultiPageForms,
            'templateFieldLayoutInfo' => $this->_getTemplateFieldLayoutInfo(),
            'fieldTypeGroups' => Formie::$plugin->getFields()->getFormBuilderFieldTypes([], $form),
            'hasSubmissions' => (bool)$submissions,
            'viewSubmissionsUrl' => $viewSubmissionsUrl,
            'showFieldHandles' => $showFieldHandles,
            ...Variables::getFormBuilderVariableConfig(),
            'reservedHandles' => Formie::$plugin->getFields()->getReservedHandles(),
            'formHandles' => $form->getBuilderHandleNames(),
            'maxFormHandleLength' => HandleHelper::getMaxFormHandle(),
            'maxFieldHandleLength' => HandleHelper::getMaxFieldHandle(),
            'formMeta' => $form->getFormMetaDetails(),
            'canonicalData' => $canonicalData,
            'translatableProperties' => Formie::$plugin->getFormSiteOverrides()->getBuilderTranslatableConfig(),
            'multiSite' => $isStencilBuilder
                ? ['enabled' => false]
                : $siteOverrides->getBuilderMultiSiteConfig($form, $activeSiteId),
            'data' => $displayData,
            'pageSettingsSchema' => $form->definePageSettingsSchema(),
            'pageButtonSettingsSchema' => $form->definePageButtonSettingsSchema(),
            'clientEventTemplates' => Formie::$plugin->getClientEventTemplates()->getBuilderConfig(),
            'tiptap' => Formie::$plugin->getTiptapExtensions()->getCpEditConfig(),
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

        $relations = $query->all();
        $elementSiteIds = $this->_getElementSiteIdsByElementId(array_map(fn($info) => (int)$info['id'], array_filter($relations, fn($info) => $info['siteId'] === null)));
        $this->_preloadFormUsageElements($relations, $elementSiteIds);

        foreach ($relations as $info) {
            $elementId = (int)$info['id'];
            $elementType = $info['type'];
            $relationSiteId = $info['siteId'] !== null ? (int)$info['siteId'] : null;
            $fieldId = (int)$info['fieldId'];

            if (array_key_exists($fieldId, $this->_getFormLookupCache()->fieldsById)) {
                $field = $this->_getFormLookupCache()->fieldsById[$fieldId];
            } else {
                $field = Craft::$app->getFields()->getFieldById($fieldId);
                $this->_getFormLookupCache()->fieldsById[$fieldId] = $field;
            }

            foreach ($this->_resolveFormUsageSiteIds($elementId, $relationSiteId, $elementType, $elementSiteIds) as $siteId) {
                $element = $this->_getFormUsageElement($elementId, $elementType, $siteId);

                if (!$element) {
                    continue;
                }

                if (isset($elements[$element->id . '_' . $element->siteId])) {
                    continue;
                }

                $nestedElements = [];
                $this->_handleNestedElement($element, $field, 0, $nestedElements);

                // Sort descending by level and reassign levels.
                usort($nestedElements, function($a, $b) {
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


    /**
     * Authorize group changes for an explicit configuring user.
     */
    public function requireUserGroupAssignmentAccess(Form $form, array $oldIntegrationSettings, UserElement $user): void
    {
        $this->_requireUserGroupAssignmentAccess($form, $oldIntegrationSettings, $user);
    }

    public function requireUserGroupCopyAccess(Form $form, UserElement $user): void
    {
        // Check only the enabled settings that will survive stencil serialization.
        $settings = StencilData::getSerializedFormSettings($form->settings);

        foreach ($settings['integrations'] ?? [] as $handle => $binding) {
            $integration = Formie::$plugin->getIntegrations()->resolveIntegrationDefinitionByHandle($handle);

            if ($integration === null || $integration instanceof MissingIntegration) {
                throw new BadRequestHttpException('Configure the integration before copying its enabled settings: ' . $handle);
            }

            if (!$integration instanceof UserIntegration) {
                continue;
            }

            $groups = array_key_exists('groupUids', $binding) ? $binding['groupUids'] : $integration->groupUids;
            $groups = $this->_normalizeUserGroupUids($integration, $groups);
            $this->_requireAssignableUserGroups($groups, $user);

            // Preserve omitted defaults while retaining the checked explicit representation.
            if (array_key_exists('groupUids', $binding)) {
                $form->settings->integrations[$handle]['groupUids'] = $groups;
            }
        }
    }

    public function requireGlobalUserGroupAssignmentAccess(UserIntegration $integration, ?UserIntegration $previous, UserElement $user, array $settings = []): void
    {
        $groupUids = $this->_normalizeUserGroupUids($integration, $integration->groupUids);
        $oldGroupUids = $previous
            ? $this->_normalizeUserGroupUids($previous, $previous->groupUids)
            : [];
        $policy = $this->_userGroupGrantPolicy($integration, $settings);
        $policyChanged = $previous && $policy !== $this->_userGroupGrantPolicy($previous);
        $isEnabling = $previous && !$previous->getEnabled() && $integration->getEnabled();
        $groupUidsToAuthorize = ($isEnabling || $policyChanged) ? $groupUids : array_diff($groupUids, $oldGroupUids);
        $this->_requireAssignableUserGroups($groupUidsToAuthorize, $user);

        $integration->groupUids = $groupUids;
        $integration->setAttributes($policy, false);

        if ($policyChanged) {
            $this->_requireInheritedUserGroupPolicyAccess($integration, $previous, $user);
        }
    }


    // Private Methods
    // =========================================================================

    private function _resolveFormUsageSiteIds(int $elementId, ?int $relationSiteId, string $elementType, array $elementSiteIds): array
    {
        if ($relationSiteId && $this->_getFormUsageElement($elementId, $elementType, $relationSiteId)) {
            return [$relationSiteId];
        }

        return $elementSiteIds[$elementId] ?? $this->_getElementSiteIdsByElementId([$elementId])[$elementId] ?? [];
    }

    private function _getElementSiteIdsByElementId(array $elementIds): array
    {
        if (!$elementIds) {
            return [];
        }

        $siteIds = [];

        $rows = (new Query())
            ->select(['elementId', 'siteId'])
            ->from([Table::ELEMENTS_SITES])
            ->where(['elementId' => array_values(array_unique($elementIds))])
            ->orderBy(['elementId' => SORT_ASC, 'siteId' => SORT_ASC])
            ->all();

        foreach ($rows as $row) {
            $siteIds[(int)$row['elementId']][] = (int)$row['siteId'];
        }

        return $siteIds;
    }

    /**
     * Loads the related elements and their owners with one query for each element type, site and depth.
     *
     * Owners are found from raw rows first, then everything is loaded from the root down, so each nested
     * element is created with its owner already set. Otherwise Craft queries every nested element's owner
     * while creating it, because its field layout comes from the owner.
     */
    private function _preloadFormUsageElements(array $relations, array $elementSiteIds): void
    {
        $idsByTypeAndSite = [];

        foreach ($relations as $info) {
            $elementId = (int)$info['id'];
            $siteIds = $info['siteId'] !== null ? [(int)$info['siteId']] : ($elementSiteIds[$elementId] ?? []);

            foreach ($siteIds as $siteId) {
                $idsByTypeAndSite[$info['type']][$siteId][$elementId] = $elementId;
            }
        }

        try {
            foreach ($this->_getFormUsageLoadOrder($idsByTypeAndSite) as $level) {
                $this->_loadFormUsageElements($level);
            }
        } catch (Throwable) {
            // Elements that fail to load in bulk are loaded one at a time, which skips any that fail again.
        }

        $this->_loadFormUsageElements($idsByTypeAndSite);
    }

    /**
     * Groups the given elements and their uncached owners by depth from their root owner, then by element type and site.
     */
    private function _getFormUsageLoadOrder(array $idsByTypeAndSite): array
    {
        $cache = $this->_getFormLookupCache();
        $nodes = [];
        $owners = [];
        $pending = [];

        foreach ($idsByTypeAndSite as $elementType => $idsBySite) {
            foreach ($idsBySite as $siteId => $elementIds) {
                foreach ($elementIds as $elementId) {
                    $nodes[$elementId . '_' . $siteId] = [$elementType, $siteId, $elementId];
                    $pending[$elementType][$siteId][$elementId] = $elementId;
                    $cache->elementTypesById[$elementId] ??= $elementType;
                }
            }
        }

        // Read each level's owner IDs from raw rows, then their types, until no new owners turn up.
        while ($pending) {
            $ownerIdsBySite = [];

            foreach ($pending as $elementType => $idsBySite) {
                if (!class_exists($elementType) || !is_subclass_of($elementType, NestedElementInterface::class)) {
                    continue;
                }

                foreach ($idsBySite as $siteId => $elementIds) {
                    $elementIds = array_values(array_filter($elementIds, fn($elementId) => !array_key_exists($elementId . '_' . $siteId, $cache->elementsByIdAndSite)));

                    if (!$elementIds) {
                        continue;
                    }

                    $rows = $this->_getFormUsageElementQuery($elementType, $siteId)
                        ->id($elementIds)
                        ->asArray()
                        ->all();

                    foreach ($rows as $row) {
                        $ownerId = (int)($row['ownerId'] ?? $row['primaryOwnerId'] ?? 0);

                        if ($ownerId) {
                            $owners[$row['id'] . '_' . $siteId] = $ownerId . '_' . $siteId;

                            if (!isset($nodes[$ownerId . '_' . $siteId]) && !array_key_exists($ownerId . '_' . $siteId, $cache->elementsByIdAndSite)) {
                                $ownerIdsBySite[$siteId][$ownerId] = $ownerId;
                            }
                        }
                    }
                }
            }

            $this->_cacheElementTypesForFormUsage($ownerIdsBySite ? array_merge(...array_values(array_map('array_values', $ownerIdsBySite))) : []);
            $pending = [];

            foreach ($ownerIdsBySite as $siteId => $ownerIds) {
                foreach ($ownerIds as $ownerId) {
                    if ($ownerType = $cache->elementTypesById[$ownerId] ?? null) {
                        $nodes[$ownerId . '_' . $siteId] = [$ownerType, $siteId, $ownerId];
                        $pending[$ownerType][$siteId][$ownerId] = $ownerId;
                    }
                }
            }
        }

        // An element's depth is its distance from the root owner, so owners always load before the elements they own.
        $depths = [];
        $getDepth = function(string $key, array $path = []) use (&$getDepth, &$depths, $nodes, $owners): int {
            if (isset($depths[$key])) {
                return $depths[$key];
            }

            $ownerKey = $owners[$key] ?? null;
            $hasOwnerToLoad = $ownerKey && isset($nodes[$ownerKey]) && !isset($path[$ownerKey]);

            return $depths[$key] = $hasOwnerToLoad ? $getDepth($ownerKey, $path + [$key => true]) + 1 : 0;
        };

        $levels = [];

        foreach ($nodes as $key => [$elementType, $siteId, $elementId]) {
            $levels[$getDepth($key)][$elementType][$siteId][$elementId] = $elementId;
        }

        ksort($levels);

        return $levels;
    }

    /**
     * Loads and caches the given elements that aren't cached yet.
     */
    private function _loadFormUsageElements(array $idsByTypeAndSite): void
    {
        $cache = $this->_getFormLookupCache();

        foreach ($idsByTypeAndSite as $elementType => $idsBySite) {
            if (!class_exists($elementType)) {
                continue;
            }

            foreach ($idsBySite as $siteId => $elementIds) {
                $elementIds = array_values(array_filter($elementIds, fn($elementId) => !array_key_exists($elementId . '_' . $siteId, $cache->elementsByIdAndSite)));

                if (!$elementIds) {
                    continue;
                }

                $query = $this->_getFormUsageElementQuery($elementType, $siteId)
                    ->id($elementIds)
                    ->indexBy('id');

                if ($query instanceof ElementQuery && is_subclass_of($elementType, NestedElementInterface::class)) {
                    $query->on(ElementQuery::EVENT_BEFORE_POPULATE_ELEMENT, fn(PopulateElementEvent $event) => $this->_setFormUsageOwner($event));
                }

                $elements = $query->all();

                foreach ($elementIds as $elementId) {
                    $cache->elementsByIdAndSite[$elementId . '_' . $siteId] = $elements[$elementId] ?? null;
                }
            }
        }
    }

    /**
     * Gives a nested element its cached owner as it's created, as Craft does for queries limited to one owner.
     */
    private function _setFormUsageOwner(PopulateElementEvent $event): void
    {
        $primaryOwnerId = $event->row['primaryOwnerId'] ?? null;
        $siteId = $event->row['siteId'] ?? null;

        if (!$primaryOwnerId || !$siteId || (isset($event->row['ownerId']) && $event->row['ownerId'] != $primaryOwnerId)) {
            return;
        }

        $owner = $this->_getFormLookupCache()->elementsByIdAndSite[$primaryOwnerId . '_' . $siteId] ?? null;

        if ($owner) {
            $event->row['owner'] = $owner;
            $event->row['primaryOwner'] = $owner;
        }
    }

    private function _getFormUsageElement(int $elementId, string $elementType, int $siteId): ?ElementInterface
    {
        $cache = $this->_getFormLookupCache();
        $cacheKey = $elementId . '_' . $siteId;

        // Missing elements are cached as null, so they aren't queried again.
        if (array_key_exists($cacheKey, $cache->elementsByIdAndSite)) {
            return $cache->elementsByIdAndSite[$cacheKey];
        }

        $element = class_exists($elementType) ? $this->_getFormUsageElementQuery($elementType, $siteId)->id($elementId)->one() : null;

        return $cache->elementsByIdAndSite[$cacheKey] = $element;
    }

    private function _getFormUsageElementQuery(string $elementType, int $siteId): ElementQueryInterface
    {
        // Match Elements::getElementById(), which ignores status and includes drafts and revisions.
        return Craft::$app->getElements()->createElementQuery($elementType)
            ->siteId($siteId)
            ->status(null)
            ->drafts(null)
            ->provisionalDrafts(null)
            ->revisions(null);
    }

    private function _getElementTypeForFormUsage(int $elementId): ?string
    {
        $this->_cacheElementTypesForFormUsage([$elementId]);

        return $this->_getFormLookupCache()->elementTypesById[$elementId];
    }

    private function _cacheElementTypesForFormUsage(array $elementIds): void
    {
        $cache = $this->_getFormLookupCache();
        $elementIds = array_values(array_unique(array_filter($elementIds, fn($elementId) => !array_key_exists($elementId, $cache->elementTypesById))));

        if (!$elementIds) {
            return;
        }

        $types = (new Query())
            ->select(['type'])
            ->from([Table::ELEMENTS])
            ->where(['id' => $elementIds])
            ->indexBy('id')
            ->column();

        foreach ($elementIds as $elementId) {
            $cache->elementTypesById[$elementId] = $types[$elementId] ?? null;
        }
    }

    private function _populateFormFromPost(Form $form, bool $applyDefaultStencil = true, bool $allowNotificationChanges = false, ?array $oldIntegrationPolicySettings = null): Form
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
        $form->formStatusId = StringHelper::toId($request->getParam('formStatusId', $form->formStatusId));
        $form->defaultStatusId = StringHelper::toId($request->getParam('defaultStatusId', $form->defaultStatusId));
        $form->userDeletedAction = $request->getParam('userDeletedAction', $form->userDeletedAction);
        $form->fileUploadsAction = $request->getParam('fileUploadsAction', $form->fileUploadsAction);
        $form->dataRetention = $request->getParam('dataRetention', $form->dataRetention);
        $form->dataRetentionValue = $request->getParam('dataRetentionValue', $form->dataRetentionValue);
        [$form->redirectEntryId, $form->redirectEntrySiteId] = SchemaHelper::firstElementSelectIds(
            $request->getParam('redirectEntry', $request->getParam('submitActionEntry')),
        );

        // Populate the form builder layout (pages/rows/fields)
        if ($pages = $request->getParam('pages')) {
            $serializer = new FormSerializer();
            $form->getFormLayout()->setPages($serializer->hydrateBuilder(Json::decodeIfJson($pages), $form));
            $form->layoutSaveContext = new LayoutSaveContext('builder');
            $form->layoutSaveContext->trusted = false;
            $form->layoutSaveContext->remaps = $serializer->remaps;
        }

        if (!Formie::$plugin->getSettings()->enableMultiPageForms && count($form->getPages()) > 1) {
            $form->addError('pages', Craft::t('formie', 'Multi-page forms are disabled in Formie settings.'));
        }

        $user = Craft::$app->getUser()->getIdentity();
        $isStencilForm = $form->getBuilderEntityType() === Form::BUILDER_ENTITY_TYPE_STENCIL;
        $canManageIntegrations = $isStencilForm
            ? ($user && $user->can('formie-showFormIntegrations'))
            : Formie::$plugin->getPermissions()->canShowFormBuilderTab($user, $form, 'formie-showFormIntegrations');
        $canManageNotifications = $isStencilForm || $allowNotificationChanges || Formie::$plugin->getPermissions()->canShowFormBuilderTab(
            $user,
            $form,
            $isNewForm ? 'formie-createNotifications' : 'formie-showNotifications',
        );
        $oldIntegrationSettings = $form->settings->integrations ?? [];
        $oldNotifications = $canManageNotifications ? [] : $form->getNotifications();

        // Merge in any new settings, while retaining existing ones. Important for users with permissions.
        if ($newSettings = $request->getParam('settings')) {
            if (!$canManageIntegrations) {
                unset($newSettings['integrations']);
            }

            // Retain any integration form settings before wiping them
            $newIntegrationSettings = $newSettings['integrations'] ?? [];
            $newSettings['integrations'] = array_merge($oldIntegrationSettings, $newIntegrationSettings);

            $form->settings->setAttributes($newSettings, false);
        }

        if ($canManageNotifications) {
            $form->setNotifications(Formie::$plugin->getNotifications()->buildNotificationsFromPost());
        }

        // Set custom field values
        $form->setFieldValuesFromRequest('fields');

        if ($isNewForm) {
            Formie::$plugin->getFormDefaults()->applyToNewForm($form, $bodyParams);
        }

        // Apply a chosen stencil, which will override a few things above
        if ($stencilId = $request->getParam('applyStencilId')) {
            if ($stencil = Formie::$plugin->getStencils()->getStencilById($stencilId)) {
                $stencil->applyStencilToForm($form, true);

                if (!$canManageIntegrations) {
                    $form->settings->setAttributes(['integrations' => $oldIntegrationSettings], false);
                }
            }
        } elseif ($isNewForm && $applyDefaultStencil) {
            Formie::$plugin->getFormDefaults()->applyDefaultStencil($form);
        }

        if ($user && $canManageIntegrations) {
            $this->_requireUserGroupAssignmentAccess($form, $oldIntegrationSettings, $user, $oldIntegrationPolicySettings);
        }

        if (!$canManageNotifications) {
            $form->setNotifications($oldNotifications);
        }

        return $form;
    }

    /**
     * Require Craft's native assignment permission for newly configured groups,
     * and for retained groups when enabling or changing how they are granted.
     */
    private function _requireUserGroupAssignmentAccess(Form $form, array $oldIntegrationSettings, UserElement $user, ?array $oldIntegrationPolicySettings = null): void
    {
        foreach ($form->settings->integrations ?? [] as $handle => $settings) {
            if (!is_string($handle) || !is_array($settings)) {
                continue;
            }

            $integration = Formie::$plugin->getIntegrations()->resolveIntegrationDefinitionByHandle($handle);

            if ($integration === null || $integration instanceof MissingIntegration) {
                // Keep unavailable settings portable without accepting new enabled bindings.
                $previous = ($oldIntegrationPolicySettings ?? $oldIntegrationSettings)[$handle] ?? null;

                if (!empty($settings['enabled']) && $settings !== $previous) {
                    throw new BadRequestHttpException('Configure the integration before enabling or changing its form settings: ' . $handle);
                }

                continue;
            }

            if (!$integration instanceof UserIntegration) {
                continue;
            }

            $oldSettings = $oldIntegrationSettings[$handle] ?? [];
            $oldGroupUids = $this->_normalizeUserGroupUids($integration, is_array($oldSettings) ? ($oldSettings['groupUids'] ?? []) : []);
            $groupUids = $this->_normalizeUserGroupUids($integration, $settings['groupUids'] ?? []);
            $isEnabling = is_array($oldSettings)
                && !(bool)($oldSettings['enabled'] ?? false)
                && (bool)($settings['enabled'] ?? false);
            $oldPolicySettings = ($oldIntegrationPolicySettings ?? $oldIntegrationSettings)[$handle] ?? [];
            $policy = $this->_userGroupGrantPolicy($integration, $settings);
            $policyChanged = $policy !== $this->_userGroupGrantPolicy($integration, is_array($oldPolicySettings) ? $oldPolicySettings : []);
            $groupUidsToAuthorize = $isEnabling ? $groupUids : array_diff($groupUids, $oldGroupUids);

            if ($policyChanged) {
                // Missing groups inherit the definition; an explicit empty list overrides it.
                $effectiveGroups = array_key_exists('groupUids', $settings)
                    ? $groupUids
                    : $this->_normalizeUserGroupUids($integration, $integration->groupUids);
                $groupUidsToAuthorize = array_merge($groupUidsToAuthorize, $effectiveGroups);
            }

            $this->_requireAssignableUserGroups($groupUidsToAuthorize, $user);

            // Persist only supplied keys so future global changes still propagate.
            foreach (array_intersect_key($policy, $settings) as $attribute => $value) {
                $form->settings->integrations[$handle][$attribute] = $value;
            }

            // An omitted setting must keep inheriting the integration's global default.
            if (array_key_exists('groupUids', $settings)) {
                $form->settings->integrations[$handle]['groupUids'] = $groupUids;
            }
        }
    }

    private function _requireAssignableUserGroups(array $groupUids, UserElement $user): void
    {
        foreach (array_unique($groupUids) as $groupUid) {
            $group = Craft::$app->getUserGroups()->getGroupByUid($groupUid);

            if (!$group) {
                throw new BadRequestHttpException('Create or restore the user group before assigning it: ' . $groupUid);
            }

            if (!$user->can("assignUserGroup:$group->uid")) {
                throw new ForbiddenHttpException(Craft::t('formie', 'Your account does not have permission to assign the user group “{name}”.', [
                    'name' => $group->name,
                ]));
            }
        }
    }

    private function _userGroupGrantPolicy(UserIntegration $integration, array $settings = []): array
    {
        $policy = $integration->getAttributes(['updateElement', 'updateElementMapping', 'mergeUserGroups', 'activateUser']);
        $policy = IntegrationSecrets::reveal(array_replace($policy, array_intersect_key($settings, $policy)));

        // Reject unreadable wrappers before boolean typecasting can discard them.
        if (IntegrationSecrets::containsUnavailable($policy)) {
            throw new BadRequestHttpException('User group settings contain unavailable encrypted values.');
        }

        Typecast::properties($integration::class, $policy);
        $policy = IntegrationSecrets::reveal($policy);

        // JSON mappings can contain legacy values that only become visible after decoding.
        if (IntegrationSecrets::containsUnavailable($policy)) {
            throw new BadRequestHttpException('User group settings contain unavailable encrypted values.');
        }

        Typecast::properties($integration::class, $policy);

        foreach ($policy['updateElementMapping'] as &$mapping) {
            // Runtime skips legacy `none` mappings even if they also declare a slot kind.
            if (is_array($mapping) && ($mapping['type'] ?? null) === 'none') {
                $mapping = '';
            }

            $mapping = ReferenceSlot::fromStored($mapping)->toArray();
        }

        unset($mapping);
        ksort($policy['updateElementMapping']);

        return $policy;
    }

    private function _requireInheritedUserGroupPolicyAccess(UserIntegration $integration, UserIntegration $previous, UserElement $user): void
    {
        // A binding may supply its own groups while inheriting the changed global behavior.
        // Read persisted settings directly, including dormant bindings, without hydrating forms.
        foreach ([Table::FORMIE_FORMS => 'settings', Table::FORMIE_STENCILS => 'data'] as $table => $column) {
            foreach ((new Query())->select([$column])->from($table)->each() as $row) {
                $data = $row[$column];

                // Match the bounded legacy decoding used when loading site stencils.
                for ($layer = 0; $layer < 2 && is_string($data); $layer++) {
                    $data = Json::decodeIfJson($data);
                }

                $settings = Json::decodeIfJson($column === 'data' ? ($data['settings'] ?? []) : $data);
                $binding = $settings['integrations'][$integration->handle] ?? null;

                if (!is_array($binding) || $this->_userGroupGrantPolicy($previous, $binding) === $this->_userGroupGrantPolicy($integration, $binding)) {
                    continue;
                }

                $groups = array_key_exists('groupUids', $binding) ? $binding['groupUids'] : $integration->groupUids;
                $this->_requireAssignableUserGroups($this->_normalizeUserGroupUids($integration, $groups), $user);
            }
        }
    }

    private function _normalizeUserGroupUids(UserIntegration $integration, mixed $value): array
    {
        // Match stored-settings loading and runtime hydration before checking permissions.
        $attributes = IntegrationSecrets::reveal(['groupUids' => $value]);
        Typecast::properties($integration::class, $attributes);

        // Decoded JSON can expose legacy values that would otherwise change on the next load.
        $attributes = IntegrationSecrets::reveal($attributes);
        $groups = $attributes['groupUids'];

        if (!is_array($groups) || IntegrationSecrets::containsUnavailable($groups)) {
            throw new BadRequestHttpException('User group assignments must be a list of group UIDs.');
        }

        foreach ($groups as $groupUid) {
            if (!is_string($groupUid) || $groupUid === '') {
                throw new BadRequestHttpException('User group assignments must be a list of group UIDs.');
            }
        }

        return $groups;
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
                } elseif (isset($field['settings']['rows'])) {
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
            if (!Craft::$app->getElements()->canView($element)) {
                return;
            }

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
                    // Owners can be a different element type, such as the entry that owns a Content Block.
                    $ownerId = $element->ownerId;
                    $ownerType = $this->_getElementTypeForFormUsage($ownerId);
                    $ownerElement = $ownerType ? $this->_getFormUsageElement($ownerId, $ownerType, $element->siteId) : null;

                    // Retrieve (or cache) the owner field using its id.
                    $fieldId = $element->fieldId;

                    if (array_key_exists($fieldId, $this->_getFormLookupCache()->fieldsById)) {
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

    private function _getFormLookupCache(bool $forSchema = false): FormLookupCache
    {
        $key = $forSchema ? 'schema' : $this->_getDefaultFormSiteId();

        return $this->_formLookupCaches[$key] ??= new FormLookupCache();
    }

    private function _getFormLayoutCacheKey(int $layoutId, ?int $siteId): string
    {
        return $layoutId . ':' . ($siteId ?? $this->_getDefaultFormSiteId());
    }

    private function _getFormLookupKey(string|int $value, ?int $siteId): string
    {
        return $value . ':' . ($siteId ?? $this->_getDefaultFormSiteId());
    }

    private function _getDefaultFormSiteId(): int
    {
        if (Formie::$plugin->getFormSitePropagation()->isEnabled() && Craft::$app->getRequest()->getIsCpRequest()) {
            $requestedSite = Cp::requestedSite();

            if ($requestedSite) {
                return (int)$requestedSite->id;
            }
        }

        return (int)Craft::$app->getSites()->getCurrentSite()->id;
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

        $sourceSiteId = Formie::$plugin->getFormSiteOverrides()->getSourceSiteIdForFormId($formId);

        Formie::$plugin->getFields()->resetFieldRegistryCache();
        $this->invalidateFormCaches();

        return Craft::$app->getElements()->getElementById($formId, Form::class, $sourceSiteId)
            ?? Form::find()->id($formId)->siteId($sourceSiteId)->status(null)->one()
            ?? Form::find()->id($formId)->status(null)->site('*')->one();
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

            $siteKey = $this->_getFormLayoutCacheKey($layoutId, $form->siteId ? (int)$form->siteId : null);

            $cache->formsByLayoutId[$siteKey] ??= $form;
        }
    }
}
