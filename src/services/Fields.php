<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\FixedParentFieldInterface;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\cache\FieldGqlCache;
use verbb\formie\cache\FieldLookupCache;
use verbb\formie\cache\FieldRegistryCache;
use verbb\formie\elements\db\SubmissionQuery;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyExistingFieldsEvent;
use verbb\formie\events\ModifyFieldConfigEvent;
use verbb\formie\events\ModifyFieldRowConfigEvent;
use verbb\formie\events\RegisterFieldOptionsEvent;
use verbb\formie\events\RegisterFieldsEvent;
use verbb\formie\fields as formiefields;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\FieldTraversal;
use verbb\formie\helpers\Plugin;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\ValidationHelper;
use verbb\formie\integrations\feedme\elementfields as FeedMeElementField;
use verbb\formie\integrations\feedme\fields as FeedMeField;
use verbb\formie\models\FieldDefinition;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\FieldLayoutRow;
use verbb\formie\models\LayoutSaveContext;
use verbb\formie\positions\AboveInput;
use verbb\formie\positions\BelowInput;
use verbb\formie\positions\Hidden as HiddenPosition;
use verbb\formie\positions\LeftInput;
use verbb\formie\positions\RightInput;
use verbb\formie\records\FieldDefinitionRecord;
use verbb\formie\records\FieldInstanceRecord;
use verbb\formie\records\FieldLayout as FieldLayoutRecord;
use verbb\formie\records\FieldLayoutPage as FieldLayoutPageRecord;
use verbb\formie\records\FieldLayoutRow as FieldLayoutRowRecord;

use Craft;
use craft\base\Component;
use craft\base\Field as CraftField;
use craft\base\FieldInterface as CraftFieldInterface;
use craft\db\Query;
use craft\errors\MissingComponentException;
use craft\fields\BaseRelationField;
use craft\fields\PlainText;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\GqlSchema;
use craft\validators\HandleValidator;

use yii\base\InvalidConfigException;
use yii\db\Expression;

use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Throwable;

use GraphQL\Type\Definition\Type;

