<?php
namespace verbb\formie\base;

use verbb\formie\base\Element as ElementIntegration;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyElementFieldQueryEvent;
use verbb\formie\fields\conditions\ElementFieldConditionRule;
use verbb\formie\fields\definitions\FieldReferenceValue;
use verbb\formie\fields\definitions\FieldValueClass;
use verbb\formie\fields\values\ElementFieldValue;
use verbb\formie\fields\values\MultiOptionFieldValue;
use verbb\formie\fields\values\OptionValue;
use verbb\formie\fields\values\SingleOptionFieldValue;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\Checkboxes;
use verbb\formie\fields\Radio;
use verbb\formie\fields\SingleLineText;
use verbb\formie\fields\Tags;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\SlotTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;
use verbb\formie\models\OptionSource;
use verbb\formie\options\ElementOptionSourceHelper;
use verbb\formie\options\OptionResolvableInterface;
use verbb\formie\positions\Hidden as HiddenPosition;
use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\InlineEditableFieldInterface;
use craft\base\NestedElementInterface;
use craft\behaviors\EventBehavior;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\ElementCollection;
use craft\elements\conditions\ElementCondition;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementRelationParamParser;
use craft\elements\db\OrderByPlaceholderExpression;
use craft\errors\SiteNotFoundException;
use craft\events\CancelableEvent;
use craft\events\ElementCriteriaEvent;
use craft\fields as CraftFields;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\Gql as CraftGqlHelper;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Queue;
use craft\helpers\Template as TemplateHelper;
use craft\records\EntryType as EntryTypeRecord;
use craft\services\ElementSources;
use craft\services\Elements;
use craft\services\Gql as CraftGqlService;

use DateTime;
use ReflectionClass;
use ReflectionProperty;
use Throwable;

use Faker\Generator as FakerFactory;

use Twig\Markup;

use Illuminate\Support\Collection;

use GraphQL\Type\Definition\Type;

use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\db\Expression;
use yii\db\ExpressionInterface;
use yii\validators\NumberValidator;

abstract class ElementField extends Field implements ElementFieldInterface, OptionResolvableInterface
{
    // Static Methods
    // =========================================================================

    abstract public static function elementType(): string;

    public static function getOptionSourceDefinition(): ?array
    {
        $definition = static::defineOptionSource();

        if ($definition === null) {
            return null;
        }

        $handle = trim((string)($definition['handle'] ?? ''));

        if ($handle === '') {
            return null;
        }

        return [
            'handle' => $handle,
            'label' => trim((string)($definition['label'] ?? static::displayName())),
        ];
    }

    public static function queryCondition(array $instances, mixed $value, array &$params): array|string|ExpressionInterface|false|null
    {
        $values = [];

        if (is_array($value)) {
            foreach ($value as $element) {
                if ($element instanceof ElementInterface) {
                    $values[] = $element->id;
                }

                if (is_int($element)) {
                    $values[] = $element;
                }
            }
        }

        if ($value instanceof ElementInterface) {
            $values[] = $value->id;
        }

        if (is_int($value)) {
            $values[] = $value;
        }

        return parent::queryCondition($instances, Json::encode($values), $params);
    }


    // Constants
    // =========================================================================

    public const EVENT_MODIFY_ELEMENT_QUERY = 'modifyElementQuery';
    public const EVENT_DEFINE_SELECTION_CRITERIA = 'defineSelectionCriteria';


    // Properties
    // =========================================================================

    public string|array|null $sources = '*';
    public ?string $source = null;
    public bool $allowMultipleSources = true;
    public bool $limit = false;
    public ?string $limitOptions = null;
    public string $displayType = 'dropdown';
    public string $labelSource = 'title';
    public string $orderBy = 'title ASC';
    public bool $multi = false;
    public ?string $layout = 'vertical';
    public string $sourceType = 'groups';
    public array $sourceElements = [];

