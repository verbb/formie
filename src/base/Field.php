<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\FieldElementEvent;
use verbb\formie\events\ModifyFieldConfigEvent;
use verbb\formie\events\ModifyFieldEmailValueEvent;
use verbb\formie\events\ModifyFieldIntegrationValueEvent;
use verbb\formie\events\ModifyFieldSchemaEvent;
use verbb\formie\events\ModifyFieldUniqueQueryEvent;
use verbb\formie\events\ModifyFieldValueEvent;
use verbb\formie\deprecations\FieldDeprecations;
use verbb\formie\fields;
use verbb\formie\fields\coercion\EmptyValueCoercer;
use verbb\formie\fields\values\FieldValueInterface;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\helpers\FileHelper;
use verbb\formie\helpers\Html;
use verbb\formie\helpers\References;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\Variables;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\FieldLayoutRow;
use verbb\formie\models\FieldPath;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;
use verbb\formie\models\Settings;
use verbb\formie\positions\Hidden as HiddenPosition;
use verbb\formie\query\FieldValueQueryHelper;
use verbb\formie\records\FormField as FormFieldRecord;
use verbb\formie\validators\HandleValidator;
use verbb\formie\validators\LayoutHandleUniqueValidator;

use Craft;
use craft\base\ElementInterface;
use craft\base\SavableComponent;
use craft\db\Query;
use craft\elements\db\ElementQueryInterface;
use craft\fieldlayoutelements\CustomField;
use craft\fields\BaseRelationField;
use craft\gql\types\DateTime as DateTimeType;
use craft\gql\types\QueryArgument;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\Html as CraftHtml;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\models\GqlSchema;
use craft\validators\UniqueValidator;
use craft\web\View;

use GraphQL\Type\Definition\Type;

use Faker\Generator as FakerFactory;

use Twig\Markup;

use Arrayable;
use DateTime;
use ReflectionException;
use ReflectionNamedType;
use ReflectionUnionType;
use Serializable;

use yii\db\ExpressionInterface;
use yii\db\Schema;