class Fields extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_EXISTING_FIELDS = 'modifyExistingFields';
    public const EVENT_BEFORE_SAVE_FIELD_ROW = 'beforeSaveFieldRow';
    public const EVENT_AFTER_SAVE_FIELD_ROW = 'afterSaveFieldRow';
    public const EVENT_BEFORE_SAVE_FIELD_PAGE = 'beforeSaveFieldPage';
    public const EVENT_AFTER_SAVE_FIELD_PAGE = 'afterSaveFieldPage';

    public const EVENT_REGISTER_FIELDS = 'registerFields';
    public const EVENT_REGISTER_LABEL_POSITIONS = 'registerLabelPositions';
    public const EVENT_REGISTER_INSTRUCTIONS_POSITIONS = 'registerInstructionsPositions';
    public const EVENT_REGISTER_ERROR_MESSAGE_POSITIONS = 'registerErrorMessagePositions';


    // Properties
    // =========================================================================

    private ?FieldLookupCache $_fieldLookupCache = null;
    private ?FieldRegistryCache $_fieldRegistryCache = null;
    private ?FieldGqlCache $_fieldGqlCache = null;
    private ?array $_reservedHandles = null;
    private array $_definitionIdsBeingDeleted = [];
    private int $_fieldSaveBatchDepth = 0;
    private bool $_fieldCachesDirty = false;

    


    // Public Methods
    // =========================================================================

    public function getRegisteredFieldTypes(bool $excludeDisabled = true): array
    {
        $cacheKey = $excludeDisabled ? 'exclude-disabled' : 'include-disabled';

        if (isset($this->_getFieldRegistryCache()->registeredFieldTypes[$cacheKey])) {
            return $this->_getFieldRegistryCache()->registeredFieldTypes[$cacheKey];
        }

        $fieldTypes = [
            formiefields\Address::class,
            formiefields\Agree::class,
            formiefields\Calculations::class,
            formiefields\Categories::class,
            formiefields\Checkboxes::class,
            formiefields\CustomField::class,
            formiefields\Date::class,
            formiefields\Dropdown::class,
            formiefields\Email::class,
            formiefields\Entries::class,
            formiefields\FileUpload::class,
            formiefields\Group::class,
            formiefields\Heading::class,
            formiefields\Hidden::class,
            formiefields\Html::class,
            formiefields\Content::class,
            formiefields\MissingField::class,
            formiefields\MultiLineText::class,
            formiefields\Name::class,
            formiefields\Note::class,
            formiefields\Number::class,
            formiefields\Payment::class,
            formiefields\Password::class,
            formiefields\Phone::class,
            formiefields\Quiz::class,
            formiefields\Radio::class,
            formiefields\Recipients::class,
            formiefields\Repeater::class,
            formiefields\Section::class,
            formiefields\Signature::class,
            formiefields\SingleLineText::class,
            formiefields\Summary::class,
            formiefields\Survey::class,
            formiefields\Table::class,
            formiefields\Tags::class,

            // Include sub-fields, despite them not being able to be added at top-level
            formiefields\subfields\AddressAutoComplete::class,
            formiefields\subfields\Address1::class,
            formiefields\subfields\Address2::class,
            formiefields\subfields\Address3::class,
            formiefields\subfields\AddressCity::class,
            formiefields\subfields\DateDate::class,
            formiefields\subfields\DateTime::class,
            formiefields\subfields\AddressZip::class,
            formiefields\subfields\AddressState::class,
            formiefields\subfields\AddressCountry::class,
            formiefields\subfields\DateYearDropdown::class,
            formiefields\subfields\DateMonthDropdown::class,
            formiefields\subfields\DateDayDropdown::class,
            formiefields\subfields\DateHourDropdown::class,
            formiefields\subfields\DateMinuteDropdown::class,
            formiefields\subfields\DateSecondDropdown::class,
            formiefields\subfields\DateAmPmDropdown::class,
            formiefields\subfields\DateYearNumber::class,
            formiefields\subfields\DateMonthNumber::class,
            formiefields\subfields\DateDayNumber::class,
            formiefields\subfields\DateHourNumber::class,
            formiefields\subfields\DateMinuteNumber::class,
            formiefields\subfields\DateSecondNumber::class,
            formiefields\subfields\DateAmPmNumber::class,
            formiefields\subfields\NamePrefix::class,
            formiefields\subfields\NameFirst::class,
            formiefields\subfields\NameMiddle::class,
            formiefields\subfields\NameLast::class,
        ];

        if (Craft::$app->getEdition() !== Craft::Solo) {
            $fieldTypes = array_merge($fieldTypes, [
                formiefields\Users::class,
            ]);
        }

        if (Plugin::isPluginInstalledAndEnabled('commerce')) {
            $fieldTypes = array_merge($fieldTypes, [
                formiefields\Products::class,
                formiefields\Variants::class,
            ]);
        }

        $this->_getFieldRegistryCache()->registeredFieldTypes[$cacheKey] = $fieldTypes;

        return $this->_getFieldRegistryCache()->registeredFieldTypes[$cacheKey];
    }

    public function getRegisteredFields(bool $excludeDisabled = true): array
    {
        $registeredFields = [];

        foreach ($this->_getResolvedRegisteredFieldTypes($excludeDisabled) as $class) {
            $registeredFields[$class] = $this->_getRegisteredFieldInstance($class);
        }

        return $registeredFields;
    }

    public function getFieldsByType(string $typeClass): array
    {
        $fields = $this->getRegisteredFieldTypes();

        return array_values(array_filter($fields, function(string $fieldClass) use ($typeClass) {
            return is_subclass_of($fieldClass, $typeClass);
        }));
    }

    public function getResolvedRegisteredFieldTypes(bool $excludeDisabled = true): array
    {
        return $this->_getResolvedRegisteredFieldTypes($excludeDisabled);
    }

    public function getRegisteredFieldByType(string $type, bool $excludeDisabled = true): ?FieldInterface
    {
        $registeredFieldTypes = $this->_getResolvedRegisteredFieldTypes($excludeDisabled);

        if (!in_array($type, $registeredFieldTypes, true)) {
            return null;
        }

        return $this->_getRegisteredFieldInstance($type);
    }

    public function canChangeFieldType(string $fromType, string $toType): bool
    {
        if ($fromType === $toType) {
            return true;
        }

        if (!is_subclass_of($fromType, Field::class) || !is_subclass_of($toType, Field::class)) {
            return false;
        }

        return in_array($toType, $fromType::compatibleFieldTypes(), true);
    }

    public function getFormBuilderFieldTypes(array $fullConfigTypes = [], ?Form $form = null): array
    {
        return Formie::$plugin->getFieldPalette()->buildFormBuilderFieldTypeGroups($fullConfigTypes, $form);
    }

    public function getFormBuilderFieldTypeConfig(string $type, bool $hydrateOnly = false): ?array
    {
        $type = trim($type);

        if ($type === '') {
            return null;
        }

        $registeredFieldTypes = $this->_getResolvedRegisteredFieldTypes(false);

        if (!in_array($type, $registeredFieldTypes, true)) {
            return null;
        }

        $field = $this->_getRegisteredFieldInstance($type);

        return $hydrateOnly
            ? $field->getFieldTypeHydrationConfig()
            : $field->getFieldTypeConfig(true);
    }

    public function getFieldTypeDefinition(string $fieldClass): array
    {
        return Formie::$plugin->getFieldTypeDefinitions()->getDefinition($fieldClass);
    }

    public function getFieldTypeDefinitions(array $fieldClasses): array
    {
        return Formie::$plugin->getFieldTypeDefinitions()->getDefinitions($fieldClasses);
    }

    public function getGroupedFieldTypeDefinitions(array $fieldClasses): array
    {
        return Formie::$plugin->getFieldTypeDefinitions()->getGroupedDefinitions($fieldClasses);
    }

    public function getRegisteredFormieFields(): array
    {
        $fields = [];

        $fields[] = FeedMeField\Address::class;
        $fields[] = FeedMeField\Agree::class;
        $fields[] = FeedMeField\Categories::class;
        $fields[] = FeedMeField\Checkboxes::class;
        $fields[] = FeedMeField\Date::class;
        $fields[] = FeedMeField\Dropdown::class;
        $fields[] = FeedMeField\Email::class;
        $fields[] = FeedMeField\Entries::class;
        $fields[] = FeedMeField\FileUpload::class;
        $fields[] = FeedMeField\Group::class;
        $fields[] = FeedMeField\Hidden::class;
        $fields[] = FeedMeField\MultiLineText::class;
        $fields[] = FeedMeField\Name::class;
        $fields[] = FeedMeField\Number::class;
        $fields[] = FeedMeField\Password::class;
        $fields[] = FeedMeField\Phone::class;
        $fields[] = FeedMeField\Radio::class;
        $fields[] = FeedMeField\Repeater::class;
        $fields[] = FeedMeField\SingleLineText::class;
        $fields[] = FeedMeField\Table::class;
        $fields[] = FeedMeField\Tags::class;

        if (Craft::$app->getEdition() !== Craft::Solo) {
            $fields[] = FeedMeField\Users::class;
        }

        if (Plugin::isPluginInstalledAndEnabled('commerce')) {
            $fields[] = FeedMeField\Products::class;
            $fields[] = FeedMeField\Variants::class;
        }

        // Include Formie's element fields
        $fields[] = FeedMeElementField\Forms::class;

        return $fields;
    }

    public function getExistingFields(Form $excludeForm = null): array
    {
        $cacheKey = $excludeForm?->id ?: 'all';

        if (array_key_exists($cacheKey, $this->_getFieldLookupCache()->existingFieldsByExcludeFormId)) {
            return $this->_getFieldLookupCache()->existingFieldsByExcludeFormId[$cacheKey];
        }

        $query = Form::find()->orderBy('title ASC');

        // Exclude the current form.
        if ($excludeForm) {
            $query = $query->id("not {$excludeForm->id}");
        }

        /* @var Form[] $forms */
        $forms = $query->all();

        $allFields = [];
        $syncedDefinitionIds = [];
        $existingFields = [];

        foreach ($forms as $form) {
            $formPages = [];

            foreach ($form->getPages() as $page) {
                $pageFields = [];

                $fields = $page->getFields();
                ArrayHelper::multisort($fields, 'label', SORT_ASC, SORT_STRING);

                foreach ($fields as $field) {
                    // Only include one instance of a synced field.
                    if ($field->isSynced && $field->definitionId && in_array($field->definitionId, $syncedDefinitionIds, true)) {
                        continue;
                    }

                    if ($field->isSynced && $field->definitionId) {
                        $syncedDefinitionIds[] = $field->definitionId;
                    }

                    $pageFields[] = $allFields[] = $field->getFormBuilderConfig();
                }

                $formPages[] = [
                    'label' => $page->label,
                    'fields' => $pageFields,
                ];
            }

            $existingFields[] = [
                'key' => $form->handle,
                'label' => $form->title,
                'pages' => $formPages,
            ];
        }

        ArrayHelper::multisort($allFields, 'label', SORT_ASC, SORT_STRING);

        array_unshift($existingFields, [
            'key' => '*',
            'label' => Craft::t('formie', 'All Forms'),
            'pages' => [
                [
                    'label' => Craft::t('formie', 'All fields'),
                    'fields' => $allFields,
                ],
            ],
        ]);

        // Fire a 'modifyExistingFields' event
        $event = new ModifyExistingFieldsEvent([
            'fields' => $existingFields,
        ]);
        $this->trigger(self::EVENT_MODIFY_EXISTING_FIELDS, $event);

        return $this->_getFieldLookupCache()->existingFieldsByExcludeFormId[$cacheKey] = $event->fields;
    }

    public function getExistingFieldFormOptions(Form $excludeForm = null): array
    {
        $query = Form::find()->orderBy('title ASC');

        // Exclude the current form.
        if ($excludeForm) {
            $query = $query->id("not {$excludeForm->id}");
        }

        /* @var Form[] $forms */
        $forms = $query->all();

        $options = [[
            'key' => '*',
            'label' => Craft::t('formie', 'All Forms'),
            'pages' => [],
        ]];

        foreach ($forms as $form) {
            if (!$form->layoutId) {
                continue;
            }

            $options[] = [
                'key' => $form->handle,
                'label' => $form->title,
                'pages' => [],
            ];
        }

        return $options;
    }

    public function getExistingFieldSummaries(Form $excludeForm = null, ?string $formKey = null, string $search = ''): array
    {
        $query = Form::find()->orderBy('title ASC');

        // Exclude the current form.
        if ($excludeForm) {
            $query = $query->id("not {$excludeForm->id}");
        }

        /* @var Form[] $forms */
        $forms = $query->all();

        if (!$forms) {
            return [];
        }

        $layoutIds = [];
        $formsByLayoutId = [];
        $existingFields = [];

        $trimmedSearch = trim($search);

        foreach ($forms as $form) {
            if ($formKey && $formKey !== '*' && $form->handle !== $formKey) {
                continue;
            }

            $layoutId = (int)$form->layoutId;

            if (!$layoutId) {
                continue;
            }

            $layoutIds[] = $layoutId;
            $formsByLayoutId[$layoutId] = [
                'key' => $form->handle,
                'label' => $form->title,
                'pages' => [],
            ];
        }

        $layoutIds = array_values(array_unique($layoutIds));

        if (!$layoutIds) {
            return [];
        }

        $pageRecords = (new Query())
            ->select(['id', 'layoutId', 'label', 'sortOrder'])
            ->from(Table::FORMIE_FIELD_LAYOUT_PAGES)
            ->where(['layoutId' => $layoutIds])
            ->orderBy(['layoutId' => SORT_ASC, 'sortOrder' => SORT_ASC])
            ->all();

        $pagesById = [];

        foreach ($pageRecords as $pageRecord) {
            $pageId = (int)$pageRecord['id'];
            $layoutId = (int)$pageRecord['layoutId'];

            if (!isset($formsByLayoutId[$layoutId])) {
                continue;
            }

            $pageData = [
                'id' => $pageId,
                'label' => (string)$pageRecord['label'],
                'fields' => [],
            ];

            $formsByLayoutId[$layoutId]['pages'][] = $pageData;
            $pagesById[$pageId] = $pageData;
        }

        $usageQuery = (new Query())
            ->select([
                'fieldId',
                'count' => 'COUNT(*)',
            ])
            ->from(Table::FORMIE_FORM_FIELDS)
            ->groupBy(['fieldId']);

        $fieldQuery = (new Query())
            ->select([
                'ff.id',
                'ff.fieldId',
                'ff.pageId',
                'ff.reference',
                'f.label',
                'f.handle',
                'f.type',
                'COALESCE(usage.count, 1) as usageCount',
            ])
            ->from(['ff' => Table::FORMIE_FORM_FIELDS])
            ->innerJoin(['f' => Table::FORMIE_FIELDS], '[[f.id]] = [[ff.fieldId]]')
            ->leftJoin(['usage' => $usageQuery], '[[usage.fieldId]] = [[ff.fieldId]]')
            ->where(['ff.layoutId' => $layoutIds]);

        if ($trimmedSearch !== '') {
            $fieldQuery->andWhere([
                'or',
                ['like', 'f.label', $trimmedSearch],
                ['like', 'f.handle', $trimmedSearch],
            ]);
        }

        $fieldRecords = $fieldQuery->all();

        foreach ($fieldRecords as $fieldRecord) {
            $pageId = (int)$fieldRecord['pageId'];

            $summary = [
                'id' => (int)$fieldRecord['id'],
                'definitionId' => (int)$fieldRecord['fieldId'],
                'isSynced' => (int)($fieldRecord['usageCount'] ?? 1) > 1,
                'label' => (string)$fieldRecord['label'],
                'handle' => (string)$fieldRecord['handle'],
                'type' => (string)$fieldRecord['type'],
                'reference' => $fieldRecord['reference'] ?: null,
            ];

            if (isset($pagesById[$pageId])) {
                $pagesById[$pageId]['fields'][] = $summary;
            }
        }

        foreach ($formsByLayoutId as $formData) {
            $pages = [];

            foreach ($formData['pages'] as $pageData) {
                $resolvedPage = $pagesById[$pageData['id']] ?? $pageData;
                ArrayHelper::multisort($resolvedPage['fields'], 'label', SORT_ASC, SORT_STRING);

                $pages[] = [
                    'label' => $resolvedPage['label'],
                    'fields' => $resolvedPage['fields'],
                ];
            }

            $existingFields[] = [
                'key' => $formData['key'],
                'label' => $formData['label'],
                'pages' => $pages,
            ];
        }

        if ($formKey === '*') {
            $allFields = [];
            $syncedDefinitions = [];

            foreach ($existingFields as $formData) {
                foreach ($formData['pages'] as $pageData) {
                    foreach ($pageData['fields'] as $fieldData) {
                        $definitionId = (int)($fieldData['definitionId'] ?? 0);

                        if (!empty($fieldData['isSynced']) && $definitionId && in_array($definitionId, $syncedDefinitions, true)) {
                            continue;
                        }

                        if (!empty($fieldData['isSynced']) && $definitionId) {
                            $syncedDefinitions[] = $definitionId;
                        }

                        $allFields[] = $fieldData;
                    }
                }
            }

            ArrayHelper::multisort($allFields, 'label', SORT_ASC, SORT_STRING);

            return [[
                'key' => '*',
                'label' => Craft::t('formie', 'All Forms'),
                'pages' => [[
                    'label' => Craft::t('formie', 'All fields'),
                    'fields' => $allFields,
                ]],
            ]];
        }

        return $existingFields;
    }

    public function getSyncedFieldReport(): array
    {
        $syncedDefinitionIds = (new Query())
            ->select(['fieldId'])
            ->from(Table::FORMIE_FORM_FIELDS)
            ->groupBy(['fieldId'])
            ->having(['>', new Expression('COUNT(*)'), 1])
            ->column();

        if (!$syncedDefinitionIds) {
            return [];
        }

        $syncedDefinitionIds = array_values(array_map('intval', $syncedDefinitionIds));
        $currentSiteId = Craft::$app->getSites()->getCurrentSite()->id;

        $definitions = (new Query())
            ->select(['id', 'label', 'handle', 'type'])
            ->from(Table::FORMIE_FIELDS)
            ->where(['id' => $syncedDefinitionIds])
            ->indexBy('id')
            ->all();

        $instanceRecords = (new Query())
            ->select([
                'ff.fieldId',
                'fo.id as formId',
                'es.title as formTitle',
                'fo.handle as formHandle',
            ])
            ->from(['ff' => Table::FORMIE_FORM_FIELDS])
            ->innerJoin(['fo' => Table::FORMIE_FORMS], '[[fo.layoutId]] = [[ff.layoutId]]')
            ->innerJoin(['e' => Table::ELEMENTS], '[[e.id]] = [[fo.id]] AND [[e.dateDeleted]] IS NULL')
            ->innerJoin(['es' => Table::ELEMENTS_SITES], '[[es.elementId]] = [[fo.id]] AND [[es.siteId]] = :siteId', [
                ':siteId' => $currentSiteId,
            ])
            ->where(['ff.fieldId' => $syncedDefinitionIds])
            ->groupBy(['ff.fieldId', 'fo.id', 'es.title', 'fo.handle'])
            ->all();

        $formIds = [];
        $formsByDefinitionId = [];

        foreach ($instanceRecords as $instanceRecord) {
            $definitionId = (int)($instanceRecord['fieldId'] ?? 0);
            $formId = (int)($instanceRecord['formId'] ?? 0);

            if (!$definitionId || !$formId) {
                continue;
            }

            $formIds[] = $formId;
            $formsByDefinitionId[$definitionId][$formId] = [
                'id' => $formId,
                'title' => (string)($instanceRecord['formTitle'] ?? $formId),
                'handle' => (string)($instanceRecord['formHandle'] ?? ''),
            ];
        }

        $formIds = array_values(array_unique($formIds));
        $formsById = [];

        if ($formIds) {
            foreach (Form::find()->id($formIds)->siteId($currentSiteId)->all() as $form) {
                $formsById[(int)$form->id] = $form;
            }
        }

        $report = [];

        foreach ($syncedDefinitionIds as $definitionId) {
            $definition = $definitions[$definitionId] ?? null;

            if (!$definition) {
                continue;
            }

            $forms = [];

            foreach ($formsByDefinitionId[$definitionId] ?? [] as $formData) {
                $formId = (int)$formData['id'];
                $form = $formsById[$formId] ?? null;

                $forms[] = [
                    'id' => $formId,
                    'title' => $form ? (string)$form->title : $formData['title'],
                    'handle' => $form ? (string)$form->handle : $formData['handle'],
                    'cpEditUrl' => $form?->getCpEditUrl() ?? UrlHelper::cpUrl('formie/forms/edit/' . $formId),
                ];
            }

            ArrayHelper::multisort($forms, 'title', SORT_ASC, SORT_STRING);

            $type = (string)($definition['type'] ?? '');

            $report[] = [
                'id' => $definitionId,
                'label' => (string)($definition['label'] ?? ''),
                'handle' => (string)($definition['handle'] ?? ''),
                'type' => $type,
                'typeLabel' => $this->_getFieldTypeLabel($type),
                'usageCount' => count($forms),
                'forms' => $forms,
            ];
        }

        ArrayHelper::multisort($report, 'label', SORT_ASC, SORT_STRING);

        return $report;
    }

    public function getExistingFieldConfigs(array $fieldIds, Form $excludeForm = null): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $fieldIds))));

        if (!$ids) {
            return [];
        }

        $query = $this->_createFormFieldConfigQuery()
            ->where(['ff.id' => $ids]);

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $layouts = (new Query())
                ->select(['layoutId'])
                ->from(Table::FORMIE_FORMS)
                ->where(['id' => Form::find()->ids()]);

            $query->andWhere(['ff.layoutId' => $layouts]);
        }

        if ($excludeForm && $excludeForm->layoutId) {
            $query->andWhere(['not', ['ff.layoutId' => $excludeForm->layoutId]]);
        }

        $records = $query->all();
        $recordsById = [];

        foreach ($records as $record) {
            $normalizedRecord = $this->_normalizeFormFieldConfig($record);
            $recordsById[(int)$normalizedRecord['id']] = $normalizedRecord;
        }

        $fields = [];

        foreach ($ids as $id) {
            if (!isset($recordsById[$id])) {
                continue;
            }

            $field = Formie::$plugin->getFields()->createField($recordsById[$id]);

            $fields[] = [
                'id' => $id,
                'field' => $field->getFormBuilderConfig() + [
                    'definitionToken' => Craft::$app->getSecurity()->hashData(Json::encode([
                        'purpose' => 'field-definition', 'definitionId' => $field->definitionId,
                        'formId' => $excludeForm?->id, 'userId' => Craft::$app->getUser()->getId(),
                    ])),
                ],
            ];
        }

        return $fields;
    }

    public function createField(array $config = []): FieldInterface
    {
        return $this->hydrateField($config);
    }

    public function hydrateField(array $config): FieldInterface
    {
        // This path hydrates trusted persisted/internal config. Request payloads first pass FormSerializer.
        $type = (string)($config['type'] ?? '');
        $instanceSettings = $config['instanceSettings'] ?? null;
        unset($config['instanceSettings']);
        if (!empty($config['syncedDefinitionUid'])) {
            $config['definitionUid'] = $config['syncedDefinitionUid'];
            $config['isSynced'] = true;
            $definition = $this->getFieldDefinitionByUid($config['syncedDefinitionUid']);
            if ($definition && $definition->type === $type) {
                $config['definitionId'] = $definition->id;
                $config['definitionUid'] = $definition->uid;
                $config['isSynced'] = true;
            }
        }
        unset($config['syncedDefinitionUid']);
        $settings = Json::decodeIfJson($config['settings'] ?? []) ?: [];
        if ($type === formiefields\MissingField::class) {
            $type = (string)($config['expectedType'] ?? $settings['expectedType'] ?? '');
            $settings = $settings['settings'] ?? $settings;
        }
        $recoverable = array_key_exists('__formieMissingSettings', $settings);
        if ($recoverable) {
            $settings = $settings['__formieMissingSettings'];
        }
        unset($config['class'], $config['syncedDefinitionHandle'], $config['syncedDefinitionId']);
        if (!in_array($type, $this->_getResolvedRegisteredFieldTypes(true), true)) {
            $identity = array_intersect_key($config, array_flip(['id', 'uid', 'reference', 'definitionId', 'definitionUid', 'layoutId', 'pageId', 'rowId', 'sortOrder', 'label', 'handle', 'required', 'isSynced', 'usageCount']));
            $field = new formiefields\MissingField($identity + [
                'expectedType' => $type,
                'settings' => $settings,
                'errorMessage' => "Unavailable field type: $type",
            ]);
        } else {
            unset($config['expectedType']);
            $config['type'] = $type;
            $config['settings'] = $recoverable ? (new \verbb\formie\helpers\FormSerializer())->recoverSettings($type, $settings) : $settings;
            $field = ComponentHelper::createComponent($config, Field::class);
        }
        $field->applyInstanceSettings($instanceSettings);
        $field->afterCreateField($config);

        return $field;
    }

    public function getAllLayouts(): array
    {
        if ($this->_getFieldLookupCache()->layouts) {
            return $this->_getFieldLookupCache()->layouts;
        }

        $layoutIds = (new Query())
            ->select(['id'])
            ->from(Table::FORMIE_FIELD_LAYOUTS)
            ->column();

        return $this->_getFieldLookupCache()->layouts = array_values($this->getLayoutsByIds($layoutIds));
    }

    public function getAllFields(): array
    {
        if ($this->_getFieldLookupCache()->fields === null) {
            // Keep raw form-field rows cached until a caller actually asks for hydrated field
            // instances. Many requests only need layout/config metadata, so eagerly creating every
            // field object up front recreates the same broad bootstrap cost Craft hit in #13992.
            $this->_getFieldLookupCache()->fields = array_map(fn($record) => $this->_normalizeFormFieldConfig($record), $this->_createFormFieldConfigQuery()->all());
        }

        return $this->_hydrateCachedFields($this->_getFieldLookupCache()->fields);
    }

    public function getAllFieldsForForm(int $formId): array
    {
        return $this->getAllFieldsForForms([$formId])[$formId] ?? [];
    }

    public function getAllFieldConfigsForForms(array $formIds): array
    {
        $resolvedFormIds = array_values(array_unique(array_filter(array_map('intval', $formIds))));

        if (!$resolvedFormIds) {
            return [];
        }

        $fieldConfigsByForm = [];
        $missingFormIds = [];

        foreach ($resolvedFormIds as $formId) {
            if (isset($this->_getFieldLookupCache()->fieldsForForm[$formId])) {
                $fieldConfigsByForm[$formId] = $this->_getFieldLookupCache()->fieldsForForm[$formId];
            } else {
                $missingFormIds[] = $formId;
            }
        }

        if ($missingFormIds) {
            $fieldRecords = $this->_createFormFieldConfigQuery()
                ->select([
                    'ff.id',
                    'ff.fieldId',
                    'ff.layoutId',
                    'ff.pageId',
                    'ff.rowId',
                    'ff.reference',
                    'ff.sortOrder',
                    'ff.dateCreated',
                    'ff.dateUpdated',
                    'ff.uid',
                    'ff.settings as formFieldSettings',
                    'f.label',
                    'f.handle',
                    'f.type',
                    'f.uid as definitionUid',
                    'f.settings',
                    'COALESCE(usage.count, 1) as usageCount',
                    'fo.id as formId',
                ])
                ->innerJoin(['fo' => Table::FORMIE_FORMS], '[[ff.layoutId]] = [[fo.layoutId]]')
                ->where(['fo.id' => $missingFormIds])
                ->all();

            foreach ($missingFormIds as $formId) {
                $this->_getFieldLookupCache()->fieldsForForm[$formId] = [];
            }

            foreach ($fieldRecords as $fieldRecord) {
                $formId = (int)($fieldRecord['formId'] ?? 0);

                if (!$formId) {
                    continue;
                }

                // Keep GraphQL and other metadata-only consumers on the normalized config path so
                // they can inspect field structure without paying the full `createField()` cost.
                $this->_getFieldLookupCache()->fieldsForForm[$formId][] = $this->_normalizeFormFieldConfig($fieldRecord);
            }
        }

        foreach ($resolvedFormIds as $formId) {
            $fieldConfigsByForm[$formId] = $this->_getFieldLookupCache()->fieldsForForm[$formId] ?? [];
        }

        return $fieldConfigsByForm;
    }

    public function getAllFieldsForForms(array $formIds): array
    {
        $fieldsByForm = [];
        foreach ($this->getAllFieldConfigsForForms($formIds) as $formId => $_fieldConfigs) {
            $fieldsByForm[$formId] = isset($this->_getFieldLookupCache()->fieldsForForm[$formId])
                ? $this->_hydrateCachedFields($this->_getFieldLookupCache()->fieldsForForm[$formId])
                : [];
        }

        return $fieldsByForm;
    }

    public function getFieldConfigSettings(array $fieldConfig): array
    {
        $cacheKey = $this->_getFieldConfigSettingsCacheKey($fieldConfig);

        if (array_key_exists($cacheKey, $this->_getFieldLookupCache()->decodedFieldSettings)) {
            return $this->_getFieldLookupCache()->decodedFieldSettings[$cacheKey];
        }

        $settings = Json::decodeIfJson($fieldConfig['settings'] ?? null);

        return $this->_getFieldLookupCache()->decodedFieldSettings[$cacheKey] = (is_array($settings) ? $settings : []);
    }

    public function getNestedFieldConfigs(array $fieldConfig): array
    {
        $settings = $this->getFieldConfigSettings($fieldConfig);
        $nestedLayoutId = (int)($fieldConfig['nestedLayoutId'] ?? $settings['nestedLayoutId'] ?? 0);

        // Saved parent fields reference a normalized layout instead of embedding rows.
        // Read its child configs without hydrating fields, while allowing explicit draft rows.
        if ($nestedLayoutId && !array_key_exists('rows', $fieldConfig) && !array_key_exists('rows', $settings)) {
            $cache = $this->_getFieldLookupCache();

            if (!array_key_exists($nestedLayoutId, $cache->nestedFieldConfigsByLayoutId)) {
                $records = $this->_createFormFieldConfigQuery()->where(['ff.layoutId' => $nestedLayoutId])->all();
                $cache->nestedFieldConfigsByLayoutId[$nestedLayoutId] = array_map(function(array $record): array {
                    $config = $this->_normalizeFormFieldConfig($record);
                    $config['enabled'] = (bool)($this->getFieldConfigSettings($config)['enabled'] ?? true);

                    return $config;
                }, $records);
            }

            return $cache->nestedFieldConfigsByLayoutId[$nestedLayoutId];
        }

        $rows = $fieldConfig['rows'] ?? $settings['rows'] ?? [];

        if (!is_array($rows)) {
            return [];
        }

        $nestedFieldConfigs = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            foreach (($row['fields'] ?? []) as $nestedFieldConfig) {
                if (!is_array($nestedFieldConfig)) {
                    continue;
                }

                $nestedFieldConfigs[] = $this->_normalizeNestedFieldConfig($nestedFieldConfig);
            }
        }

        return $nestedFieldConfigs;
    }

    public function getFieldConfigGqlTypeName(array $fieldConfig, string $suffix): string
    {
        $handle = preg_replace('/[^A-Za-z0-9_]+/', '_', (string)($fieldConfig['handle'] ?? 'Field'));
        $handle = trim((string)$handle, '_');
        $handle = $handle !== '' ? $handle : 'Field';
        $identifier = $this->_getGqlFieldConfigCacheKey($fieldConfig);
        $identifier = preg_replace('/[^A-Za-z0-9_]+/', '_', $identifier);
        $identifier = trim((string)$identifier, '_');

        return "Formie_{$handle}_{$identifier}_{$suffix}";
    }

    public function getLayoutById(int $id): ?FieldLayout
    {
        return $this->getLayoutsByIds([$id])[$id] ?? null;
    }

    public function getLayoutsByIds(array $ids): array
    {
        $resolvedIds = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if (!$resolvedIds) {
            return [];
        }

        $layouts = [];
        $missingIds = [];

        foreach ($resolvedIds as $id) {
            if (array_key_exists($id, $this->_getFieldLookupCache()->layoutsById)) {
                if ($this->_getFieldLookupCache()->layoutsById[$id] !== null) {
                    $layouts[$id] = $this->_getFieldLookupCache()->layoutsById[$id];
                }
            } else {
                $missingIds[] = $id;
            }
        }

        if ($missingIds) {
            // Resolve all missing layouts in one query/build pass so callers that iterate many forms
            // do not pay the nested layout hydration cost once per layout.
            foreach ($this->_getLayouts(['l.id' => $missingIds]) as $layoutId => $layout) {
                $this->_getFieldLookupCache()->layoutsById[$layoutId] = $layout;
                $layouts[$layoutId] = $layout;
            }

            foreach ($missingIds as $missingId) {
                if (!array_key_exists($missingId, $this->_getFieldLookupCache()->layoutsById)) {
                    // Cache misses too, otherwise repeated lookups for unknown IDs keep requerying.
                    $this->_getFieldLookupCache()->layoutsById[$missingId] = null;
                }
            }
        }

        return $layouts;
    }

    public function getPageById(int $id): ?FieldLayoutPage
    {
        if (!$id) {
            return null;
        }

        if (array_key_exists($id, $this->_getFieldLookupCache()->pagesById)) {
            return $this->_getFieldLookupCache()->pagesById[$id];
        }

        return $this->_getFieldLookupCache()->pagesById[$id] = $this->_getPage(['p.id' => $id]);
    }

    public function getRowById(int $id): ?FieldLayoutRow
    {
        if (!$id) {
            return null;
        }

        if (array_key_exists($id, $this->_getFieldLookupCache()->rowsById)) {
            return $this->_getFieldLookupCache()->rowsById[$id];
        }

        return $this->_getFieldLookupCache()->rowsById[$id] = $this->_getRow(['r.id' => $id]);
    }

    public function getFieldById(int $id): ?FieldInterface
    {
        $fieldRecord = $this->_getFieldConfigById($id);

        return $fieldRecord ? $this->hydrateField($fieldRecord) : null;
    }

    public function getFieldDefinitionById(int $id): ?FieldDefinition
    {
        if (!$id) {
            return null;
        }

        $fieldRecord = (new Query())
            ->from(Table::FORMIE_FIELDS)
            ->where(['id' => $id])
            ->one();

        return $fieldRecord ? new FieldDefinition($fieldRecord) : null;
    }

    public function getFieldDefinitionByUid(string $uid): ?FieldDefinition
    {
        $record = (new Query())->from(Table::FORMIE_FIELDS)->where(['uid' => $uid])->one();

        return $record ? new FieldDefinition($record) : null;
    }

    public function getFieldByReference(string $reference): ?FieldInterface
    {
        $fieldRecord = $this->_getFieldConfigByReference($reference);

        return $fieldRecord ? $this->hydrateField($fieldRecord) : null;
    }

    public function fieldIncludedInGqlSchema(FieldInterface $field, GqlSchema $schema): bool
    {
        $fieldCacheKey = $this->_getGqlFieldCacheKey($field);
        $schemaCacheKey = spl_object_id($schema);
        $cachedValue = $this->_getFieldGqlCache()->getFieldIncludeInSchema($fieldCacheKey, $schemaCacheKey);

        if ($cachedValue === null) {
            $cachedValue = $field->includeInGqlSchema($schema);
            $this->_getFieldGqlCache()->setFieldIncludeInSchema($fieldCacheKey, $schemaCacheKey, $cachedValue);
        }

        return $cachedValue;
    }

    public function getFieldContentGqlType(FieldInterface $field): Type|array
    {
        $fieldCacheKey = $this->_getGqlFieldCacheKey($field);
        $cachedValue = $this->_getFieldGqlCache()->getFieldContentType($fieldCacheKey);

        if ($cachedValue === null) {
            $cachedValue = $field->getContentGqlType();
            $this->_getFieldGqlCache()->setFieldContentType($fieldCacheKey, $cachedValue);
        }

        return $cachedValue;
    }

    public function getFieldContentGqlQueryArgumentType(FieldInterface $field): Type|array
    {
        $fieldCacheKey = $this->_getGqlFieldCacheKey($field);
        $cachedValue = $this->_getFieldGqlCache()->getFieldQueryArgumentType($fieldCacheKey);

        if ($cachedValue === null) {
            $cachedValue = $field->getContentGqlQueryArgumentType();
            $this->_getFieldGqlCache()->setFieldQueryArgumentType($fieldCacheKey, $cachedValue);
        }

        return $cachedValue;
    }

    public function getFieldContentGqlMutationArgumentType(FieldInterface $field): Type|array
    {
        $fieldCacheKey = $this->_getGqlFieldCacheKey($field);
        $cachedValue = $this->_getFieldGqlCache()->getFieldMutationArgumentType($fieldCacheKey);

        if ($cachedValue === null) {
            $cachedValue = $field->getContentGqlMutationArgumentType();
            $this->_getFieldGqlCache()->setFieldMutationArgumentType($fieldCacheKey, $cachedValue);
        }

        return $cachedValue;
    }

    public function fieldConfigIncludedInGqlSchema(array $fieldConfig, GqlSchema $schema): bool
    {
        $fieldCacheKey = $this->_getGqlFieldConfigCacheKey($fieldConfig);
        $schemaCacheKey = spl_object_id($schema);
        $cachedValue = $this->_getFieldGqlCache()->getConfigIncludeInSchema($fieldCacheKey, $schemaCacheKey);

        if ($cachedValue === null) {
            $provider = $this->_getGqlFieldConfigProvider($fieldConfig);

            $cachedValue = $provider
                ? $provider::gqlIncludeInSchemaFromConfig($fieldConfig, $schema)
                : $this->fieldIncludedInGqlSchema($this->hydrateField($fieldConfig), $schema);

            $this->_getFieldGqlCache()->setConfigIncludeInSchema($fieldCacheKey, $schemaCacheKey, $cachedValue);
        }

        return $cachedValue;
    }

    public function getFieldConfigContentGqlType(array $fieldConfig): Type|array
    {
        $fieldCacheKey = $this->_getGqlFieldConfigCacheKey($fieldConfig);
        $cachedValue = $this->_getFieldGqlCache()->getConfigContentType($fieldCacheKey);

        if ($cachedValue === null) {
            $provider = $this->_getGqlFieldConfigProvider($fieldConfig);

            $cachedValue = $provider
                ? $provider::gqlContentTypeFromConfig($fieldConfig)
                : $this->getFieldContentGqlType($this->hydrateField($fieldConfig));

            $this->_getFieldGqlCache()->setConfigContentType($fieldCacheKey, $cachedValue);
        }

        return $cachedValue;
    }

    public function getFieldConfigContentGqlQueryArgumentType(array $fieldConfig): Type|array
    {
        $fieldCacheKey = $this->_getGqlFieldConfigCacheKey($fieldConfig);
        $cachedValue = $this->_getFieldGqlCache()->getConfigQueryArgumentType($fieldCacheKey);

        if ($cachedValue === null) {
            $provider = $this->_getGqlFieldConfigProvider($fieldConfig);

            $cachedValue = $provider
                ? $provider::gqlContentQueryArgumentTypeFromConfig($fieldConfig)
                : $this->getFieldContentGqlQueryArgumentType($this->hydrateField($fieldConfig));

            $this->_getFieldGqlCache()->setConfigQueryArgumentType($fieldCacheKey, $cachedValue);
        }

        return $cachedValue;
    }

    public function getFieldConfigContentGqlMutationArgumentType(array $fieldConfig): Type|array
    {
        $fieldCacheKey = $this->_getGqlFieldConfigCacheKey($fieldConfig);
        $cachedValue = $this->_getFieldGqlCache()->getConfigMutationArgumentType($fieldCacheKey);

        if ($cachedValue === null) {
            $provider = $this->_getGqlFieldConfigProvider($fieldConfig);

            $cachedValue = $provider
                ? $provider::gqlContentMutationArgumentTypeFromConfig($fieldConfig)
                : $this->getFieldContentGqlMutationArgumentType($this->hydrateField($fieldConfig));

            $this->_getFieldGqlCache()->setConfigMutationArgumentType($fieldCacheKey, $cachedValue);
        }

        return $cachedValue;
    }

    public function saveLayout(FieldLayout $layout, ?LayoutSaveContext $context = null): bool
    {
        if ($context === null) {
            $context = new LayoutSaveContext();
            if ($layout->id) {
                $context->includeLayout($layout->id);
            }
        }
        $context->assertLayout($layout->id);
        $context->ensureValidationGraph($layout);
        $isNewLayout = !$layout->id;

        if (!$layout->beforeSave($isNewLayout)) {
            return false;
        }

        if (!$context->getIsGraphValidated()) {
            if (!$layout->validate()) {
                return false;
            }

            $context->markGraphValidated();
        }

        // Nested layouts participate in the root save rather than opening their own
        // transactions, repeating usage queries and flushing the same caches.
        $isRootSave = $context->beginSave($layout->getFieldsRecursively());
        $transaction = null;
        $transactionFinished = false;

        if ($isRootSave) {
            $this->_beginFieldSaveBatch();
        }

        try {
            if ($isRootSave) {
                $transaction = Craft::$app->getDb()->beginTransaction();
            }

            $layoutRecord = $isNewLayout ? new FieldLayoutRecord() : FieldLayoutRecord::findOne($layout->id);

            if (!$layoutRecord) {
                throw new Exception('Invalid field layout ID: ' . $layout->id);
            }

            $layoutRecord->save(false);
            $layout->id = $layoutRecord->id;
            $context->layouts[$layout->id] = true;
            foreach ($layout->getPages() as $pageKey => $page) {
                $page->layoutId = $layout->id;
                $page->sortOrder = $pageKey;

                if (!$this->savePage($page, $context)) {
                    // Bubble-up the page errors with stable page/row/field paths.
                    ValidationHelper::addPrefixedErrors($layout, $page->getErrors(), "pages.$pageKey");

                    throw new Exception('Failed to save field layout page.');
                }
            }

            // Cleanup any deleted pages/rows/fields by diffing against payload.
            $this->_cleanupDeletedLayoutItems($layout);

            if ($isRootSave) {
                $context->refreshSavedFieldUsage();
                $transaction->commit();
                $transactionFinished = true;
            }
            
            $layout->afterSave($isNewLayout);

            return true;
        } catch (Throwable $e) {
            if ($transaction && !$transactionFinished) {
                $transaction->rollBack();
                $transactionFinished = true;
            }

            Formie::error('Failed to save field layout: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'exception' => $e,
            ]);

            if (!$layout->hasErrors()) {
                $layout->addError('layout', $e->getMessage() ?: Craft::t('formie', 'An error occurred.'));
            }
        } finally {
            $context->endSave();

            if ($isRootSave) {
                if ($transaction && !$transactionFinished) {
                    $transaction->rollBack();
                    $transactionFinished = true;
                }

                $this->_endFieldSaveBatch();
            }
        }

        return false;
    }
    
    public function deleteLayoutById(int $id): bool
    {
        $layout = $this->getLayoutById($id);

        if (!$layout) {
            return false;
        }

        return $this->deleteLayout($layout);
    }
    
    public function deleteLayout(FieldLayout $layout): bool
    {
        if (!$layout->beforeDelete()) {
            return false;
        }

        Db::delete(Table::FORMIE_FIELD_LAYOUTS, ['id' => $layout->id]);
        $this->_deleteUnusedFieldDefinitions();
        $this->_resetFieldCaches();

        $layout->afterDelete();

        return true;
    }

    public function savePage(FieldLayoutPage $page, ?LayoutSaveContext $context = null): bool
    {
        $isNewPage = !$page->id;

        if (!$page->beforeSave($isNewPage)) {
            return false;
        }

        if (!$context?->getIsGraphValidated() && !$page->validate()) {
            return false;
        }

        $pageRecord = $isNewPage ? new FieldLayoutPageRecord() : FieldLayoutPageRecord::findOne($page->id);

        if (!$pageRecord) {
            throw new Exception('Invalid field page ID: ' . $page->id);
        }

        if ($page->id && (int)$pageRecord->layoutId !== (int)$page->layoutId) {
            throw new InvalidConfigException('The layout item belongs to another layout.');
        }

        $pageRecord->id = $page->id;
        $pageRecord->layoutId = $page->layoutId;
        $pageRecord->label = $page->label;
        $pageRecord->sortOrder = $page->sortOrder;
        $pageRecord->settings = $page->getSettings();

        if ($page->uid) {
            $pageRecord->uid = $page->uid;
        }

        $pageRecord->save(false);

        $page->id = $pageRecord->id;
        $page->uid = $pageRecord->uid;

        $page->afterSave($isNewPage);

        foreach ($page->getRows() as $rowKey => $row) {
            $row->layoutId = $page->layoutId;
            $row->pageId = $page->id;
            $row->sortOrder = $rowKey;

            if (!$this->saveRow($row, $context)) {
                // Bubble-up the row errors with stable row/field paths.
                ValidationHelper::addPrefixedErrors($page, $row->getErrors(), "rows.$rowKey");

                return false;
            }
        }

        return true;
    }
    
    public function deletePageById(int $id): bool
    {
        $page = $this->getPageById($id);

        if (!$page) {
            return false;
        }

        return $this->deletePage($page);
    }
    
    public function deletePage(FieldLayoutPage $page): bool
    {
        if (!$page->beforeDelete()) {
            return false;
        }

        Db::delete(Table::FORMIE_FIELD_LAYOUT_PAGES, ['id' => $page->id]);

        $page->afterDelete();

        return true;
    }

    public function saveRow(FieldLayoutRow $row, ?LayoutSaveContext $context = null): bool
    {
        $isNewRow = !$row->id;

        if (!$row->beforeSave($isNewRow)) {
            return false;
        }

        if (!$context?->getIsGraphValidated() && !$row->validate()) {
            return false;
        }

        $rowRecord = $isNewRow ? new FieldLayoutRowRecord() : FieldLayoutRowRecord::findOne($row->id);

        if (!$rowRecord) {
            throw new Exception('Invalid field row ID: ' . $row->id);
        }

        if ($row->id && (int)$rowRecord->layoutId !== (int)$row->layoutId) {
            throw new InvalidConfigException('The layout item belongs to another layout.');
        }

        $rowRecord->id = $row->id;
        $rowRecord->layoutId = $row->layoutId;
        $rowRecord->pageId = $row->pageId;
        $rowRecord->sortOrder = $row->sortOrder;

        $rowRecord->save(false);

        $row->id = $rowRecord->id;

        $row->afterSave($isNewRow);

        foreach ($row->getFields() as $fieldKey => $field) {
            $field->layoutId = $row->layoutId;
            $field->pageId = $row->pageId;
            $field->rowId = $row->id;
            $field->sortOrder = $fieldKey;

            if (!$this->saveField($field, true, $context)) {
                // Bubble-up field errors with a deterministic field index path.
                ValidationHelper::addPrefixedErrors($row, $field->getErrors(), "fields.$fieldKey");

                return false;
            }
        }

        return true;
    }

    public function deleteRowById(int $id): bool
    {
        $row = $this->getRowById($id);

        if (!$row) {
            return false;
        }

        return $this->deleteRow($row);
    }
    
    public function deleteRow(FieldLayoutRow $row): bool
    {
        if (!$row->beforeDelete()) {
            return false;
        }

        Db::delete(Table::FORMIE_FIELD_LAYOUT_ROWS, ['id' => $row->id]);

        $row->afterDelete();

        return true;
    }

    public function saveField(Field $field, bool $updateSyncedFields = true, ?LayoutSaveContext $context = null): bool
    {
        if ($context === null) {
            $context = new LayoutSaveContext();
            if ($field->layoutId) {
                $context->includeLayout($field->layoutId);
            }
        }

        $context->assertField($field);
        $context->prepareFieldValidation($field);
        $isRootSave = $context->beginSave(FieldTraversal::recursively([$field]));
        $transaction = null;
        $transactionFinished = false;

        if ($isRootSave) {
            $this->_beginFieldSaveBatch();
        }

        $field->layoutSaveContext = $context;

        try {
            if ($isRootSave) {
                $transaction = Craft::$app->getDb()->beginTransaction();
            }

            $isNewField = !$field->id;
            $definitionId = $field->definitionId;
            $previousDefinitionId = $field->id ? ($context->fields[$field->id] ?? $definitionId) : null;
            $definitionRecord = $definitionId ? FieldDefinitionRecord::findOne($definitionId) : new FieldDefinitionRecord();
            $existingDefinitionUsageCount = $context->getDefinitionUsageCount($definitionId);

            if (!$definitionRecord) {
                throw new Exception('Invalid field definition ID: ' . $definitionId);
            }

            if ($definitionId && !($field instanceof formiefields\MissingField) && $definitionRecord->type !== $field->type) {
                if ($existingDefinitionUsageCount > 1) {
                    $field->addError('type', Craft::t('formie', 'Synced fields cannot change field type.'));

                    return false;
                }

                if (!$this->canChangeFieldType($definitionRecord->type, $field->type)) {
                    $field->addError('type', Craft::t('formie', 'This field type cannot be changed to the selected field type.'));

                    return false;
                }
            }

            if (!$field->beforeSave($isNewField)) {
                return false;
            }

            $context->refreshValidationField($field);

            if (!$field->validate()) {
                return false;
            }

            if ($definitionId && $existingDefinitionUsageCount > 1) {
                $field->handle = $definitionRecord->handle;
            }

            $skipSharedDefinitionUpdate = $definitionId && (
                isset($context->savedDefinitions[$definitionId])
                || ($existingDefinitionUsageCount > 1 && (!$updateSyncedFields || !$context->updateDefinitions))
            );

            if (!$skipSharedDefinitionUpdate) {
                $definitionRecord->id = $definitionId;
                $definitionRecord->label = $field->label;
                $definitionRecord->handle = $field->handle;
                $definitionRecord->type = $field->type;
                $definitionRecord->settings = Json::encode($field->getDefinitionSettings());

                // Check if this is a missing field, and swap back its type.
                // This can commonly happen during a migration, not really from normal use.
                if ($field instanceof formiefields\MissingField) {
                    $definitionRecord->type = $field->expectedType;
                    // Keep unavailable configuration inert until the registered type can validate its schema.
                    $definitionRecord->settings = Json::encode(['__formieMissingSettings' => $field->getSettings()]);
                }

                $definitionRecord->save(false);
                $context->savedDefinitions[$definitionRecord->id] = true;

                if (!$definitionId) {
                    $context->registerNewDefinition((int)$definitionRecord->id);
                }
            }

            $instanceRecord = $isNewField ? new FieldInstanceRecord() : FieldInstanceRecord::findOne($field->id);

            if (!$instanceRecord) {
                throw new Exception('Invalid form field ID: ' . $field->id);
            }

            $instanceRecord->id = $field->id;
            $instanceRecord->fieldId = $definitionRecord->id;
            $instanceRecord->layoutId = $field->layoutId;
            $instanceRecord->pageId = $field->pageId;
            $instanceRecord->rowId = $field->rowId;
            $instanceRecord->sortOrder = $field->sortOrder;
            $instanceRecord->reference = $field->reference ?: StringHelper::UUID();
            $instanceRecord->settings = Json::encode($field->getInstanceSettings());
            if ($field->uid) {
                $instanceRecord->uid = $field->uid;
            }
            $instanceRecord->save(false);

            $field->id = $instanceRecord->id;
            $field->definitionId = $definitionRecord->id;
            $field->definitionUid = $definitionRecord->uid;
            $field->uid = $instanceRecord->uid;
            $field->reference = $instanceRecord->reference;
            $context->recordSavedField($field, $previousDefinitionId);
            $this->_resetFieldCaches();

            if ($isRootSave) {
                $context->refreshSavedFieldUsage();
                $transaction->commit();
                $transactionFinished = true;
            }

            $field->afterSave($isNewField);

            return true;
        } finally {
            $field->layoutSaveContext = null;
            $context->endSave();

            if ($isRootSave) {
                if ($transaction && !$transactionFinished) {
                    $transaction->rollBack();
                    $transactionFinished = true;
                }

                $this->_endFieldSaveBatch();
            }
        }
    }
    
    public function deleteFieldById(int $id): bool
    {
        $field = $this->getFieldById($id);

        if (!$field) {
            return false;
        }

        return $this->deleteField($field);
    }
    
    public function deleteField(Field $field): bool
    {
        if (!$field->beforeDelete()) {
            return false;
        }

        Db::delete(Table::FORMIE_FORM_FIELDS, ['id' => $field->id]);

        if ($field->definitionId && !(new Query())->from(Table::FORMIE_FORM_FIELDS)->where(['fieldId' => $field->definitionId])->exists()) {
            $definitionField = $this->getFieldDefinitionById($field->definitionId);

            if ($definitionField) {
                // Nested layouts are definition-owned; metadata cleanup must not create a live field.
                    if ($nestedLayoutId = $definitionField->settings['nestedLayoutId'] ?? null) {
                        $this->deleteLayoutById((int)$nestedLayoutId);
                    }
            }

            Db::delete(Table::FORMIE_FIELDS, ['id' => $field->definitionId]);
        }

        $this->_resetFieldCaches();

        return true;
    }

    public function updateSyncedFields(Field $field): bool
    {
        return true;
    }

    public function checkRequiredPlugin(FieldInterface $field): bool
    {
        foreach ($field::getRequiredPlugins() as $requiredPlugin) {
            $version = $requiredPlugin['version'] ?? 0;
            $handle = $requiredPlugin['handle'] ?? '';

            if ($handle) {
                if (!Plugin::isPluginInstalledAndEnabled($handle)) {
                    throw new MissingComponentException();
                }

                $plugin = Craft::$app->getPlugins()->getPlugin($handle);

                if (version_compare($plugin->getVersion(), $version, '<')) {
                    throw new MissingComponentException();
                }
            }
        }

        return true;
    }

    public function getFieldOptions(FieldInterface $field, array $options = null): array
    {
        if (empty($options)) {
            return [];
        }

        /* @var Field $field */
        $allFieldOptions = $options['fields'] ?? [];
        $fieldOptions = $allFieldOptions[$field->handle] ?? [];

        if (isset($allFieldOptions['*'])) {
            $fieldOptions = ArrayHelper::merge($allFieldOptions['*'], $fieldOptions);
        }

        return $fieldOptions;
    }

    public function getLabelPositions(FieldInterface $field = null): array
    {
        $labelPositions = [
            AboveInput::class,
            BelowInput::class,
            LeftInput::class,
            RightInput::class,
            HiddenPosition::class,
        ];

        $event = new RegisterFieldOptionsEvent([
            'field' => $field,
            'options' => $labelPositions,
        ]);
        $this->trigger(self::EVENT_REGISTER_LABEL_POSITIONS, $event);

        if ($field) {
            $supportedPositions = [];

            foreach ($event->options as $class) {
                if ($class::supports($field)) {
                    $supportedPositions[] = $class;
                }
            }

            return $supportedPositions;
        }

        return $event->options;
    }

    public function getLabelPositionsOptions(FieldInterface $field = null): array
    {
        return array_map(function($class) {
            return [
                'label' => $class::displayName(),
                'value' => $class,
            ];
        }, $this->getLabelPositions($field));
    }

    public function getInstructionsPositions(FieldInterface $field = null): array
    {
        $instructionsPositions = [
            AboveInput::class,
            BelowInput::class,
        ];

        $event = new RegisterFieldOptionsEvent([
            'field' => $field,
            'options' => $instructionsPositions,
        ]);
        $this->trigger(self::EVENT_REGISTER_INSTRUCTIONS_POSITIONS, $event);

        if ($field) {
            $supportedPositions = [];

            foreach ($event->options as $class) {
                if ($class::supports($field)) {
                    $supportedPositions[] = $class;
                }
            }

            return $supportedPositions;
        }

        return $event->options;
    }

    public function getInstructionsPositionsOptions(FieldInterface $field = null): array
    {
        return array_map(function($class) {
            return [
                'label' => $class::displayName(),
                'value' => $class,
            ];
        }, $this->getInstructionsPositions($field));
    }

    public function getErrorMessagePositions(FieldInterface $field = null): array
    {
        $errorMessagePositions = [
            AboveInput::class,
            BelowInput::class,
        ];

        $event = new RegisterFieldOptionsEvent([
            'field' => $field,
            'options' => $errorMessagePositions,
        ]);
        $this->trigger(self::EVENT_REGISTER_ERROR_MESSAGE_POSITIONS, $event);

        if ($field) {
            $supportedPositions = [];

            foreach ($event->options as $class) {
                if ($class::supports($field)) {
                    $supportedPositions[] = $class;
                }
            }

            return $supportedPositions;
        }

        return $event->options;
    }

    public function getErrorMessagePositionsOptions(FieldInterface $field = null): array
    {
        return array_map(function($class) {
            return [
                'label' => $class::displayName(),
                'value' => $class,
            ];
        }, $this->getErrorMessagePositions($field));
    }

    public function getReservedHandles(): array
    {
        if ($this->_reservedHandles !== null) {
            return $this->_reservedHandles;
        }

        try {
            // Submission public properties are reserved field handles.
            $reflection = new ReflectionClass(Submission::class);

            $this->_reservedHandles = array_map(function($prop) {
                return $prop->name;
            }, $reflection->getProperties(ReflectionProperty::IS_PUBLIC));
        } catch (Throwable) {
            $this->_reservedHandles = [];
        }

        return $this->_reservedHandles;
    }

    public function resetFieldRegistryCache(): void
    {
        $this->_resetFieldCaches();
    }


    // Private Methods
    // =========================================================================

    private function _getLayoutQuerySelect(): array
    {
        return [
            'l.id as layoutId',
            'l.dateCreated as layoutDateCreated',
            'l.dateUpdated as layoutDateUpdated',
            'l.uid as layoutUid',
        ];
    }

    private function _getPageQuerySelect(): array
    {
        return [
            'p.id as pageId',
            'p.layoutId as pageLayoutId',
            'p.label as pageLabel',
            'p.sortOrder as pageSortOrder',
            'p.settings as pageSettings',
            'p.dateCreated as pageDateCreated',
            'p.dateUpdated as pageDateUpdated',
            'p.uid as pageUid',
        ];
    }

    private function _getRowQuerySelect(): array
    {
        return [
            'r.id as rowId',
            'r.layoutId as rowLayoutId',
            'r.pageId as rowPageId',
            'r.sortOrder as rowSortOrder',
            'r.dateCreated as rowDateCreated',
            'r.dateUpdated as rowDateUpdated',
            'r.uid as rowUid',
        ];
    }

    private function _getFieldQuerySelect(): array
    {
        return [
            'ff.id as formFieldId',
            'ff.fieldId as fieldDefinitionId',
            'f.uid as fieldDefinitionUid',
            'ff.layoutId as fieldLayoutId',
            'ff.pageId as fieldPageId',
            'ff.rowId as fieldRowId',
            'ff.settings as fieldFormSettings',
            'ff.reference as fieldReference',
            'ff.sortOrder as fieldSortOrder',
            'ff.dateCreated as fieldDateCreated',
            'ff.dateUpdated as fieldDateUpdated',
            'ff.uid as fieldUid',
            'f.label as fieldLabel',
            'f.handle as fieldHandle',
            'f.type as fieldType',
            'f.settings as fieldSettings',
            // Pull usage count into the main layout/row/page hydration query so we do not need a
            // second pass/query just to derive sync metadata for every populated field config.
            'COALESCE(usage.count, 1) as fieldUsageCount',
        ];
    }

    private function _getPopulatedPage(array $item): array
    {
        return [
            'id' => $item['pageId'],
            'layoutId' => $item['pageLayoutId'],
            'label' => $item['pageLabel'],
            'settings' => $item['pageSettings'],
            'sortOrder' => $item['pageSortOrder'],
            'dateCreated' => $item['pageDateCreated'],
            'dateUpdated' => $item['pageDateUpdated'],
            'uid' => $item['pageUid'],
            'rows' => [],
        ];
    }

    private function _getPopulatedRow(array $item): array
    {
        return [
            'id' => $item['rowId'],
            'layoutId' => $item['rowLayoutId'],
            'pageId' => $item['rowPageId'],
            'sortOrder' => $item['rowSortOrder'],
            'dateCreated' => $item['rowDateCreated'],
            'dateUpdated' => $item['rowDateUpdated'],
            'uid' => $item['rowUid'],
            'fields' => [],
        ];
    }

    private function _getPopulatedField(array $item): array
    {
        $usageCount = max((int)($item['fieldUsageCount'] ?? 1), 1);
        $formFieldSettings = Json::decodeIfJson($item['fieldFormSettings'] ?? null);

        return [
            'id' => $item['formFieldId'],
            'definitionId' => $item['fieldDefinitionId'],
            'definitionUid' => $item['fieldDefinitionUid'],
            'layoutId' => $item['fieldLayoutId'],
            'pageId' => $item['fieldPageId'],
            'rowId' => $item['fieldRowId'],
            'label' => $item['fieldLabel'],
            'handle' => $item['fieldHandle'],
            'reference' => $item['fieldReference'],
            'type' => $item['fieldType'],
            'settings' => $item['fieldSettings'],
            'instanceSettings' => is_array($formFieldSettings) ? $formFieldSettings : [],
            'required' => is_array($formFieldSettings) && array_key_exists('required', $formFieldSettings)
                ? (bool)$formFieldSettings['required']
                : null,
            'usageCount' => $usageCount,
            'isSynced' => $usageCount > 1,
            'sortOrder' => $item['fieldSortOrder'],
            'dateCreated' => $item['fieldDateCreated'],
            'dateUpdated' => $item['fieldDateUpdated'],
            'uid' => $item['fieldUid'],
        ];
    }

    private function _getLayout(array $params): ?FieldLayout
    {
        return array_values($this->_getLayouts($params))[0] ?? null;
    }

    private function _getLayouts(array $params): array
    {
        $layouts = [];
        $usageQuery = (new Query())
            ->select([
                'fieldId',
                'count' => 'COUNT(*)',
            ])
            ->from(Table::FORMIE_FORM_FIELDS)
            ->groupBy(['fieldId']);
        $usageQuery->andWhere(['fieldId' => (new Query())->select('ff.fieldId')
            ->from(['ff' => Table::FORMIE_FORM_FIELDS])
            ->innerJoin(['l' => Table::FORMIE_FIELD_LAYOUTS], '[[l.id]] = [[ff.layoutId]]')
            ->where($params)]);


        // Load the full layout/page/row/field graph in one flat result set, then rebuild the nested
        // model structure in PHP. This keeps the expensive hydration work batched even when the caller
        // needs many layouts at once.
        $dataItems = (new Query())
            ->select([
                ...$this->_getLayoutQuerySelect(),
                ...$this->_getPageQuerySelect(),
                ...$this->_getRowQuerySelect(),
                ...$this->_getFieldQuerySelect(),
            ])
            ->from(['l' => Table::FORMIE_FIELD_LAYOUTS])
            ->leftJoin(['p' => Table::FORMIE_FIELD_LAYOUT_PAGES], '[[p.layoutId]] = [[l.id]]')
            ->leftJoin(['r' => Table::FORMIE_FIELD_LAYOUT_ROWS], '[[r.pageId]] = [[p.id]]')
            ->leftJoin(['ff' => Table::FORMIE_FORM_FIELDS], '[[ff.rowId]] = [[r.id]]')
            ->leftJoin(['f' => Table::FORMIE_FIELDS], '[[f.id]] = [[ff.fieldId]]')
            ->leftJoin(['usage' => $usageQuery], '[[usage.fieldId]] = [[ff.fieldId]]')
            ->where($params)
            ->orderBy([
                'p.sortOrder' => SORT_ASC,
                'r.sortOrder' => SORT_ASC,
                'ff.sortOrder' => SORT_ASC,
            ])
            ->all();

        // While we could use the `sortOrder` for things, we don't want to rely on it. If something goes
        // wrong with it, we end up overwriting pages/rows/fields due to the same `sortOrder` value.
        if ($dataItems) {
            foreach ($dataItems as $item) {
                $layoutId = (int)($item['layoutId'] ?? 0);

                if (!$layoutId) {
                    continue;
                }

                if (!isset($layouts[$layoutId])) {
                    $layouts[$layoutId] = [
                        'layoutData' => [
                            'id' => $layoutId,
                            'dateCreated' => $item['layoutDateCreated'],
                            'dateUpdated' => $item['layoutDateUpdated'],
                            'uid' => $item['layoutUid'],
                        ],
                        'pages' => [],
                        'rows' => [],
                        'fields' => [],
                    ];
                }

                $pageId = $item['pageId'];
                $rowId = $item['rowId'];
                $fieldId = $item['formFieldId'];

                if ($pageId && !isset($layouts[$layoutId]['pages'][$pageId])) {
                    $layouts[$layoutId]['pages'][$pageId] = $this->_getPopulatedPage($item);
                }

                if ($rowId && !isset($layouts[$layoutId]['rows'][$rowId])) {
                    $layouts[$layoutId]['rows'][$rowId] = $this->_getPopulatedRow($item);
                }

                if ($fieldId && !isset($layouts[$layoutId]['fields'][$fieldId])) {
                    $layouts[$layoutId]['fields'][$fieldId] = $this->_getPopulatedField($item);
                }
            }
        }

        foreach ($layouts as $layoutId => $layoutParts) {
            $fields = $layoutParts['fields'];
            $rows = $layoutParts['rows'];
            $pages = $layoutParts['pages'];

            // Stitch pages/rows/fields together into a single nested array
            foreach ($fields as $field) {
                $rowId = $field['rowId'];

                if (isset($rows[$rowId])) {
                    $rows[$rowId]['fields'][] = $field;
                }
            }

            foreach ($rows as $row) {
                $pageId = $row['pageId'];

                if (isset($pages[$pageId])) {
                    $pages[$pageId]['rows'][] = $row;
                }
            }

            $layoutData = $layoutParts['layoutData'];
            $layoutData['pages'] = array_values($pages);

            $layouts[$layoutId] = new FieldLayout($layoutData);
        }

        return $layouts;
    }

    private function _getPage(array $params): ?FieldLayoutPage
    {
        $layoutData = [];
        $usageQuery = (new Query())
            ->select([
                'fieldId',
                'count' => 'COUNT(*)',
            ])
            ->from(Table::FORMIE_FORM_FIELDS)
            ->groupBy(['fieldId']);
        $usageQuery->andWhere(['fieldId' => (new Query())->select('ff.fieldId')
            ->from(['ff' => Table::FORMIE_FORM_FIELDS])
            ->innerJoin(['p' => Table::FORMIE_FIELD_LAYOUT_PAGES], '[[p.id]] = [[ff.pageId]]')
            ->where($params)]);


        // Do a single query here for everything, for performance, then cleanup due to lack of
        // MySQL being able to prefix tables nicely and get a nested structure.
        $dataItems = (new Query())
            ->select([
                ...$this->_getPageQuerySelect(),
                ...$this->_getRowQuerySelect(),
                ...$this->_getFieldQuerySelect(),
            ])
            ->from(['p' => Table::FORMIE_FIELD_LAYOUT_PAGES])
            ->leftJoin(['r' => Table::FORMIE_FIELD_LAYOUT_ROWS], '[[r.pageId]] = [[p.id]]')
            ->leftJoin(['ff' => Table::FORMIE_FORM_FIELDS], '[[ff.rowId]] = [[r.id]]')
            ->leftJoin(['f' => Table::FORMIE_FIELDS], '[[f.id]] = [[ff.fieldId]]')
            ->leftJoin(['usage' => $usageQuery], '[[usage.fieldId]] = [[ff.fieldId]]')
            ->where($params)
            ->orderBy([
                'p.sortOrder' => SORT_ASC,
                'r.sortOrder' => SORT_ASC,
                'ff.sortOrder' => SORT_ASC,
            ])
            ->all();

        $rows = [];
        $fields = [];

        // While we could use the `sortOrder` for things, we don't want to rely on it. If something goes
        // wrong with it, we end up overwriting pages/rows/fields due to the same `sortOrder` value.
        if ($dataItems) {
            foreach ($dataItems as $item) {
                $layoutData = $this->_getPopulatedPage($item);

                $rowId = $item['rowId'];
                $fieldId = $item['formFieldId'];

                if ($rowId && !isset($rows[$rowId])) {
                    $rows[$rowId] = $this->_getPopulatedRow($item);
                }

                if ($fieldId && !isset($fields[$fieldId])) {
                    $fields[$fieldId] = $this->_getPopulatedField($item);
                }
            }
        }

        // Stitch pages/rows/fields together into a single nested array
        foreach ($fields as $field) {
            $rowId = $field['rowId'];

            if (isset($rows[$rowId])) {
                $rows[$rowId]['fields'][] = $field;
            }
        }

        $layoutData['rows'] = array_values($rows);

        return $layoutData ? new FieldLayoutPage($layoutData) : null;
    }

    private function _getRow(array $params): ?FieldLayoutRow
    {
        $layoutData = [];
        $usageQuery = (new Query())
            ->select([
                'fieldId',
                'count' => 'COUNT(*)',
            ])
            ->from(Table::FORMIE_FORM_FIELDS)
            ->groupBy(['fieldId']);
        $usageQuery->andWhere(['fieldId' => (new Query())->select('ff.fieldId')
            ->from(['ff' => Table::FORMIE_FORM_FIELDS])
            ->innerJoin(['r' => Table::FORMIE_FIELD_LAYOUT_ROWS], '[[r.id]] = [[ff.rowId]]')
            ->where($params)]);


        // Do a single query here for everything, for performance, then cleanup due to lack of
        // MySQL being able to prefix tables nicely and get a nested structure.
        $dataItems = (new Query())
            ->select([
                ...$this->_getRowQuerySelect(),
                ...$this->_getFieldQuerySelect(),
            ])
            ->from(['r' => Table::FORMIE_FIELD_LAYOUT_ROWS])
            ->leftJoin(['ff' => Table::FORMIE_FORM_FIELDS], '[[ff.rowId]] = [[r.id]]')
            ->leftJoin(['f' => Table::FORMIE_FIELDS], '[[f.id]] = [[ff.fieldId]]')
            ->leftJoin(['usage' => $usageQuery], '[[usage.fieldId]] = [[ff.fieldId]]')
            ->where($params)
            ->orderBy([
                'r.sortOrder' => SORT_ASC,
                'ff.sortOrder' => SORT_ASC,
            ])
            ->all();

        $fields = [];

        // While we could use the `sortOrder` for things, we don't want to rely on it. If something goes
        // wrong with it, we end up overwriting pages/rows/fields due to the same `sortOrder` value.
        if ($dataItems) {
            foreach ($dataItems as $item) {
                $layoutData = $this->_getPopulatedRow($item);

                $fieldId = $item['formFieldId'];

                if ($fieldId && !isset($fields[$fieldId])) {
                    $fields[$fieldId] = $this->_getPopulatedField($item);
                }
            }
        }

        $layoutData['fields'] = array_values($fields);

        return $layoutData ? new FieldLayoutRow($layoutData) : null;
    }

    private function _deleteUnusedFieldDefinitions(): void
    {
        $unusedDefinitionIds = array_values(array_unique(array_filter(array_map('intval', (new Query())
            ->select(['f.id'])
            ->from(['f' => Table::FORMIE_FIELDS])
            ->leftJoin(['ff' => Table::FORMIE_FORM_FIELDS], '[[ff.fieldId]] = [[f.id]]')
            ->where(['ff.id' => null])
            ->column()))));

        if (!$unusedDefinitionIds) {
            return;
        }

        $definitionIdsToDelete = array_values(array_filter($unusedDefinitionIds, function(int $definitionId) {
            return !isset($this->_definitionIdsBeingDeleted[$definitionId]);
        }));

        if (!$definitionIdsToDelete) {
            return;
        }

        foreach ($definitionIdsToDelete as $definitionId) {
            $this->_definitionIdsBeingDeleted[$definitionId] = true;
        }

        try {
            foreach ($definitionIdsToDelete as $definitionId) {
                $definitionField = $this->getFieldDefinitionById($definitionId);

                if ($definitionField) {
                    // Nested layouts are definition-owned; metadata cleanup must not create a live field.
                    if ($nestedLayoutId = $definitionField->settings['nestedLayoutId'] ?? null) {
                        $this->deleteLayoutById((int)$nestedLayoutId);
                    }
                }
            }

            Db::delete(Table::FORMIE_FIELDS, ['id' => $definitionIdsToDelete]);
        } finally {
            foreach ($definitionIdsToDelete as $definitionId) {
                unset($this->_definitionIdsBeingDeleted[$definitionId]);
            }
        }
    }

    private function _cleanupDeletedLayoutItems(FieldLayout $layout): void
    {
        $layoutId = $layout->id;

        if (!$layoutId) {
            return;
        }

        $keptPageIds = [];
        $keptRowIds = [];
        $keptFieldIds = [];

        foreach ($layout->getPages() as $page) {
            if ($page->id) {
                $keptPageIds[] = $page->id;
            }

            foreach ($page->getRows() as $row) {
                if ($row->id) {
                    $keptRowIds[] = $row->id;
                }

                $this->_collectKeptLayoutFieldIds($row->getFields(), $keptFieldIds);
            }
        }

        $existingPageIds = [];
        $existingRowIds = [];
        $existingFieldIds = [];

        $rows = (new Query())
            ->select([
                'pageId' => 'p.id',
                'rowId' => 'r.id',
                'fieldId' => 'ff.id',
            ])
            ->from(['p' => Table::FORMIE_FIELD_LAYOUT_PAGES])
            ->leftJoin(['r' => Table::FORMIE_FIELD_LAYOUT_ROWS], '[[r.pageId]] = [[p.id]]')
            ->leftJoin(['ff' => Table::FORMIE_FORM_FIELDS], '[[ff.rowId]] = [[r.id]]')
            ->where(['p.layoutId' => $layoutId])
            ->all();

        foreach ($rows as $row) {
            if (!empty($row['pageId'])) {
                $existingPageIds[] = $row['pageId'];
            }

            if (!empty($row['rowId'])) {
                $existingRowIds[] = $row['rowId'];
            }

            if (!empty($row['fieldId'])) {
                $existingFieldIds[] = $row['fieldId'];
            }
        }

        $deletedFieldIds = array_values(array_filter(array_diff($existingFieldIds, $keptFieldIds)));
        $deletedRowIds = array_values(array_filter(array_diff($existingRowIds, $keptRowIds)));
        $deletedPageIds = array_values(array_filter(array_diff($existingPageIds, $keptPageIds)));

        foreach ($deletedFieldIds as $id) {
            if (!$this->deleteFieldById((int)$id)) {
                throw new Exception('Failed to delete field ID: ' . $id);
            }
        }

        foreach ($deletedRowIds as $id) {
            if (!$this->deleteRowById((int)$id)) {
                throw new Exception('Failed to delete row ID: ' . $id);
            }
        }

        foreach ($deletedPageIds as $id) {
            if (!$this->deletePageById((int)$id)) {
                throw new Exception('Failed to delete page ID: ' . $id);
            }
        }
    }

    private function _collectKeptLayoutFieldIds(array $fields, array &$keptFieldIds): void
    {
        foreach ($fields as $field) {
            if ($field->id) {
                $keptFieldIds[] = $field->id;
            }

            if (!($field instanceof ParentFieldInterface)) {
                continue;
            }

            foreach ($field->getRows() as $nestedRow) {
                $this->_collectKeptLayoutFieldIds($nestedRow->getFields(), $keptFieldIds);
            }
        }
    }

    private function _getResolvedRegisteredFieldTypes(bool $excludeDisabled = true): array
    {
        $cacheKey = $excludeDisabled ? 'exclude-disabled' : 'include-disabled';

        if (isset($this->_getFieldRegistryCache()->resolvedRegisteredFieldTypes[$cacheKey])) {
            return $this->_getFieldRegistryCache()->resolvedRegisteredFieldTypes[$cacheKey];
        }

        $fieldTypes = $this->getRegisteredFieldTypes(false);
        $event = new RegisterFieldsEvent([
            'fields' => $fieldTypes,
        ]);

        $this->trigger(self::EVENT_REGISTER_FIELDS, $event);

        // Missing Field cannot be removed
        $event->fields[] = formiefields\MissingField::class;
        foreach ($event->fields as $class) {
            if (!is_string($class) || !is_subclass_of($class, Field::class) || !(new ReflectionClass($class))->isInstantiable()) {
                throw new InvalidConfigException('Registered Formie field types must be concrete subclasses of ' . Field::class . ': ' . (is_string($class) ? $class : get_debug_type($class)));
            }
        }

        $resolvedFieldTypes = array_values(array_unique($event->fields));

        if ($excludeDisabled) {
            $fieldPalette = Formie::$plugin->getFieldPalette();
            $resolvedFieldTypes = array_values(array_filter($resolvedFieldTypes, function(string $class) use ($fieldPalette) {
                return $fieldPalette->isFieldClassEnabled($class);
            }));
        }

        $this->_getFieldRegistryCache()->resolvedRegisteredFieldTypes[$cacheKey] = $resolvedFieldTypes;

        return $this->_getFieldRegistryCache()->resolvedRegisteredFieldTypes[$cacheKey];
    }

    private function _getRegisteredFieldInstance(string $fieldClass): FieldInterface
    {
        return new $fieldClass();
    }

    private function _beginFieldSaveBatch(): void
    {
        $this->_fieldSaveBatchDepth++;
    }

    private function _endFieldSaveBatch(): void
    {
        $this->_fieldSaveBatchDepth = max(0, $this->_fieldSaveBatchDepth - 1);

        if ($this->_fieldSaveBatchDepth === 0 && $this->_fieldCachesDirty) {
            $this->_flushFieldCaches();
        }
    }

    private function _resetFieldCaches(): void
    {
        if ($this->_fieldSaveBatchDepth > 0) {
            $this->_fieldCachesDirty = true;

            return;
        }

        $this->_flushFieldCaches();
    }

    private function _flushFieldCaches(): void
    {
        $this->_fieldLookupCache?->reset();
        $this->_fieldRegistryCache?->reset();
        $this->_fieldGqlCache?->reset();
        SubmissionQuery::invalidateStaticCaches();
        $this->_fieldCachesDirty = false;
    }

    private function _getFieldConfigById(int $id): array
    {
        if (!$id) {
            return [];
        }

        if (!isset($this->_getFieldLookupCache()->fieldConfigById[$id])) {
            $this->_getFieldLookupCache()->fieldConfigById[$id] = $this->_normalizeFormFieldConfig($this->_createFormFieldConfigQuery()
                ->where(['ff.id' => $id])
                ->one() ?: []);
        }

        return $this->_getFieldLookupCache()->fieldConfigById[$id];
    }

    private function _getFieldConfigByReference(string $reference): array
    {
        if ($reference === '') {
            return [];
        }

        if (!isset($this->_getFieldLookupCache()->fieldConfigByReference[$reference])) {
            $fieldConfig = $this->_normalizeFormFieldConfig($this->_createFormFieldConfigQuery()
                ->where(['ff.reference' => $reference])
                ->one() ?: []);

            $this->_getFieldLookupCache()->fieldConfigByReference[$reference] = $fieldConfig;

            if ($fieldId = (int)($fieldConfig['id'] ?? 0)) {
                $this->_getFieldLookupCache()->fieldConfigById[$fieldId] = $fieldConfig;
            }
        }

        return $this->_getFieldLookupCache()->fieldConfigByReference[$reference];
    }

    private function _createFormFieldConfigQuery(): Query
    {
        $usageQuery = (new Query())
            ->select([
                'fieldId',
                'count' => 'COUNT(*)',
            ])
            ->from(Table::FORMIE_FORM_FIELDS)
            ->groupBy(['fieldId']);

        return (new Query())
            ->select([
                'ff.id',
                'ff.fieldId',
                'f.uid as definitionUid',
                'ff.layoutId',
                'ff.pageId',
                'ff.rowId',
                'ff.reference',
                'ff.sortOrder',
                'ff.dateCreated',
                'ff.dateUpdated',
                'ff.uid',
                'ff.settings as formFieldSettings',
                'f.label',
                'f.handle',
                'f.type',
                'f.settings',
                'COALESCE(usage.count, 1) as usageCount',
            ])
            ->from(['ff' => Table::FORMIE_FORM_FIELDS])
            ->innerJoin(['f' => Table::FORMIE_FIELDS], '[[f.id]] = [[ff.fieldId]]')
            ->leftJoin(['usage' => $usageQuery], '[[usage.fieldId]] = [[ff.fieldId]]');
    }

    private function _normalizeFormFieldConfig(array $fieldConfig): array
    {
        if (!$fieldConfig) {
            return [];
        }

        $formFieldSettings = Json::decodeIfJson($fieldConfig['formFieldSettings'] ?? null);
        $fieldConfig['instanceSettings'] = is_array($formFieldSettings) ? $formFieldSettings : [];

        if (is_array($formFieldSettings) && array_key_exists('required', $formFieldSettings)) {
            $fieldConfig['required'] = (bool)$formFieldSettings['required'];
        }

        $usageCount = max((int)($fieldConfig['usageCount'] ?? 1), 1);
        $fieldConfig['usageCount'] = $usageCount;
        $fieldConfig['isSynced'] = $usageCount > 1;
        $fieldConfig['definitionId'] = (int)($fieldConfig['fieldId'] ?? 0);
        unset($fieldConfig['fieldId']);

        unset($fieldConfig['formFieldSettings']);

        return $fieldConfig;
    }

    private function _sanitizeCompatibleFieldTypeConfig(array $fieldConfig, string $fromType, string $toType): array
    {
        $identityKeys = [
            'id',
            'definitionId',
            'definitionUid',
            'layoutId',
            'pageId',
            'rowId',
            'type',
            'label',
            'handle',
            'reference',
            'sortOrder',
            'dateCreated',
            'dateUpdated',
            'uid',
            'usageCount',
            'isSynced',
        ];

        $settingKeys = $this->_getCommonFieldSettingKeys($fromType, $toType);
        $preservedKeys = array_flip(array_merge($identityKeys, $settingKeys));
        $sanitizedConfig = array_intersect_key($fieldConfig, $preservedKeys);

        $settings = Json::decodeIfJson($fieldConfig['settings'] ?? null);

        if (is_array($settings)) {
            $sanitizedConfig['settings'] = array_intersect_key($settings, array_flip($settingKeys));
        }

        return $sanitizedConfig;
    }

    private function _getCommonFieldSettingKeys(string $fromType, string $toType): array
    {
        if (!is_subclass_of($fromType, Field::class) || !is_subclass_of($toType, Field::class)) {
            return [];
        }

        try {
            $fromField = new $fromType();
            $toField = new $toType();
        } catch (Throwable) {
            return [];
        }

        return array_values(array_intersect($fromField->settingsAttributes(), $toField->settingsAttributes()));
    }

    private function _normalizeNestedFieldConfig(array $fieldConfig): array
    {
        if (!$fieldConfig) {
            return [];
        }

        $settings = $fieldConfig['settings'] ?? [];
        $settings = Json::decodeIfJson($settings);
        $settings = is_array($settings) ? $settings : [];

        if (isset($fieldConfig['rows']) && !isset($settings['rows'])) {
            $settings['rows'] = $fieldConfig['rows'];
        }

        $fieldConfig['settings'] = $settings;

        if (array_key_exists('required', $fieldConfig)) {
            $fieldConfig['required'] = (bool)$fieldConfig['required'];
        }

        if (array_key_exists('enabled', $fieldConfig)) {
            $fieldConfig['enabled'] = (bool)$fieldConfig['enabled'];
        }

        return $fieldConfig;
    }

    private function _hydrateCachedFields(array &$fields): array
    {
        foreach ($fields as $index => $field) {
            if ($field instanceof FieldInterface) {
                continue;
            }

            $fields[$index] = $this->hydrateField($field);
        }

        return $fields;
    }

    private function _getGqlFieldCacheKey(FieldInterface $field): string
    {
        $fieldId = (int)($field->id ?? 0);

        if ($fieldId) {
            return get_class($field) . ':' . $fieldId;
        }

        return get_class($field) . ':obj:' . spl_object_id($field);
    }

    private function _getGqlFieldConfigCacheKey(array $fieldConfig): string
    {
        $fieldId = (int)($fieldConfig['id'] ?? 0);
        $fieldType = (string)($fieldConfig['type'] ?? '');

        if ($fieldId) {
            return $fieldType . ':' . $fieldId;
        }

        $reference = (string)($fieldConfig['reference'] ?? '');

        if ($reference !== '') {
            return $fieldType . ':ref:' . $reference;
        }

        return $fieldType . ':cfg:' . hash('sha256', Json::encode([
            'handle' => $fieldConfig['handle'] ?? '',
            'instructions' => $fieldConfig['instructions'] ?? '',
            'settings' => $fieldConfig['settings'] ?? null,
        ]));
    }

    private function _getFieldConfigSettingsCacheKey(array $fieldConfig): string
    {
        $fieldId = (int)($fieldConfig['id'] ?? 0);

        if ($fieldId) {
            return 'id:' . $fieldId;
        }

        $reference = (string)($fieldConfig['reference'] ?? '');

        if ($reference !== '') {
            return 'ref:' . $reference;
        }

        return 'cfg:' . hash('sha256', Json::encode([
            'type' => $fieldConfig['type'] ?? '',
            'handle' => $fieldConfig['handle'] ?? '',
            'settings' => $fieldConfig['settings'] ?? null,
        ]));
    }

    private function _getGqlFieldConfigProvider(array $fieldConfig): ?string
    {
        $fieldType = $fieldConfig['type'] ?? null;

        if (!is_string($fieldType) || $fieldType === '' || !class_exists($fieldType)) {
            return null;
        }

        if (!is_subclass_of($fieldType, Field::class)) {
            return null;
        }

        return $fieldType::supportsGqlConfigProvider() ? $fieldType : null;
    }

    private function _getFieldLookupCache(): FieldLookupCache
    {
        if ($this->_fieldLookupCache === null) {
            $this->_fieldLookupCache = new FieldLookupCache();
        }

        return $this->_fieldLookupCache;
    }

    private function _getFieldRegistryCache(): FieldRegistryCache
    {
        if ($this->_fieldRegistryCache === null) {
            $this->_fieldRegistryCache = new FieldRegistryCache();
        }

        return $this->_fieldRegistryCache;
    }

    private function _getFieldGqlCache(): FieldGqlCache
    {
        if ($this->_fieldGqlCache === null) {
            $this->_fieldGqlCache = new FieldGqlCache();
        }

        return $this->_fieldGqlCache;
    }

    private function _getFieldTypeLabel(string $type): string
    {
        if ($type === '') {
            return '';
        }

        try {
            $field = $this->hydrateField(['type' => $type]);

            if ($field instanceof Field) {
                return (string)$field::displayName();
            }
        } catch (Throwable) {
            // Fall back to the stored class name when the field type is unavailable.
        }

        return $type;
    }

}