    protected ?ElementQuery $elementsQuery = null;
    protected ?string $cpInputJsClass = null;
    protected string $cpInputTemplate = '_includes/forms/elementSelect';

    private array|null|ElementConditionInterface $_selectionCondition = null;


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        // Normalize the options
        if (array_key_exists('multiple', $config)) {
            $config['multi'] = ArrayHelper::remove($config, 'multiple');
        }

        parent::__construct($config);
    }

    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'sources';
        $attributes[] = 'source';
        $attributes[] = 'limitOptions';
        $attributes[] = 'displayType';
        $attributes[] = 'labelSource';
        $attributes[] = 'orderBy';
        $attributes[] = 'multi';
        $attributes[] = 'layout';
        $attributes[] = 'sourceType';
        $attributes[] = 'sourceElements';

        return $attributes;
    }

    public function isValueEmpty(mixed $value, ?ElementInterface $element): bool
    {
        if ($value instanceof ElementQueryInterface) {
            return !$value->exists();
        }

        return $value->isEmpty();
    }

    public function isSearchableField(): bool
    {
        // Element-backed fields can trigger expensive relation queries while indexing.
        return false;
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value instanceof ElementQueryInterface) {
            return $value;
        }

        $query = static::elementType()::find();

        // Restrict elements to be on the current site, for multi-sites
        if (Craft::$app->getIsMultiSite()) {
            $query->siteId($this->targetSiteId($element));
        }

        if (is_array($value)) {
            // Check if the array contains associative arrays with an 'id' key
            if (isset($value[0]) && is_array($value[0]) && array_key_exists('id', $value[0])) {
                $value = ArrayHelper::getColumn($value, 'id');
            }

            // Cleanup to ensure only valid IDs
            $ids = array_values(array_filter($value, function($id) {
                return !empty($id);
            }));

            $query->id($ids)->fixedOrder();
        } else {
            $query->id(false);
        }

        // Allow any status for now, probably refactor `modifyElementFieldQuery` for next breakpoint
        $query->status(null);

        return $query;
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        // Ensure that we allow saving any status elements
        $value->status(null);

        return $value->ids();
    }

    public function getElementConditionRuleType(): array|string|null
    {
        return ElementFieldConditionRule::class;
    }

    public function getElementsQuery(): ElementQueryInterface
    {
        $query = static::elementType()::find();
        $conditionsService = Craft::$app->getConditions();

        // Restrict elements to be on the current site, for multi-sites
        if (Craft::$app->getIsMultiSite()) {
            $query->siteId(Craft::$app->getSites()->getCurrentSite()->id);
        }

        if ($this->sourceType === 'groups') {
            $criteria = [];

            $sources = $this->getInputSources();

            if (is_array($sources)) {
                foreach ($sources as $sourceKey) {
                    $elementSource = ArrayHelper::firstWhere($this->availableSources(), 'key', $sourceKey);

                    // Check for custom sources, which use conditions directly on the query
                    if ($elementSource && $elementSource['type'] === ElementSources::TYPE_CUSTOM) {
                        // Handle conditions by parsing the rules and applying to query
                        $sourceCondition = $conditionsService->createCondition($elementSource['condition']);
                        $sourceCondition->modifyQuery($query);
                    } else if (str_contains($sourceKey, 'type:')) {
                        // Special-case for entries, maybe redactor?
                        $entryTypeUid = str_replace('type:', '', $sourceKey);
                        $entryType = EntryTypeRecord::find()->where(['uid' => $entryTypeUid])->one();

                        if ($entryType) {
                            $criteria[] = ['typeId' => $entryType->id];
                        }
                    } else {
                        $sourceCriteria = $elementSource['criteria'] ?? [];

                        // Remove anything we don't need/want
                        unset($sourceCriteria['editable']);

                        $criteria[] = $sourceCriteria;
                    }
                }
            }

            // Merge here for performance
            $criteria = array_merge_recursive(...$criteria);

            // Apply the criteria on our query
            Craft::configure($query, $criteria);
        } else if ($this->sourceType === 'elements') {
            $query->id(ArrayHelper::getColumn($this->sourceElements, 'id'));
        }

        // Ensure selected initial values survive option limits for render-time prefill/default cases.
        $initialValue = $this->getInitialValue();

        if ($initialValue && $this->limitOptions) {
            $ids = [];

            // Handle the two ways a default value can be set
            if ($initialValue instanceof ElementQueryInterface) {
                $ids = $initialValue->id;
            } else {
                $ids = ArrayHelper::getColumn($initialValue, 'id');

                if (!$ids && is_array($initialValue)) {
                    $ids = $initialValue;
                }
            }

            if ($ids) {
                $query->id($ids);
            }
        }

        if ($this->limitOptions) {
            $query->limit($this->limitOptions);
        }

        if ($this->orderBy) {
            $query->orderBy($this->orderBy);
        }

        // Allow any template-defined elementQuery to override
        if ($this->elementsQuery) {
            $query = $this->elementsQuery;
        }

        // Fire a 'modifyElementFieldQuery' event
        $event = new ModifyElementFieldQueryEvent([
            'query' => $query,
            'field' => $this,
        ]);
        $this->trigger(self::EVENT_MODIFY_ELEMENT_QUERY, $event);

        return $event->query;
    }

    public function getDefaultValueQuery()
    {
        $defaultValue = $this->getInitialValue() ?? '';

        if ($defaultValue instanceof ElementQuery) {
            $defaultValue = $defaultValue->all();
        }

        // If passing in a single ID, normalise it
        if (!is_array($defaultValue)) {
            $defaultValue = $defaultValue ? [['id' => $defaultValue]] : [];
        }

        // Just in case there are empty items
        $defaultValue = array_filter($defaultValue);

        if ($defaultValue) {
            // Handle when setting via a multidimensional array with `id`
            $ids = array_filter(ArrayHelper::getColumn($defaultValue, 'id'));

            // If nothing found, we might be setting an array of IDs
            if (!$ids) {
                $ids = $defaultValue;
            }

            if ($ids) {
                return static::elementType()::find()->id($ids);
            }
        }

        return null;
    }

    public function getIsMultiDropdown(): bool
    {
        return ($this->displayType === 'dropdown' && $this->multi);
    }

    public function getPreviewElements(): array
    {
        $options = array_map(function($input) {
            return ['label' => $this->getElementLabel($input), 'value' => $input->id];
        }, $this->getElementsQuery()->limit(5)->all());

        return [
            'total' => $this->getElementsQuery()->count(),
            'options' => $options,
        ];
    }

    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        if ($value instanceof ElementQueryInterface) {
            return Cp::elementPreviewHtml($value->all());
        }

        return '';
    }

    public function getInputTemplateVariables(Form $form, mixed $value): array
    {
        $inputOptions = parent::getInputTemplateVariables($form, $value);

        $inputOptions['elementsQuery'] = $this->getElementsQuery();

        return $inputOptions;
    }

    public function populateValue(mixed $value, ?Submission $submission): void
    {
        if ($value) {
            if ($value instanceof ElementQuery) {
                $query = $value;
            } else {
                $query = static::elementType()::find()->id($value);
            }

            // Ensure that disabled elements can be populated, just in case
            $query->status(null);

            parent::populateValue($query, $submission);
        }
    }

    public function getFieldOptions(): array
    {
        return $this->getResolvedOptions();
    }

    public function getResolvedOptions(): array
    {
        $options = [];

        foreach ($this->getElementsQuery()->all() as $element) {
            // Important to cast as a string, otherwise Twig will struggle to compare
            $options[] = ['label' => $this->getElementLabel($element), 'value' => (string)$element->id];
        }

        return $options;
    }

    public function toOptionSource(): ?OptionSource
    {
        $provider = ElementOptionSourceHelper::getProviderForFieldClass(static::class);

        if (!$provider) {
            return null;
        }

        return new OptionSource([
            'type' => 'element',
            'provider' => $provider,
            'params' => $this->getOptionSourceParams(),
        ]);
    }

    public function getDisplayTypeFieldConfig(): array
    {
        $config = [
            'options' => $this->getFieldOptions(),
            'hasMultiNamespace' => true,
            'multi' => $this->multi,
        ];

        // Set the parent field and namespace, but in a specific way due to nested field handling.
        if ($this->getParentField()) {
            // Note the order here is important, due to Repeaters (and other nested fields)
            // can set the namespace with `setParentFIeld()`, but we want to specifically use the
            // namespace value we already have, which has already been set anyway.
            $config['parentField'] = $this->getParentField();
            $config['namespace'] = $this->getNamespace();
        } else {
            $config['namespace'] = $this->getNamespace();
        }

        // Grab just the properties for this field, defined in the base `Field` class
        $class = new ReflectionClass($this);

        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic() && $property->class === Field::class) {
                $config[$property->getName()] = $this->{$property->getName()};
            }
        }

        return $config;
    }

    public function getDisplayTypeField(): ?FieldInterface
    {
        $config = $this->getDisplayTypeFieldConfig();

        if ($this->displayType === 'dropdown') {
            return new Dropdown($config);
        }

        if ($this->displayType === 'radio') {
            return new Radio($config);
        }

        if ($this->displayType === 'checkboxes') {
            return new Checkboxes($config);
        }

        return null;
    }

    public function getDisplayTypeValue(?ElementQuery $value): MultiOptionFieldValue|SingleOptionFieldValue|null
    {
        // Setup the default value, if the value is empty
        if ($this->isValueEmpty($value, null)) {
            if ($defaultValue = $this->getDefaultValueQuery()) {
                $value = $defaultValue;
            }
        }

        if ($this->displayType === 'checkboxes' || $this->getIsMultiDropdown()) {
            $options = [];

            foreach ($value->all() as $element) {
                $options[] = new OptionValue($this->getElementLabel($element), $element->id, true);
            }

            return new MultiOptionFieldValue($options);
        }

        if ($this->displayType === 'radio') {
            if ($element = $value->one()) {
                return new SingleOptionFieldValue($this->getElementLabel($element), $element->id, true);
            }

            return null;
        }

        if ($this->displayType === 'dropdown') {
            if ($element = $value->one()) {
                return new SingleOptionFieldValue($this->getElementLabel($element), $element->id, true);
            }

            return null;
        }

        return $value;
    }

    public function setElementsQuery(?ElementQuery $query): void
    {
        $this->elementsQuery = $query;
    }

    public function defineLabelSourceOptions(): array
    {
        return [];
    }

    public function getLabelSourceOptions(): array
    {
        return array_merge([
            ['value' => 'id', 'label' => Craft::t('app', 'ID')],
        ], $this->defineLabelSourceOptions(), [
            ['value' => 'dateCreated', 'label' => Craft::t('app', 'Date Created')],
            ['value' => 'dateUpdated', 'label' => Craft::t('app', 'Date Updated')],
        ]);
    }

    public function getOrderByOptions(): array
    {
        $options = [];

        foreach ($this->getLabelSourceOptions() as $opt) {
            $options[] = ['value' => $opt['value'] . ' ASC', 'label' => $opt['label'] . ' Ascending'];
            $options[] = ['value' => $opt['value'] . ' DESC', 'label' => $opt['label'] . ' Descending'];
        }

        return $options;
    }

    public function getSourceOptions(): array
    {
        $options = array_map(fn($s) => [
            'label' => $s['label'],
            'value' => $s['key'],
            'data' => [
                'structure-id' => $s['structureId'] ?? null,
            ],
        ], $this->availableSources());

        ArrayHelper::multisort($options, 'label', SORT_ASC, SORT_NATURAL | SORT_FLAG_CASE);

        return $options;
    }

    public function getOptionSourceMode(): string
    {
        return $this->allowMultipleSources ? 'multiple' : 'single';
    }

    public function getOptionSourceParams(): array
    {
        $params = [
            'labelSource' => $this->labelSource,
            'orderBy' => $this->orderBy,
        ];

        if ($this->limitOptions) {
            $params['limitOptions'] = $this->limitOptions;
        }

        if ($this->getOptionSourceMode() === 'multiple') {
            $params['sources'] = $this->sources ?? '*';
        } else {
            $params['source'] = $this->source;
        }

        return $params;
    }

    public function getOptionSourceFieldConfig(array $params = []): array
    {
        $config = [];

        if (!empty($params['labelSource'])) {
            $config['labelSource'] = (string)$params['labelSource'];
        }

        if (!empty($params['orderBy'])) {
            $config['orderBy'] = (string)$params['orderBy'];
        }

        if (!empty($params['limitOptions'])) {
            $config['limitOptions'] = (string)$params['limitOptions'];
        }

        if ($this->getOptionSourceMode() === 'multiple') {
            $sources = $params['sources'] ?? '*';

            if (is_string($sources) && $sources !== '') {
                $config['sources'] = $sources;
            } elseif (is_array($sources) && $sources !== []) {
                $config['sources'] = $sources;
            } else {
                $config['sources'] = '*';
            }
        } else {
            $source = $params['source'] ?? null;

            if (!$source && !empty($params['sources'])) {
                $sources = $params['sources'];
                $source = is_array($sources) ? ($sources[0] ?? null) : $sources;
            }

            if ($source) {
                $config['source'] = (string)$source;
            }
        }

        return $config;
    }

    public function getOptionSourceSourceOptions(): array
    {
        return $this->getSourceOptions();
    }

    public function getOptionSourceOrderByOptions(): array
    {
        return $this->getOrderByOptions();
    }

    public function getOptionSourceWarning(array $sourceOptions): ?string
    {
        return $sourceOptions === []
            ? Craft::t('formie', 'No sources available for this element type.')
            : null;
    }

    public function getInputSources(?ElementInterface $element = null): array|string|null
    {
        if ($this->allowMultipleSources) {
            $sources = $this->sources;
        } else {
            $sources = [$this->source];
        }

        return $sources;
    }

    public function getInputSelectionCriteria(): array
    {
        // Fire a 'defineSelectionCriteria event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_SELECTION_CRITERIA)) {
            $event = new ElementCriteriaEvent();
            $this->trigger(self::EVENT_DEFINE_SELECTION_CRITERIA, $event);

            return $event->criteria;
        }

        return [];
    }

    public function getSelectionCondition(): ?ElementConditionInterface
    {
        if ($this->_selectionCondition !== null && !$this->_selectionCondition instanceof ConditionInterface) {
            $condition = Craft::$app->getConditions()->createCondition($this->_selectionCondition);
            
            if (!empty($condition->getConditionRules())) {
                $this->_selectionCondition = $condition;
            } else {
                $this->_selectionCondition = null;
            }
        }

        return $this->_selectionCondition;
    }

    public function setSelectionCondition(mixed $condition): void
    {
        if ($condition instanceof ConditionInterface && !$condition->getConditionRules()) {
            $condition = null;
        }

        // Don't instantiate it unless we actually end up needing it.
        // Avoids an infinite recursion bug (ElementCondition::selectableConditionRules() => getAllFields() => setSelectionCondition() => ...)
        $this->_selectionCondition = $condition;
    }

    public function fieldKind(): string
    {
        $displayType = (string)($this->displayType ?? 'dropdown');

        return match ($displayType) {
            'radio' => self::KIND_RADIO_GROUP,
            'checkboxes' => self::KIND_CHECKBOX_GROUP,
            default => self::KIND_SELECT,
        };
    }

    public function getIsMultiOptionsField(): bool
    {
        return false;
    }

    public function getSafeElementUrl(object $element, bool $forCp = false): ?string
    {
        $url = null;

        if ($forCp && method_exists($element, 'getCpEditUrl')) {
            $url = $element->getCpEditUrl();
        }

        if (!$forCp && method_exists($element, 'getUrl')) {
            $url = $element->getUrl();
        }

        return StringHelper::sanitizeUrlAttribute(is_string($url) ? $url : null);
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'sources' => [
                'name' => 'sources',
                'type' => Type::string(),
                'resolve' => function($field) {
                    $value = $field->sources;

                    return is_array($value) ? Json::encode($value) : $value;
                },
            ],
            'source' => [
                'name' => 'source',
                'type' => Type::string(),
            ],
            'limitOptions' => [
                'name' => 'limitOptions',
                'type' => Type::string(),
            ],
            'displayType' => [
                'name' => 'displayType',
                'type' => Type::string(),
            ],
            'labelSource' => [
                'name' => 'labelSource',
                'type' => Type::string(),
            ],
            'orderBy' => [
                'name' => 'orderBy',
                'type' => Type::string(),
            ],
            'multi' => [
                'name' => 'multi',
                'type' => Type::boolean(),
            ],
            'layout' => [
                'name' => 'layout',
                'type' => Type::string(),
            ],
            'defaultValue' => [
                'name' => 'defaultValue',
                'type' => Type::string(),
                'resolve' => function($field) {
                    $value = $field->defaultValue;

                    return is_array($value) ? Json::encode($value) : $value;
                },
            ],
        ]);
    }

    public function getContentGqlMutationArgumentType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => Type::listOf(Type::int()),
            'description' => $this->instructions,
        ];
    }


    // Protected Methods
    // =========================================================================

    protected static function defineOptionSource(): ?array
    {
        return null;
    }

    protected static function gqlElementContentTypeDefinitionFromConfig(array $config, Type $elementType, array $arguments, string $resolverClass): array
    {
        return [
            'name' => $config['handle'] ?? '',
            'type' => Type::nonNull(Type::listOf($elementType)),
            'args' => $arguments,
            'resolve' => $resolverClass . '::resolve',
            'complexity' => CraftGqlHelper::relatedArgumentComplexity(CraftGqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
        ];
    }

    protected static function gqlElementContentMutationArgumentTypeDefinitionFromConfig(array $config): array
    {
        return [
            'name' => $config['handle'] ?? '',
            'type' => Type::listOf(Type::int()),
            'description' => $config['instructions'] ?? null,
        ];
    }

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;

        $id = $this->getHtmlId($form);

        if (in_array($this->displayType, ['checkboxes', 'radio'])) {
            if ($key === 'fieldLayout') {
                return SlotTag::make('fieldset')
                    ->core([
                        'data-formie-field-layout' => true,
                        'data-formie-element-field-layout' => true,
                        'data-formie-layout' => $this->layout ?? 'vertical',
                        'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
                    ])
                    ->theme([
                        'class' => [
                            'formie-field-layout',
                            'formie-element-field-layout',
                            'formie-layout-' . ($this->layout ?? 'vertical'),
                        ],
                    ]);
            }

            if ($key === 'fieldLabel') {
                $labelPosition = $context->get('labelPosition');

                return SlotTag::make('legend')
                    ->core([
                        'data-formie-label' => true,
                        'data-formie-field-label' => true,
                        'data-formie-element-field-label' => true,
                        'data-formie-sr-only' => $labelPosition instanceof HiddenPosition ? true : false,
                    ])
                    ->theme([
                        'class' => [
                            'formie-label',
                            'formie-field-label',
                            'formie-element-field-label',
                            $labelPosition instanceof HiddenPosition ? 'formie-sr-only' : false,
                        ],
                    ]);
            }
        }

        return parent::defineFieldSlotTag($key, $context);
    }

    protected function defineClientInput(): array
    {
        $displayType = (string)($this->displayType ?? 'dropdown');

        return array_merge(parent::defineClientInput(), [
            'multiple' => $this->getIsMultiOptionsField(),
            'layout' => in_array($displayType, ['radio', 'checkboxes'], true) ? ($this->layout ?? 'vertical') : null,
            'options' => array_values(array_map(static function(array $option) {
                return [
                    'label' => $option['label'] ?? '',
                    'value' => $option['value'] ?? '',
                    'selected' => (bool)($option['default'] ?? false),
                    'disabled' => (bool)($option['disabled'] ?? false),
                ];
            }, $this->getFieldOptions())),
        ]);
    }

    protected function cpInputTemplateVariables(array|ElementQueryInterface $value = null, ?ElementInterface $element = null): array
    {
        return [
            'id' => Html::id($this->handle),
            'jsClass' => $this->cpInputJsClass,
            'elementType' => static::elementType(),
            'storageKey' => 'field.' . $this->id,
            'condition' => [],
            'criteria' => [],
            'fieldId' => $this->id,
            'selectionLabel' => Craft::t('formie', 'Choose'),
            'name' => $this->handle,
            'elements' => $value,
            'sources' => $this->getInputSources($element),
            'sourceElementId' => !empty($element->id) ? $element->id : null,
            'showSiteMenu' => 'auto',
            'viewMode' => 'list',
            'limit' => $this->limitOptions ? $this->limitOptions : null,
            'defaultPlacement' => 'end',
            'modalSettings' => [
                'defaultSiteId' => $element->siteId ?? null,
            ],
        ];
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        // Ensure that the element query allows all statuses for the CP
        $value->status(null);

        return Craft::$app->getView()->renderTemplate($this->cpInputTemplate, $this->cpInputTemplateVariables($value->all(), $element));
    }

    protected function availableSources(): array
    {
        $sources = ArrayHelper::where(
            Craft::$app->getElementSources()->getSources(static::elementType(), 'modal'),
            fn($s) => $s['type'] !== ElementSources::TYPE_HEADING
        );

        // Ensure that we always include a "All" option, even if people are removing it from sources in events
        if ($this->allowMultipleSources) {
            $hasAllSource = false;

            foreach ($sources as $key => $source) {
                if (isset($source['key']) && $source['key'] === '*') {
                    $hasAllSource = true;
                }
            }

            if (!$hasAllSource) {
                array_unshift($sources, [
                    'key' => '*',
                    'label' => Craft::t('formie', 'All'),
                ]);
            }
        }

        return $sources;
    }

    protected function setPrePopulatedValue(mixed $value): array
    {
        $ids = [];

        // Normalize setting from query param.
        if (is_array($value)) {
            $ids = array_values(array_filter($value));
        } else {
            $ids = [$value];
        }

        return $ids;
    }

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
    {
        return implode(', ', array_map(function($item) {
            return $this->getElementLabel($item);
        }, $value->all()));
    }

    protected function defineValueAsArray(mixed $value, ElementInterface $element = null): mixed
    {
        return array_map(function($item) {
            return $this->_elementToArray($item);
        }, $value->all());
    }

    protected function defineValueForIntegration(mixed $value, IntegrationField $integrationField, IntegrationInterface $integration, ElementInterface $element = null, string $fieldKey = ''): mixed
    {
        // Set the status to null to include disabled elements
        $value->status(null);

        // Send through a CSV of element titles, when mapping to a string
        if ($integrationField->getType() === IntegrationField::TYPE_STRING) {
            return $this->defineValueAsString($value, $element);
        }

        if ($integrationField->getType() === IntegrationField::TYPE_ARRAY) {
            // When an array, assume a collection of titles for most integrations, except element integrations
            if ($integration instanceof ElementIntegration) {
                return $value->ids();
            }

            // All other instances should use the title (or title-value)
            return array_map(function($item) {
                return $this->getElementLabel($item);
            }, $value->all());
        }

        // When a number, assume a single ID
        if ($integrationField->getType() === IntegrationField::TYPE_NUMBER) {
            return $value->ids()[0] ?? null;
        }

        // When a number, assume a single ID
        if ($integrationField->getType() === IntegrationField::TYPE_FLOAT) {
            return $value->ids()[0] ?? null;
        }

        return null;
    }

    protected function defineValueForEmailPreview(FakerFactory $faker): mixed
    {
        $query = $this->getElementsQuery();

        if (Craft::$app->getDb()->getIsMysql()) {
            $query->orderBy('RAND()');
        } else {
            $query->orderBy('RANDOM()');
        }

        // Check if we should limit to 1 if a (single) dropdown or radio
        if ($this->displayType === 'radio' || ($this->displayType === 'dropdown' && !$this->multi)) {
            $query->limit(1);
        }

        return $query;
    }

    protected function getStringCustomFieldOptions(array $fields): array
    {
        $options = [];

        // Better to opt-out fields, so we can always allow third-party ones which are impossible to check
        $excludedFields = [
            CraftFields\Assets::class,
            CraftFields\Categories::class,
            CraftFields\Checkboxes::class,
            CraftFields\Entries::class,
            CraftFields\Matrix::class,
            CraftFields\MultiSelect::class,
            CraftFields\Table::class,
            CraftFields\Tags::class,
            CraftFields\Users::class,
        ];

        foreach ($fields as $field) {
            if (in_array(get_class($field), $excludedFields)) {
                continue;
            }

            $options[] = ['label' => $field->name, 'value' => $field->handle];
        }

        return $options;
    }

    protected function getElementLabel(ElementInterface $element): string
    {
        try {
            return (string)$element->{$this->labelSource};
        } catch (Throwable $e) {

        }

        return $element->title;
    }

    protected function targetSiteId(?ElementInterface $element = null): int
    {
        return $element->siteId ?? Craft::$app->getSites()->getCurrentSite()->id;
    }

    protected function createSelectionCondition(): ?ElementConditionInterface
    {
        return null;
    }

    protected function defineReferenceValues(): array
    {
        return [
            FieldReferenceValue::property([
                'handle' => 'title',
                'label' => Craft::t('formie', 'Title'),
                'supportsFieldSelect' => false,
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'url',
                'label' => Craft::t('formie', 'Public URL'),
                'supportsFieldSelect' => false,
                'variableTypes' => [Variables::TYPE_TEXT, Variables::TYPE_URL],
            ]),
            FieldReferenceValue::property([
                'handle' => 'cpUrl',
                'label' => Craft::t('formie', 'Control Panel URL'),
                'supportsFieldSelect' => false,
                'variableTypes' => [Variables::TYPE_TEXT, Variables::TYPE_URL],
            ]),
            FieldReferenceValue::property([
                'handle' => '__toString',
                'label' => Craft::t('formie', 'Formatted'),
                'supportsFieldSelect' => false,
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
        ];
    }

    protected function defineValueClass(): ?string
    {
        return ElementFieldValue::class;
    }

    protected function supportedDefaults(): array
    {
        return ['displayType', 'labelSource', 'orderBy', 'limitOptions'];
    }


    // Private Methods
    // =========================================================================

    private function _elementToArray(ElementInterface $element)
    {
        // Get all attributes, exclude custom fields which can cause recursion
        $array = $element->getAttributes();

        // Add in some useful extras
        $array['url'] = $element->getUrl();
        $array['link'] = $element->getLink();
        $array['uriFormat'] = $element->getUriFormat();
        $array['isHomepage'] = $element->getIsHomepage();
        $array['uiLabel'] = $element->getUiLabel();
        $array['cpEditUrl'] = $element->getCpEditUrl();
        $array['postEditUrl'] = $element->getPostEditUrl();
        $array['cpRevisionsUrl'] = $element->getCpRevisionsUrl();
        $array['status'] = $element->getStatus();

        // Get the custom fields
        $array = array_merge($array, $element->getSerializedFieldValues());

        ksort($array);

        return Json::decode(Json::encode($array));
    }

}