abstract class Field extends SavableComponent implements FieldInterface, SearchableFieldInterface
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_ELEMENT_SAVE = 'beforeElementSave';
    public const EVENT_AFTER_ELEMENT_SAVE = 'afterElementSave';
    public const EVENT_AFTER_ELEMENT_PROPAGATE = 'afterElementPropagate';
    public const EVENT_BEFORE_ELEMENT_DELETE = 'beforeElementDelete';
    public const EVENT_AFTER_ELEMENT_DELETE = 'afterElementDelete';
    public const EVENT_BEFORE_ELEMENT_RESTORE = 'beforeElementRestore';
    public const EVENT_AFTER_ELEMENT_RESTORE = 'afterElementRestore';

    public const EVENT_MODIFY_DEFAULT_VALUE = 'modifyDefaultValue';
    public const EVENT_MODIFY_FIELD_CONFIG = 'modifyFieldConfig';
    public const EVENT_MODIFY_SLOT_TAG = 'modifySlotTag';
    public const EVENT_MODIFY_HTML_TAG = 'modifyHtmlTag';
    public const EVENT_MODIFY_VALUE_AS_STRING = 'modifyValueAsString';
    public const EVENT_MODIFY_VALUE_AS_ARRAY = 'modifyValueAsArray';
    public const EVENT_MODIFY_VALUE_FOR_EXPORT = 'modifyValueForExport';
    public const EVENT_MODIFY_VALUE_FOR_INTEGRATION = 'modifyValueForIntegration';
    public const EVENT_MODIFY_VALUE_FOR_REFERENCE = 'modifyValueForReference';
    public const EVENT_MODIFY_VALUE_FOR_REFERENCE_BLOCK = 'modifyValueForReferenceBlock';
    public const EVENT_MODIFY_VALUE_FOR_SUMMARY = 'modifyValueForSummary';
    public const EVENT_MODIFY_VALUE_FOR_EMAIL_PREVIEW = 'modifyValueForEmailPreview';
    public const EVENT_MODIFY_UNIQUE_QUERY = 'modifyUniqueQuery';
    public const EVENT_MODIFY_FIELD_SCHEMA = 'modifyFieldSchema';

    public const TRANSLATION_METHOD_NONE = 'none';
    public const TRANSLATION_METHOD_SITE = 'site';
    public const TRANSLATION_METHOD_SITE_GROUP = 'siteGroup';
    public const TRANSLATION_METHOD_LANGUAGE = 'language';
    public const TRANSLATION_METHOD_CUSTOM = 'custom';

    public const KIND_CUSTOM = 'custom';
    public const KIND_TEXT = 'text';
    public const KIND_TEXTAREA = 'textarea';
    public const KIND_BOOLEAN = 'boolean';
    public const KIND_PHONE = 'phone';
    public const KIND_FILE = 'file';
    public const KIND_HIDDEN = 'hidden';
    public const KIND_DATE = 'date';
    public const KIND_ADDRESS = 'address';
    public const KIND_NAME = 'name';
    public const KIND_PAYMENT = 'payment';
    public const KIND_REPEATER = 'repeater';
    public const KIND_TABLE = 'table';
    public const KIND_SIGNATURE = 'signature';
    public const KIND_SELECT = 'select';
    public const KIND_RADIO_GROUP = 'radio-group';
    public const KIND_CHECKBOX_GROUP = 'checkbox-group';

    // Deprecated
    public const EVENT_MODIFY_VALUE_AS_JSON = 'modifyValueAsJson';
    public const EVENT_MODIFY_VALUE_FOR_EMAIL = 'modifyValueForEmail';


    // Traits
    // =========================================================================

    use FieldDeprecations;
    use FieldDefinitionTrait;
    use FieldClientValidationTrait;
    use FieldClientConditionTrait;
    use FieldClientDefinitionTrait;
    use FieldServerRenderTrait;
    use FieldValueTrait;
    use FieldFormBuilderTrait;
    use FieldSubmissionTrait;


    // Static Methods
    // =========================================================================

    public static function className(): string
    {
        $classNameParts = explode('\\', static::class);

        return array_pop($classNameParts);
    }

    public static function kebabClassName(): string
    {
        return StringHelper::toKebabCase(static::className());
    }

    public static function lowerClassName(): string
    {
        return StringHelper::toLowerCase(static::className());
    }

    public static function phpType(): string
    {
        return 'mixed';
    }

    public static function dbType(): array|string|null
    {
        return Schema::TYPE_TEXT;
    }

    public static function queryCondition(array $instances, mixed $value, array &$params): array|string|ExpressionInterface|false|null
    {
        return FieldValueQueryHelper::buildQueryCondition($instances, $value, $params);
    }

    public static function getReferenceBlockTemplatePath(): string
    {
        if (static::_hasLegacyStaticMethodOverride('getEmailTemplatePath')) {
            return static::getEmailTemplatePath();
        }

        return static::_getDefaultReferenceBlockTemplatePath();
    }

    public static function defineFieldType(): array
    {
        $icon = static::getSvgIcon();

        $isParentField = is_subclass_of(static::class, ParentFieldInterface::class);
        $isFixedParentField = is_subclass_of(static::class, FixedParentFieldInterface::class);
        $isChildField = is_subclass_of(static::class, ChildFieldInterface::class);
        $isCosmetic = is_subclass_of(static::class, CosmeticFieldInterface::class);

        return [
            'icon' => $icon,
            'type' => static::class,
            'label' => static::displayName(),
            'hasLabel' => !$isCosmetic,
            'hasConditions' => false,
            'isCosmetic' => $isCosmetic,
            'isSynced' => false,
            'isParentField' => $isParentField,
            'isFixedParentField' => $isFixedParentField,
            'isContainerParentField' => is_subclass_of(static::class, ContainerParentFieldInterface::class),
            'isRepeatableParentField' => is_subclass_of(static::class, RepeatableParentFieldInterface::class),
            'isChildField' => $isChildField,
            'hasEditableFields' => !($isParentField && !$isFixedParentField),
            'isPickable' => !$isChildField,
        ];
    }

    public static function getFieldTypeDefinition(): array
    {
        if (isset(self::$_fieldTypeDefinitionCache[static::class])) {
            return self::$_fieldTypeDefinitionCache[static::class];
        }

        // Builder/type metadata is pure static configuration, so cache it once
        // per concrete field class rather than re-deriving it during every
        // builder boot or field registry lookup.
        self::$_fieldTypeDefinitionCache[static::class] = static::defineFieldType();

        return self::$_fieldTypeDefinitionCache[static::class];
    }

    public static function getSvgIcon(): string
    {
        $iconPath = static::getSvgIconPath();

        if ($iconPath === '') {
            return '';
        }

        if (array_key_exists(static::class, self::$_svgIconCache)) {
            return self::$_svgIconCache[static::class];
        }

        // Fast-path static SVG templates, while allowing custom module template paths.
        $svg = FileHelper::readTemplateContents($iconPath, View::TEMPLATE_MODE_CP, __METHOD__);

        if ($svg !== null) {
            self::$_svgIconCache[static::class] = $svg;

            return self::$_svgIconCache[static::class];
        }

        self::$_svgIconCache[static::class] = Craft::$app->getView()->renderTemplate($iconPath);

        return self::$_svgIconCache[static::class];
    }
    
    public static function getSvgIconPath(): string
    {
        return '';
    }

    public static function getInputTemplatePath(): string
    {
        return 'fields/' . static::kebabClassName();
    }

    public static function getRequiredPlugins(): array
    {
        return [];
    }

    public static function supportsGqlConfigProvider(): bool
    {
        return false;
    }

    public static function gqlIncludeInSchemaFromConfig(array $config, GqlSchema $schema): bool
    {
        return true;
    }

    public static function gqlContentTypeFromConfig(array $config): Type|array
    {
        return Type::string();
    }

    public static function gqlContentMutationArgumentTypeFromConfig(array $config): Type|array
    {
        return [
            'name' => $config['handle'] ?? '',
            'type' => Type::string(),
            'description' => $config['instructions'] ?? null,
        ];
    }

    public static function gqlContentQueryArgumentTypeFromConfig(array $config): Type|array
    {
        return [
            'name' => $config['handle'] ?? '',
            'type' => Type::listOf(QueryArgument::getType()),
        ];
    }

    protected static function valueSql(array $instances, string $key = null): ?string
    {
        return FieldValueQueryHelper::resolveCoalescedValueSql($instances, $key);
    }

    protected static function valueColumnType(array $instances, string $key = null): ?string
    {
        return FieldValueQueryHelper::resolveCoalescedColumnType($instances, $key);
    }
    

    // Properties
    // =========================================================================

    private static array $_fieldTypeDefinitionCache = [];
    private static array $_svgIconCache = [];
    private static array $_previewTemplateCache = [];

    public ?int $layoutId = null;
    public ?int $pageId = null;
    public ?int $rowId = null;
    public ?int $fieldId = null;
    public ?int $syncId = null;
    public ?int $usageCount = null;
    public ?string $label = null;
    public ?string $handle = null;
    public ?string $reference = null;
    public ?int $sortOrder = null;
    public ?DateTime $dateCreated = null;
    public ?DateTime $dateUpdated = null;
    public ?string $uid = null;
    public bool $isSynced = false;

    public ?string $instructions = null;
    public bool $required = false;
    public bool $enabled = true;
    public ?string $matchField = null;
    public ?string $placeholder = null;
    public mixed $defaultValue = null;
    public ?string $prePopulate = null;
    public ?string $errorMessage = null;
    public ?string $labelPosition = null;
    public ?string $instructionsPosition = null;
    public ?string $cssClasses = null;
    public ?array $containerAttributes = null;
    public ?array $inputAttributes = null;
    public bool $includeInEmailFieldSummaries = true;
    public ?string $emailFieldSummaryValue = null;
    public bool $enableConditions = false;
    public ?array $conditions = null;
    public bool $enableContentEncryption = false;
    public ?string $visibility = null;

    private ?Form $_form = null;
    private ?FieldLayout $_layout = null;
    private ?FieldLayoutPage $_page = null;
    private ?FieldLayoutRow $_row = null;
    private array $_themeConfig = [];
    private ?FieldInterface $_parentField = null;
    private string $_namespace = 'fields';
    private ?FieldPath $_fieldPath = null;
    private ?bool $_isFresh = null;
    private array $_valueSql = [];
    private array $_valueColumnType = [];
    private bool $_hasPopulatedValue = false;
    private mixed $_populatedValue = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Config normalization
        self::normalizeConfig($config);

        Formie::$plugin?->getFormDefaults()?->applyToNewField($config, static::class, $this->getSupportedDefaults());

        parent::__construct($config);
    }

    public function settingsAttributes(): array
    {
        $names = parent::settingsAttributes();
        $names[] = 'instructions';
        $names[] = 'enabled';
        $names[] = 'required';
        $names[] = 'matchField';
        $names[] = 'placeholder';
        $names[] = 'defaultValue';
        $names[] = 'prePopulate';
        $names[] = 'errorMessage';
        $names[] = 'labelPosition';
        $names[] = 'instructionsPosition';
        $names[] = 'cssClasses';
        $names[] = 'containerAttributes';
        $names[] = 'inputAttributes';
        $names[] = 'includeInEmailFieldSummaries';
        $names[] = 'emailFieldSummaryValue';
        $names[] = 'enableConditions';
        $names[] = 'conditions';
        $names[] = 'enableContentEncryption';
        $names[] = 'visibility';

        return $names;
    }

    public function getType(): string
    {
        return static::class;
    }

    public function themeConfigKey(): string
    {
        return StringHelper::toCamelCase(StringHelper::toKebabCase(static::className()));
    }

    public function getDisplayType(): ?string
    {
        if (property_exists($this, 'displayType')) {
            return $this->displayType;
        }

        return null;
    }

    public function hasLabel(): bool
    {
        return true;
    }

    public function getIsCosmetic(): bool
    {
        return false;
    }

    public function hasReferenceBlockLabel(): bool
    {
        if ($this->_hasLegacyFieldMethodOverride('hasEmailLabel')) {
            return $this->hasEmailLabel();
        }

        return true;
    }

    public function hasReferenceBlockPlaceholder(): bool
    {
        if ($this->_hasLegacyFieldMethodOverride('hasEmailPlaceholder')) {
            return $this->hasEmailPlaceholder();
        }

        return true;
    }

    public function getIsHidden(): bool
    {
        return $this->visibility === 'hidden';
    }

    public function getIsDisabled(): bool
    {
        return !$this->enabled || $this->visibility === 'disabled';
    }

    public function getIsRequired(): ?bool
    {
        return $this->required;
    }

    public function getIsNested(): bool
    {
        return (bool)$this->getParentField();
    }

    public function setIsFresh(?bool $isFresh = null): void
    {
        $this->_isFresh = $isFresh;
    }

    public function getForm(): ?Form
    {
        if ($this->_form || !$this->layoutId) {
            return $this->_form;
        }

        if ($parentField = $this->getParentField()) {
            return $this->_form = Formie::$plugin->getForms()->getFormByLayoutId($parentField->layoutId);
        }

        return $this->_form = Formie::$plugin->getForms()->getFormByLayoutId($this->layoutId);
    }

    public function getLayout(): ?FieldLayout
    {
        if ($this->_layout || !$this->layoutId) {
            return $this->_layout;
        }

        return $this->_layout = Formie::$plugin->getFields()->getLayoutById($this->layoutId);
    }

    public function getPage(): ?FieldLayoutPage
    {
        if ($this->_page || !$this->pageId) {
            return $this->_page;
        }

        return $this->_page = Formie::$plugin->getFields()->getPageById($this->pageId);
    }

    public function getRow(): ?FieldLayoutRow
    {
        if ($this->_row || !$this->rowId) {
            return $this->_row;
        }

        return $this->_row = Formie::$plugin->getFields()->getRowById($this->rowId);
    }

    public function modifyAttributeLabels(array &$labels): void
    {
    }

    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->normalizeValue($value, $element);
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if (is_string($value)) {
            if ($this->enableContentEncryption || str_contains($value, 'base64:')) {
                $value = StringHelper::decdec($value);
            }

            // Preserve plain-text field values and only normalize invalid control characters.
            $value = StringHelper::normalizePlainText($value);
            $value = $this->sanitizePlainTextValueIfConfigured($value);
        }

        return $value;
    }
    public function serializeValueForDb(mixed $value, ElementInterface $element): mixed
    {
        return $this->serializeValue($value, $element);
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value instanceof Serializable) {
            // If the object explicitly defines its savable value, use that
            $value = $value->serialize();
        } else if ($value instanceof Arrayable) {
            // If it's "arrayable", convert to array
            $value = $value->toArray();
        } else if ($value instanceof DateTime || DateTimeHelper::isIso8601($value)) {
            // Only DateTime objects and ISO-8601 strings should automatically be detected as dates
            $value = Db::prepareDateForDb($value);
        }

        // Handle if we need to save field content as encrypted
        if ($this->enableContentEncryption && is_string($value)) {
            $value = StringHelper::encenc($value);
        }

        return $value;
    }

    public function isValueEmpty(mixed $value, ?ElementInterface $element): bool
    {
        return EmptyValueCoercer::isEmpty($value);
    }

    public function getElementConditionRuleType(): array|string|null
    {
        return null;
    }

    public function supportsValueCapability(string $capabilityType): bool
    {
        return $this->valueClass()->supportsCapability($capabilityType);
    }

    public function supportsStringValue(): bool
    {
        return $this->supportsValueCapability('string');
    }

    public function supportsArrayValue(): bool
    {
        return $this->supportsValueCapability('array');
    }

    public function getValueSql(?string $key = null): ?string
    {
        if (!$this->uid) {
            return null;
        }

        $cacheKey = $key ?? '*';
        $this->_valueSql[$cacheKey] ??= $this->_valueSql($key) ?? false;

        return $this->_valueSql[$cacheKey] ?: null;
    }

    public function getValueColumnType(?string $key = null): ?string
    {
        if (!$this->uid) {
            return null;
        }

        $cacheKey = $key ?? '*';
        $this->_valueColumnType[$cacheKey] ??= $this->_valueColumnType($key) ?? false;

        return $this->_valueColumnType[$cacheKey] ?: null;
    }

    public function getSortOption(): array
    {
        return [
            'label' => Craft::t('site', $this->label),
            'orderBy' => [$this->getValueSql(), 'elements.id'],
            'attribute' => "field:{$this->uid}",
        ];
    }

    public function populateValue(mixed $value, ?Submission $submission): void
    {
        $this->_populatedValue = $this->normalizeValue($value, $submission);
        $this->_hasPopulatedValue = true;
    }

    public function getMatchField(): ?string
    {
        if (!$this->matchField) {
            return null;
        }

        $matchField = trim($this->matchField);
        $expression = References::parseReferenceExpression($matchField);

        // Match-field values are canonical reference tokens: `{field:<reference>}`.
        if (!$expression->isValid || $expression->target !== 'field' || $expression->identifier === '') {
            return null;
        }

        $resolvedMatchField = Formie::$plugin->getFields()->getFieldByReference($expression->identifier);

        return $resolvedMatchField?->handle ?? null;
    }

    public function getElementValidationRules(): array
    {
        $rules = [];

        if ($matchField = $this->getMatchField()) {
            $rules[] = [$this->handle, 'validateMatchField', 'skipOnEmpty' => false];
        }

        return $rules;
    }

    public function validateMatchField(ElementInterface $element): void
    {
        $fieldHandle = $this->getMatchField();

        if (!$fieldHandle) {
            return;
        }

        $sourceValue = $element->getFieldValue($fieldHandle);
        $value = $element->getFieldValue($this->valueKey());

        if ($sourceValue !== $value) {
            $sourceField = $element->getFieldByHandle($fieldHandle);

            $element->addError($this->valueKey(), Craft::t('formie', '{name} must match {value}.', [
                'name' => $this->label,
                'value' => $sourceField->label ?? '',
            ]));
        }
    }

    public function validateUniqueValue(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->valueKey());
        $value = trim($value);

        // Use a DB lookup for performance
        $contentQuery = Craft::$app->getDb()->getQueryBuilder()->jsonContains('s.content', [$this->uid => $value]);

        $query = (new Query())
            ->from(['s' => Table::FORMIE_SUBMISSIONS])
            ->where(['isIncomplete' => false, 'e.dateDeleted' => null])
            ->andWhere($contentQuery)
            ->leftJoin(['e' => Table::ELEMENTS], '[[e.id]] = [[s.id]]');

        // Exclude _this_ element, if there is one
        if ($element->id) {
            $query->andWhere(['!=', 's.id', $element->id]);
        }

        $event = new ModifyFieldUniqueQueryEvent([
            'query' => $query,
            'field' => $this,
        ]);

        // Fire a 'modifyFieldUniqueQuery' event
        $this->trigger(self::EVENT_MODIFY_UNIQUE_QUERY, $event);

        // Be sure to check only against completed submission content
        $valueExists = $event->query->exists();

        if ($valueExists) {
            $element->addError($this->valueKey(), Craft::t('formie', '“{name}” must be unique.', [
                'name' => $this->label,
            ]));
        }
    }

    public function getNamespace(): string
    {
        return $this->_namespace;
    }

    public function setNamespace(string|bool|null $value): void
    {
        $this->_namespace = (string)$value;
        $this->_fieldPath = null;
    }

    public function getParentField(): ?FieldInterface
    {
        return $this->_parentField;
    }

    public function withParentField(FieldInterface $parent, string|int|null $namespace = null): static
    {
        $field = clone $this;
        $field->applyParentFieldContext($parent, $namespace === null ? '' : (string)$namespace);

        return $field;
    }

    public function getFieldPath(): FieldPath
    {
        return $this->_fieldPath ??= FieldPath::fromField($this);
    }

    public function valueKey(): string
    {
        return $this->getFieldPath()->valueKey();
    }

    public function errorKey(): string
    {
        return $this->getFieldPath()->errorKey();
    }

    public function handlePath(): array
    {
        return $this->getFieldPath()->handlePath();
    }

    public function namespacePath(): array
    {
        return $this->getFieldPath()->namespacePath();
    }

    public function getDefaultValue(): mixed
    {
        $defaultValue = $this->normalizeValue($this->defaultValue, null);

        $event = new ModifyFieldValueEvent([
            'value' => $defaultValue,
            'field' => $this,
        ]);

        $this->trigger(static::EVENT_MODIFY_DEFAULT_VALUE, $event);

        if (is_string($event->value)) {
            $event->value = trim($event->value);
        }

        return $event->value;
    }

    public function getPrefillValue(?ElementInterface $element = null, ?bool &$found = null): mixed
    {
        if ($this->_hasPopulatedValue) {
            $found = true;

            return $this->_populatedValue;
        }

        if ($this->prePopulate) {
            $queryParam = Craft::$app->getRequest()->getParam($this->prePopulate);

            if ($queryParam !== null) {
                $found = true;

                $prefillValue = $this->normalizeValue($this->setPrePopulatedValue($queryParam), $element);

                if (is_string($prefillValue)) {
                    $prefillValue = trim($prefillValue);
                }

                return $prefillValue;
            }
        }

        $found = false;

        return null;
    }

    public function getInitialValue(?ElementInterface $element = null): mixed
    {
        $prefillValue = $this->getPrefillValue($element, $found);

        if ($found) {
            return $prefillValue;
        }

        return $this->getDefaultValue();
    }

    public function getIsSynced(): bool
    {
        return $this->isSynced || (($this->usageCount ?? 1) > 1);
    }

    public function getDefinitionSettings(): array
    {
        $settings = $this->getSettings();
        unset($settings['required']);

        return $settings;
    }

    public function getFormFieldSettings(): array
    {
        return [
            'required' => $this->required,
        ];
    }

    public function applyFormFieldSettings(array|string|null $settings): void
    {
        $settings = is_string($settings) ? Json::decodeIfJson($settings) : $settings;

        if (!is_array($settings)) {
            return;
        }

        if (array_key_exists('required', $settings)) {
            $this->required = (bool)$settings['required'];
        }
    }

    public function hasConditions(): bool
    {
        return ($this->enableConditions && $this->getConditions());
    }

    public function isConditionallyHidden(Submission $submission): bool
    {
        $isFieldHidden = false;
        $isPageHidden = false;

        // Check if the field itself is hidden
        if ($this->enableConditions) {
            $conditionSettings = $this->getConditions();
            $conditions = $conditionSettings['conditions'] ?? [];

            if ($conditionSettings && $conditions) {
                // A `true` result means the field passed the evaluation and that it has a value, whilst a `false` result means
                // it didn't (for instance the field doesn't have a value)
                $result = ConditionsHelper::getConditionalTestResult($conditionSettings, $submission);

                // Depending on if we show or hide the field when evaluating. If `false` and set to show, it means
                // the field is hidden and the conditions to show it isn't met. Therefore, report back that this field is hidden.
                if (($result && $conditionSettings['showRule'] !== 'show') || (!$result && $conditionSettings['showRule'] === 'show')) {
                    $isFieldHidden = true;
                }
            }
        }

        // Also check if the field is in a hidden page
        if (!$isFieldHidden && $page = $this->getPage($submission)) {
            $isPageHidden = $page->isConditionallyHidden($submission);
        }

        return $isFieldHidden || $isPageHidden;
    }

    public function getReferenceBlockHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool
    {
        if ($this->_hasLegacyFieldMethodOverride('getEmailHtml')) {
            return $this->getEmailHtml($submission, $notification, $value, $renderOptions);
        }

        return $this->_renderReferenceBlockHtml($submission, $notification, $value, $renderOptions);
    }

    public function getReferenceBlockOptions(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): array
    {
        if ($this->_hasLegacyFieldMethodOverride('getEmailOptions')) {
            return $this->getEmailOptions($submission, $notification, $value, $renderOptions);
        }

        return $this->_buildReferenceBlockOptions($submission, $notification, $value, $renderOptions);
    }
    
    public function includeInGqlSchema(GqlSchema $schema): bool
    {
        return true;
    }

    public function getSettingGqlTypes(): array
    {
        return [];
    }

    public function getGqlTypeName(): string
    {
        $classNameParts = explode('\\', static::class);
        $end = array_pop($classNameParts);

        return 'Field_' . $end;
    }

    public function getContentGqlType(): Type|array
    {
        return Type::string();
    }

    public function getContentGqlMutationArgumentType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => Type::string(),
            'description' => $this->instructions,
        ];
    }

    public function getContentGqlQueryArgumentType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => Type::listOf(QueryArgument::getType()),
        ];
    }

    public function getExportLabel(ElementInterface $element): string
    {
        // Check to see if there's another field with the same label
        foreach ($element->getFields() as $field) {
            if ($field->id === $this->id) {
                continue;
            }

            if ($field->label === $this->label) {
                return $this->label . ' (' . $this->handle . ')';
            }
        }

        return $this->label;
    }

    public function getSearchKeywords(mixed $value, ElementInterface $element): string
    {
        if ($this->enableContentEncryption) {
            return '';
        }

        return $this->getValueAsString($value, $element);
    }

    public function isSearchableField(): bool
    {
        return !$this->getIsCosmetic();
    }

    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        return ElementHelper::attributeHtml($value);
    }

    public function afterCreateField(array $data): void
    {
    }

    public function modifyElementIndexQuery(ElementQueryInterface $query): void
    {
    }

    public function beforeElementSave(ElementInterface $element, bool $isNew): bool
    {
        // Fire a 'beforeElementSave' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_ELEMENT_SAVE)) {
            $event = new FieldElementEvent([
                'element' => $element,
                'isNew' => $isNew,
            ]);
            $this->trigger(self::EVENT_BEFORE_ELEMENT_SAVE, $event);
            return $event->isValid;
        }

        return true;
    }

    public function afterElementSave(ElementInterface $element, bool $isNew): void
    {
        // Fire an 'afterElementSave' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_ELEMENT_SAVE)) {
            $this->trigger(self::EVENT_AFTER_ELEMENT_SAVE, new FieldElementEvent([
                'element' => $element,
                'isNew' => $isNew,
            ]));
        }
    }

    public function afterElementPropagate(ElementInterface $element, bool $isNew): void
    {
        // Fire an 'afterElementPropagate' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_ELEMENT_PROPAGATE)) {
            $this->trigger(self::EVENT_AFTER_ELEMENT_PROPAGATE, new FieldElementEvent([
                'element' => $element,
                'isNew' => $isNew,
            ]));
        }
    }

    public function beforeElementDelete(ElementInterface $element): bool
    {
        // Fire a 'beforeElementDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_ELEMENT_DELETE)) {
            $event = new FieldElementEvent(['element' => $element]);
            $this->trigger(self::EVENT_BEFORE_ELEMENT_DELETE, $event);
            return $event->isValid;
        }

        return true;
    }

    public function afterElementDelete(ElementInterface $element): void
    {
        // Fire an 'afterElementDelete' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_ELEMENT_DELETE)) {
            $this->trigger(self::EVENT_AFTER_ELEMENT_DELETE, new FieldElementEvent([
                'element' => $element,
            ]));
        }
    }

    public function beforeElementDeleteForSite(ElementInterface $element): bool
    {
        return true;
    }

    public function afterElementDeleteForSite(ElementInterface $element): void
    {
    }

    public function beforeElementRestore(ElementInterface $element): bool
    {
        // Fire a 'beforeElementRestore' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_ELEMENT_RESTORE)) {
            $event = new FieldElementEvent(['element' => $element]);
            $this->trigger(self::EVENT_BEFORE_ELEMENT_RESTORE, $event);
            return $event->isValid;
        }

        return true;
    }

    public function afterElementRestore(ElementInterface $element): void
    {
        // Fire an 'afterElementRestore' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_ELEMENT_RESTORE)) {
            $this->trigger(self::EVENT_AFTER_ELEMENT_RESTORE, new FieldElementEvent([
                'element' => $element,
            ]));
        }
    }

    public function propagateValue(ElementInterface $from, ElementInterface $to): void
    {
        $to->setFieldValue($this->handle, $from->getFieldValue($this->handle));
    }

    public function copyValue(ElementInterface $from, ElementInterface $to): void
    {
        $value = $this->serializeValue($from->getFieldValue($this->handle), $from);
        $to->setFieldValue($this->handle, $value);
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['label', 'handle'], 'required'];
        $rules[] = [['placeholder', 'errorMessage', 'cssClasses'], 'string', 'max' => 255];
        $rules[] = [['handle'], HandleValidator::class, 'reservedWords' => Formie::$plugin->getFields()->getReservedHandles()];
        $rules[] = [['handle'], 'string', 'max' => 64];
        $rules[] = [['reference'], 'string', 'max' => 36];

        $rules[] = [
            ['handle'],
            LayoutHandleUniqueValidator::class,
        ];

        $rules[] = [
            ['reference'],
            UniqueValidator::class,
            'targetClass' => FormFieldRecord::class,
            'targetAttribute' => ['reference'],
            'skipOnEmpty' => true,
            'message' => Craft::t('yii', '{attribute} "{value}" has already been taken.'),
        ];

        // Only validate the ID if it’s not a new field
        if (!$this->getIsNew()) {
            $rules[] = [['id'], 'number', 'integerOnly' => true];
        }

        $rules[] = [
            ['labelPosition'],
            'in',
            'range' => Formie::$plugin->getFields()->getLabelPositions($this),
            'skipOnEmpty' => true,
        ];

        $rules[] = [
            ['instructionsPosition'],
            'in',
            'range' => Formie::$plugin->getFields()->getInstructionsPositions($this),
            'skipOnEmpty' => true,
        ];

        return $rules;
    }

    protected function dbTypeForValueSql(): array|string|null
    {
        return static::dbType();
    }

    protected function isFresh(?ElementInterface $element = null): bool
    {
        if (isset($this->_isFresh)) {
            return $this->_isFresh;
        }

        if ($element) {
            return $element->getIsFresh();
        }

        return true;
    }

    protected function requestParamName(ElementInterface $element): ?string
    {
        $namespace = $element->getFieldParamNamespace();

        return ($namespace ? $namespace . '.' : '') . $this->valueKey();
    }
    
    protected function setPrePopulatedValue(mixed $value): mixed
    {
        return $value;
    }

    protected function supportsPlainTextHtmlSanitization(): bool
    {
        return false;
    }

    protected function sanitizePlainTextValueIfConfigured(string $value): string
    {
        if (!$this->supportsPlainTextHtmlSanitization()) {
            return $value;
        }

        $policy = Formie::$plugin->getSettings()->plainTextHtmlSanitizationMode;

        if ($policy !== Settings::PLAIN_TEXT_HTML_SANITIZATION_MODE_SANITIZE) {
            return $value;
        }

        return StringHelper::sanitizePlainTextInput($value);
    }

    protected function renderPreviewText(string $text): string
    {
        return ElementHelper::attributeHtml($text);
    }

    protected function applyParentFieldContext(FieldInterface $value, string $namespace = ''): void
    {
        $this->_parentField = $value;

        // Also, set the namespace (on the parent field), commonly just the field handle
        // But allows it to be added to (think Repeater).
        // Be sure to create a valid name attribute, from `fieldHandle` and `some[more][attrs]`
        // to `fieldHandle[some][some][attrs]`. Also allow `0` as a namespace.
        if ($namespace !== '') {
            $this->setNamespace(Html::namespaceInputName($namespace, $value->handle));
        } else {
            $this->setNamespace($value->handle);
        }

        $this->_fieldPath = null;
    }


    // Private Methods
    // =========================================================================

    private function _valueSql(?string $key): ?string
    {
        return FieldValueQueryHelper::buildValueSql(static::class, $this->uid, $this->dbTypeForValueSql(), $key);
    }

    private function _valueColumnType(?string $key): ?string
    {
        return FieldValueQueryHelper::resolveValueColumnType(static::class, $this->dbTypeForValueSql(), $key);
    }

    private static function _hasLegacyStaticMethodOverride(string $method): bool
    {
        $reflection = new \ReflectionMethod(static::class, $method);

        return $reflection->getDeclaringClass()->getName() !== self::class;
    }

    private static function _getDefaultReferenceBlockTemplatePath(): string
    {
        return 'fields/' . static::kebabClassName();
    }

    private function _hasLegacyFieldMethodOverride(string $method): bool
    {
        $reflection = new \ReflectionMethod(static::class, $method);

        return $reflection->getDeclaringClass()->getName() !== self::class;
    }

    private function _renderReferenceBlockHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool
    {
        // Reference-block rendering is the canonical rich/looped field output,
        // even while notification templates still provide the first consumer.
        $value = $this->getValueForReferenceBlock($value, $notification, $submission);
        $inputOptions = $this->getReferenceBlockOptions($submission, $notification, $value, $renderOptions);
        $html = $notification->renderTemplate(static::getReferenceBlockTemplatePath(), $inputOptions);

        return Template::raw($html);
    }

    private function _buildReferenceBlockOptions(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): array
    {
        return [
            'notification' => $notification,
            'submission' => $submission,
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'renderOptions' => $renderOptions,
        ];
    }

}
