<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\NestedFieldRow;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFieldValueEvent;
use verbb\formie\events\ModifyFieldEmailValueEvent;
use verbb\formie\events\ModifyFieldIntegrationValueEvent;
use verbb\formie\events\ModifyFieldHtmlTagEvent;
use verbb\formie\fields\formfields;
use verbb\formie\fields\formfields\BaseOptionsField;
use verbb\formie\fields\formfields\Hidden;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\helpers\Html;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;
use verbb\formie\models\HtmlTag;
use verbb\formie\positions\AboveInput;
use verbb\formie\positions\BelowInput;

use Craft;
use craft\base\ElementInterface;
use craft\gql\types\DateTime as DateTimeType;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\validators\HandleValidator;

use GraphQL\Type\Definition\Type;

use Twig\Markup;

use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use Throwable;

trait FormFieldTrait
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function getFrontEndInputTemplatePath(): string
    {
        return 'fields/' . static::_getKebabName();
    }

    /**
     * @inheritDoc
     */
    public static function getEmailTemplatePath(): string
    {
        return 'fields/' . static::_getKebabName();
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIcon(): string
    {
        if (static::getSvgIconPath()) {
            return Craft::$app->getView()->renderTemplate(static::getSvgIconPath());
        }

        return '';
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return '';
    }


    // Properties
    // =========================================================================

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
    public bool $includeInEmail = true;
    public bool $enableConditions = false;
    public ?array $conditions = null;
    public bool $enableContentEncryption = false;
    public ?string $visibility = null;
    
    public ?int $formId = null;
    public ?int $rowId = null;
    public ?string $rowUid = null;
    public ?int $rowIndex = null;

    public bool $isNested = false;

    private ?Form $_form = null;
    private ?NestedFieldInterface $_container = null;
    private array $_themeConfig = [];
    private ?FormFieldInterface $_parentField = null;
    private string $_namespace = 'fields';


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Config normalization
        self::normalizeConfig($config);

        parent::__construct($config);
    }

    /**
     * @inheritDoc
     */
    public function getIsNew(): bool
    {
        return parent::getIsNew() || $this->getIsRef();
    }

    /**
     * @inheritDoc
     */
    public function getIsRef(): bool
    {
        return $this->id && str_starts_with($this->id, 'sync:');
    }

    /**
     * @inheritDoc
     */
    public function getValue(ElementInterface $element): mixed
    {
        return $element->getFieldValue($this->handle);
    }

    /**
     * @inheritdoc
     */
    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        $value = parent::serializeValue($value, $element);

        // Handle if we need to save field content as encrypted
        if ($this->enableContentEncryption && is_string($value)) {
            $value = StringHelper::encenc($value);
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        $value = parent::normalizeValue($value, $element);

        // Check if the string contains a previously encrypted version, or the field is enabled
        // This might occur if the field was set to encrypted, but changed later. We still need to
        // decrypt field content
        if (is_string($value)) {
            if ($this->enableContentEncryption || str_contains($value, 'base64:')) {
                $value = StringHelper::decdec($value);
            }
        }

        return $value;
    }

    public function getValueAsString(mixed $value, ?ElementInterface $element = null): mixed
    {
        $value = $this->defineValueAsString($value, $element);

        $event = new ModifyFieldValueEvent([
            'value' => $value,
            'field' => $this,
            'submission' => $element,
        ]);

        $this->trigger(static::EVENT_MODIFY_VALUE_AS_STRING, $event);

        return $event->value;
    }

    public function getValueAsJson(mixed $value, ?ElementInterface $element = null): mixed
    {
        $value = $this->defineValueAsJson($value, $element);

        $event = new ModifyFieldValueEvent([
            'value' => $value,
            'field' => $this,
            'submission' => $element,
        ]);

        $this->trigger(static::EVENT_MODIFY_VALUE_AS_JSON, $event);

        return $event->value;
    }

    public function getValueForExport(mixed $value, ?ElementInterface $element = null): mixed
    {
        $value = $this->defineValueForExport($value, $element);

        $event = new ModifyFieldValueEvent([
            'value' => $value,
            'field' => $this,
            'submission' => $element,
        ]);

        $this->trigger(static::EVENT_MODIFY_VALUE_FOR_EXPORT, $event);

        return $event->value;
    }

    public function getValueForIntegration(mixed $value, $integrationField, $integration, ?ElementInterface $element = null, $fieldKey = ''): mixed
    {
        $value = $this->defineValueForIntegration($value, $integrationField, $integration, $element, $fieldKey);

        $event = new ModifyFieldIntegrationValueEvent([
            'value' => $value,
            'field' => $this,
            'submission' => $element,
            'integrationField' => $integrationField,
            'integration' => $integration,
        ]);

        $this->trigger(static::EVENT_MODIFY_VALUE_FOR_INTEGRATION, $event);

        // Raise the same event on the integration class for convenience
        $integration->trigger($integration::EVENT_MODIFY_FIELD_MAPPING_VALUE, $event);

        return $event->value;
    }

    public function getValueForSummary(mixed $value, ?ElementInterface $element = null): mixed
    {
        $value = $this->defineValueForSummary($value, $element);

        $event = new ModifyFieldValueEvent([
            'value' => $value,
            'field' => $this,
            'submission' => $element,
        ]);

        $this->trigger(static::EVENT_MODIFY_VALUE_FOR_SUMMARY, $event);

        return $event->value;
    }

    public function getValueForEmail(mixed $value, $notification, ?ElementInterface $element = null): mixed
    {
        $value = $this->defineValueForEmail($value, $notification, $element);

        $event = new ModifyFieldEmailValueEvent([
            'value' => $value,
            'field' => $this,
            'submission' => $element,
            'notification' => $notification,
        ]);

        $this->trigger(static::EVENT_MODIFY_VALUE_FOR_EMAIL, $event);

        return $event->value;
    }

    public function populateValue($value): void
    {
        $this->defaultValue = $value;
    }

    public function parsePopulatedFieldValues($value, $element)
    {
        return $value;
    }

    /**
     * @inheritdoc
     */
    public function settingsAttributes(): array
    {
        $class = new ReflectionClass($this);
        $names = parent::settingsAttributes();

        // Parent method does not get properties from traits.
        $parent = $class->getParentClass();
        $traits = $class->getTraits();

        $extraTraits = [];

        if ($class->isSubclassOf(FormField::class)) {
            while (true) {
                $extraTraits[] = $parent->getTraits();
                $parent = $parent->getParentClass();

                if ($parent->name !== FormField::class) {
                    break;
                }
            }
        }

        if ($class->isSubclassOf(BaseOptionsField::class)) {
            while (true) {
                $extraTraits[] = $parent->getTraits();
                $parent = $parent->getParentClass();

                if ($parent->name !== BaseOptionsField::class) {
                    break;
                }
            }
        }

        // For performance
        $traits = array_merge(...$extraTraits);

        foreach ($traits as $trait) {
            foreach ($trait->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                if (!$property->isStatic() && !$property->getDeclaringClass()->isAbstract()) {
                    $names[] = $property->getName();
                }
            }
        }

        $names = array_unique($names);
        ArrayHelper::removeValue($names, 'rowId');
        ArrayHelper::removeValue($names, 'rowIndex');
        ArrayHelper::removeValue($names, 'rowUid');

        return $names;
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        if ($this->matchField) {
            $rules[] = ['validateMatchField', 'skipOnEmpty' => false];
        }

        return $rules;
    }

    public function validateMatchField(ElementInterface $element): void
    {
        $fieldHandle = str_replace(['{', '}'], '', $this->matchField);
        $sourceValue = $element->getFieldValue($fieldHandle);
        $value = $element->getFieldValue($this->handle);

        if ($sourceValue !== $value) {
            $sourceField = $element->getFieldByHandle($fieldHandle);

            $element->addError($this->handle, Craft::t('formie', '{name} must match {value}.', [
                'name' => $this->name,
                'value' => $sourceField->name ?? '',
            ]));
        }
    }

    /**
     * @return NestedFieldInterface|Form|null
     */
    public function getGqlFieldContext(): Form|NestedFieldInterface|null
    {
        return $this->isNested ? $this->getContainer() : $this->getForm();
    }

    /**
     * Return the container if this is a nested field.
     *
     * @return NestedFieldInterface
     */
    public function getContainer(): NestedFieldInterface
    {
        return $this->_container;
    }

    /**
     * Set the container for a nested field.
     */
    public function setContainer(NestedFieldInterface $container): void
    {
        $this->_container = $container;
    }

    /**
     * @return Form|null
     */
    public function getForm(): ?Form
    {
        if (!$this->formId) {
            // Try and fetch the form via the UID from the context
            /* @var Form $form */
            if ($form = Form::find()->uid($this->getContextUid())->one()) {
                $this->formId = $form->id;

                return $this->_form = $form;
            }

            return null;
        }

        if ($this->_form) {
            return $this->_form;
        }

        return $this->_form = Form::find()->id($this->formId)->one();
    }

    public function setForm($value): void
    {
        $this->_form = $value;
    }

    public function getHtmlId(Form $form, ?string $extra = null): string
    {
        // Return the `id` attribute for the field, including parent fields
        // `fui-contactForm-xpvgyvsp-singleName` or `fui-contactForm-xpvgyvsp-multiName-firstName`
        $ids = [$form->getFormId(), ...$this->_getFullNamespace(), $this->handle, $extra];

        return Html::getInputIdAttribute(array_filter($ids));
    }

    public function getHtmlDataId(Form $form, ?string $extra = null): string
    {
        // Return the `data-id` attribute for the field, including parent fields
        // `contactForm-singleName` or `contactForm-multiName-firstName`
        $ids = [$form->handle, ...$this->_getFullHandle(), $extra];

        return implode('-', array_filter($ids));
    }

    public function getHtmlName(?string $extra = null): string
    {
        // Return the `name` attribute for the field, including parent fields
        // `fields[singleName]` or `fields[multiName][firstName]`
        $names = [...$this->_getFullNamespace(), $this->handle, $extra];

        return Html::getInputNameAttribute(array_filter($names));
    }

    public function getContextUid(): array|string|null
    {
        return str_replace('formie:', '', $this->context);
    }

    public function getType(): string
    {
        return static::class;
    }

    /**
     * @inheritDoc
     */
    public function hasLabel(): bool
    {
        return true;
    }

    public function hasSubfields(): bool
    {
        return false;
    }

    public function hasNestedFields(): bool
    {
        return false;
    }

    public function getIsCosmetic(): bool
    {
        return false;
    }

    public function getIsHidden(): bool
    {
        return $this->visibility === 'hidden';
    }

    /**
     * @inheritDoc
     */
    public function getExtraBaseFieldConfig(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getSavedSettings(): array
    {
        return $this->getSettings();
    }

    public function getSavedFieldConfig(): array
    {
        return $this->getAttributes(['id', 'name', 'handle', 'columnSuffix']);
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAllFieldDefaults(): array
    {
        $defaults = [
            'labelPosition' => '',
            'instructionsPosition' => '',
        ];

        // Combine any class-specified defaults
        if (method_exists($this, 'getFieldDefaults')) {
            $defaults = array_merge($defaults, $this->getFieldDefaults());
        }

        return $defaults;
    }

    public function getFieldValue($element, $handle = '', $attributePrefix = '')
    {
        // Allow handle to be overridden
        if (!$handle) {
            $handle = $this->handle;
        }

        // If we pass in an element (submission), fetch the value on that
        $value = $element->{$handle} ?? null;

        // If we pass in an array, fetch the value on that
        if (is_array($element)) {
            $value = $element[$handle] ?? null;
        }

        // Otherwise, check if there are any default values
        if ($value === null) {
            $defaultValue = $this->getDefaultValue($attributePrefix);

            if ($defaultValue !== null) {
                return $defaultValue;
            }
        }

        return $value;
    }

    public function getDefaultValue($attributePrefix = '')
    {
        $defaultValue = null;
        $defaultValueAttribute = 'defaultValue';
        $prePopulateAttribute = 'prePopulate';

        // Handle nested fields that supply their own attribute to fetch default values from
        if ($attributePrefix) {
            $defaultValueAttribute = "{$attributePrefix}DefaultValue";
            $prePopulateAttribute = "{$attributePrefix}PrePopulate";
        }

        // Check for a query string is configured
        if ($this->$prePopulateAttribute) {
            $queryParam = Craft::$app->getRequest()->getParam($this->$prePopulateAttribute);

            if ($queryParam !== null) {
                $defaultValue = $this->setPrePopulatedValue($queryParam);
            }
        }

        if (!$defaultValue) {
            $defaultValue = $this->$defaultValueAttribute;

            // Parse the default value for variables
            if (!is_array($defaultValue) && !is_object($defaultValue)) {
                // Don't do this for a hidden field, as we want to retain variable until the form it submitted,
                // to evaluate there. As such, the default value is more or less the value of the field.
                if (!($this instanceof Hidden)) {
                    $defaultValue = Variables::getParsedValue($defaultValue);
                }
            }
        }

        $event = new ModifyFieldValueEvent([
            'value' => $defaultValue,
            'field' => $this,
        ]);

        $this->trigger(static::EVENT_MODIFY_DEFAULT_VALUE, $event);

        return $event->value;
    }

    /**
     * @inheritDoc
     */
    public function getFieldSchema(): array
    {
        $tabs = [];
        $fields = [];

        // Define the tabs we have for editing a field. Only these can be used.
        $definedTabs = [
            'General',
            'Settings',
            'Appearance',
            'Advanced',
            'Conditions',
        ];

        foreach ($definedTabs as $definedTab) {
            $methodName = 'define' . $definedTab . 'Schema';

            if (method_exists($this, $methodName)) {
                if ($fieldSchema = $this->$methodName()) {
                    $tabLabel = Craft::t('formie', $definedTab);

                    // Add `name` and `id` attributes automatically for every FormKit input
                    SchemaHelper::setFieldAttributes($fieldSchema);

                    $fields[] = [
                        '$cmp' => 'TabPanel',
                        'attrs' => [
                            'data-tab-panel' => $tabLabel,
                        ],
                        'children' => $fieldSchema,
                    ];

                    $tabs[] = [
                        'label' => $tabLabel,
                        'fields' => SchemaHelper::extractFieldsFromSchema($fieldSchema),
                    ];
                }
            }
        }

        // Return the DOM schema for Vue to render
        return [
            'tabs' => $tabs,
            'fields' => [
                [
                    '$cmp' => 'TabPanels',
                    'attrs' => [
                        'class' => 'fui-modal-content',
                    ],
                    'children' => $fields,
                ],
            ],
        ];
    }

    public function renderHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        // Get the HtmlTag definition
        $tag = $this->defineHtmlTag($key, $context);

        if ($tag) {
            // The render options are stored on the form for efficiency, so they're only parsed once
            // even if passing in options via `craft.formie.renderField()`.
            $form = $context['form'] ?? $this->getForm();
            
            // Find if there's a config option for this key, either in plugin config or template render options
            $templateConfig = $form->getThemeConfigItem($key);

            // Check if this is a class-specific key (e.g. `singleLineText`) which will take precedence over
            // more general config, and merge them.
            $classTemplateConfig = $form->getThemeConfigItem(Html::getFieldClassKey($this) . '.' . $key);
            $config = Html::mergeHtmlConfigs([$key => $templateConfig], [$key => $classTemplateConfig])[$key] ?? [];

            // Check if the config is falsey - then don't render
            if ($config === false || $config === null) {
                $tag = null;
            } else {
                // Are we resetting classes globally?
                if ($form->resetClasses) {
                    $config['resetClass'] = true;
                }

                $tag->setFromConfig($config, $context);
            }
        }

        $event = new ModifyFieldHtmlTagEvent([
            'field' => $this,
            'tag' => $tag,
            'key' => $key,
            'context' => $context,
        ]);

        $this->trigger(static::EVENT_MODIFY_HTML_TAG, $event);

        return $event->tag;
    }

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        $form = $context['form'] ?? null;
        $submission = $context['element'] ?? null;
        $errors = $context['errors'] ?? null;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        if ($key === 'field') {
            $labelPosition = $context['labelPosition'] ?? null;
            $subfieldLabelPosition = $context['subfieldLabelPosition'] ?? null;
            $instructionsPosition = $context['instructionsPosition'] ?? null;
            $containerAttributes = $this->getContainerAttributes() ?? [];

            return new HtmlTag('div', array_merge([
                'class' => [
                    'fui-field',
                    'fui-type-' . StringHelper::toKebabCase($this->displayName()),
                    'fui-label-' . $labelPosition,
                    'fui-subfield-label-' . $subfieldLabelPosition,
                    'fui-instructions-' . $instructionsPosition,
                    $errors ? 'fui-field-error fui-error' : null,
                    $this->required ? 'fui-field-required' : null,
                    $this->getIsHidden() ? 'fui-hidden' : null,
                    $this->getParentField() ? 'fui-' . StringHelper::toKebabCase($this->getParentField()->displayName() . ' ' . $this->handle) : 'fui-page-field',
                ],
                'data' => [
                    'field-handle' => $this->handle,
                    'field-type' => StringHelper::toKebabCase($this->displayName()),
                    'field-config' => $this->getConfigJson(),
                    'field-conditions' => $this->getConditionsJson($submission),
                ],
            ], $containerAttributes), $this->cssClasses);
        }

        if ($key === 'fieldContainer') {
            return new HtmlTag('div', [
                'class' => 'fui-field-container',
            ]);
        }

        if ($key === 'fieldLabel') {
            if (!$this->hasLabel()) {
                return null;
            }

            return new HtmlTag('label', [
                'class' => 'fui-label',
                'for' => $id,
            ]);
        }

        if ($key === 'fieldInstructions') {
            return new HtmlTag('div', [
                'id' => "{$id}-instructions",
                'class' => 'fui-instructions',
            ]);
        }

        if ($key === 'fieldInputContainer') {
            return new HtmlTag('div', [
                'class' => 'fui-input-container',
            ]);
        }

        if ($key === 'fieldErrors') {
            return new HtmlTag('ul', [
                'class' => 'fui-errors',
            ]);
        }

        if ($key === 'fieldError') {
            return new HtmlTag('li', [
                'class' => 'fui-error-message',
            ]);
        }

        if ($key === 'subFieldRows') {
            return new HtmlTag('div', [
                'class' => 'fui-field-rows',
            ]);
        }

        if ($key === 'subFieldRow') {
            return new HtmlTag('div', [
                'class' => 'fui-row',
            ]);
        }

        if ($key === 'nestedFieldRows') {
            return new HtmlTag('div', [
                'class' => 'fui-field-rows',
            ]);
        }

        if ($key === 'nestedFieldRow') {
            return new HtmlTag('div', [
                'class' => 'fui-row',
            ]);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getBaseFieldConfig(): array
    {
        $labelPositions = Formie::$plugin->getFields()->getLabelPositionsArray($this);
        $instructionsPositions = Formie::$plugin->getFields()->getInstructionsPositionsArray($this);

        $config = [
            'type' => $this->getType(),
            'label' => static::displayName(),
            'defaults' => $this->getAllFieldDefaults(),
            'icon' => static::getSvgIcon(),
            'preview' => $this->getPreviewInputHtml(),
            'data' => $this->getExtraBaseFieldConfig(),
            'hasLabel' => $this->hasLabel(),
            'fieldsSchema' => $this->getFieldSchema()['fields'],
            'tabsSchema' => $this->getFieldSchema()['tabs'],

            // Field settings
            'labelPositions' => $labelPositions,
            'instructionsPositions' => $instructionsPositions,
        ];

        // Nested fields have rows of their own.
        if ($config['supportsNested'] = ($this instanceof NestedFieldInterface)) {
            /* @var NestedFieldInterface|NestedFieldTrait $field */
            $config['rows'] = [];
        }

        return $config;
    }

    /**
     * @inheritDoc
     */
    public function getContainerAttributes(): array
    {
        if (!$this->containerAttributes) {
            return [];
        }

        return ArrayHelper::map($this->containerAttributes, 'label', 'value');
    }

    /**
     * @inheritDoc
     */
    public function getInputAttributes(): array
    {
        if (!$this->inputAttributes) {
            return [];
        }

        return ArrayHelper::map($this->inputAttributes, 'label', 'value');
    }

    /**
     * @inheritDoc
     */
    public function getNamespace(): string
    {
        return $this->_namespace;
    }

    public function setNamespace($value): void
    {
        $this->_namespace = $value;
    }

    /**
     * @inheritDoc
     */
    public function getParentField(): ?FormFieldInterface
    {
        return $this->_parentField;
    }

    public function setParentField($value, $namespace = ''): void
    {
        $this->_parentField = $value;

        // Also, set the namespace (on the parent field), commonly just the field handle
        // But allows it to be added to (think Repeater).
        // Be sure to create a valid name attribute, from `fieldHandle` and `some[more][attrs]`
        // to `fieldHandle[some][some][attrs]`.
        if ($namespace) {
            $this->setNamespace(Html::namespaceInputName($namespace, $value->handle));
        } else {
            $this->setNamespace($value->handle);
        }
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndInputHtml(Form $form, mixed $value, array $renderOptions = []): Markup
    {
        if (!static::getFrontEndInputTemplatePath()) {
            return Template::raw('');
        }

        $inputOptions = $this->getFrontEndInputOptions($form, $value, $renderOptions);
        $html = $form->renderTemplate(static::getFrontEndInputTemplatePath(), $inputOptions);

        return Template::raw($html);
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndInputOptions(Form $form, mixed $value, array $renderOptions = []): array
    {
        // Check to see if we're overriding the field
        $field = $renderOptions['field'] ?? $this;

        // Remove some attributes from render options
        $errors = ArrayHelper::remove($renderOptions, 'errors');
        $submission = ArrayHelper::remove($renderOptions, 'submission');

        return [
            'form' => $form,
            'name' => $this->handle,
            'value' => $value,
            'field' => $field,
            'errors' => $errors,
            'submission' => $submission,
            'renderOptions' => $renderOptions,
        ];
    }

    public function applyRenderOptions(Form $form, array $renderOptions = []): void
    {
        /* @var Settings $pluginSettings */
        $pluginSettings = Formie::$plugin->getSettings();

        $fieldNamespace = $renderOptions['fieldNamespace'] ?? null;

        // Allow the use of falsey namespaces
        if ($fieldNamespace !== null) {
            $this->setNamespace($fieldNamespace);
        }

        $templateConfig = $renderOptions['themeConfig'] ?? [];

        if ($templateConfig) {
            $form->setThemeConfig($templateConfig);
        }
    }

    public function getFrontEndJsModules(): ?array
    {
        return null;
    }

    public function getConfigJson(): ?string
    {
        // From the provided JS module config, extract just the settings and module name
        // for use inline in the HTML. We load the scripts async, and rely on the HTML for
        // fields to output their config, so it's reliable and works for on-demand HTML (repeater)
        $modules = $this->getFrontEndJsModules();

        // Normalise to handle multiple module registrations
        if (!isset($modules[0])) {
            $modules = [$modules];
        }

        if ($modules) {
            $config = [];

            foreach ($modules as $module) {
                $settings = $module['settings'] ?? [];
                $settings['module'] = $module['module'] ?? '';
                $settings = array_filter($settings);

                if ($settings) {
                    $config[] = $settings;
                }
            }

            if ($config) {
                return Json::encode($config);
            }
        }

        return null;
    }

    public function hasConditions(): bool
    {
        return ($this->enableConditions && $this->getConditions());
    }

    public function getConditions(): array
    {
        // Filter out any un-set conditions
        $conditions = $this->conditions ?? [];
        $conditionRows = $conditions['conditions'] ?? [];

        foreach ($conditionRows as $key => $condition) {
            if (!($condition['condition'] ?? null)) {
                unset($conditions['conditions'][$key]);
            }
        }

        return $conditions;
    }

    public function getConditionsJson($element = null): ?string
    {
        if ($this->hasConditions()) {
            $conditionSettings = $this->getConditions();
            $conditions = $conditionSettings['conditions'] ?? [];

            $namespace = $this->getNamespace();

            // Prep the conditions for JS
            foreach ($conditions as &$condition) {
                ArrayHelper::remove($condition, 'id');

                // Dot-notation to name input syntax
                $condition['field'] = 'fields[' . str_replace(['{', '}', '.'], ['', '', ']['], $condition['field']) . ']';

                // A little extra work for Group/Repeater fields, which conditions would be set with `new1`.
                // When going back to a previous page this will be replaced with the blockId and the condition won't work.
                if ($element instanceof NestedFieldRow && $element->id) {
                    $condition['field'] = preg_replace('/\[new\d*\]/', "[$element->id]", $condition['field']);
                }
            }

            unset($condition);

            $conditionSettings['conditions'] = $conditions;

            // Check if this is a nested field within a Group/Repeater.
            $conditionSettings['isNested'] = (bool)strstr($this->context, 'formieField:');

            return Json::encode($conditionSettings);
        }

        return null;
    }

    public function getPage($submission)
    {
        $pages = $submission->getFieldPages();

        return $pages[$this->handle] ?? null;
    }

    /**
     * Returns whether the field has passed conditional evaluation and is hidden.
     */
    public function isConditionallyHidden(Submission|NestedFieldRow $element): bool
    {
        $isFieldHidden = false;
        $isPageHidden = false;

        // Always use the submission as the context for element data
        if ($element instanceof NestedFieldRow) {
            $element = $element->getOwner();
        }

        // Check if the field itself is hidden
        if ($this->enableConditions) {
            $conditionSettings = $this->getConditions();
            $conditions = $conditionSettings['conditions'] ?? [];

            if ($conditionSettings && $conditions) {
                // A `true` result means the field passed the evaluation and that it has a value, whilst a `false` result means
                // it didn't (for instance the field doesn't have a value)
                $result = ConditionsHelper::getConditionalTestResult($conditionSettings, $element);

                // Depending on if we show or hide the field when evaluating. If `false` and set to show, it means
                // the field is hidden and the conditions to show it isn't met. Therefore, report back that this field is hidden.
                if (($result && $conditionSettings['showRule'] !== 'show') || (!$result && $conditionSettings['showRule'] === 'show')) {
                    $isFieldHidden = true;
                }
            }
        }

        // Also check if the field is in a hidden page
        if (!$isFieldHidden && $page = $this->getPage($element)) {
            $isPageHidden = $page->isConditionallyHidden($element);
        }

        return $isFieldHidden || $isPageHidden;
    }

    /**
     * @inheritDoc
     */
    public function getEmailHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool
    {
        $inputOptions = $this->getEmailOptions($submission, $notification, $value, $renderOptions);
        $html = $notification->renderTemplate(static::getEmailTemplatePath(), $inputOptions);

        return Template::raw($html);
    }

    /**
     * @inheritDoc
     */
    public function getEmailOptions(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): array
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

    /**
     * @inheritDoc
     */
    public function defineGeneralSchema(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function defineSettingsSchema(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function defineAppearanceSchema(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function defineAdvancedSchema(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function afterCreateField(array $data): void
    {

    }

    public function getSettingGqlTypes(): array
    {
        $types = [];
        $excludedProperties = [];

        // Use reflections to grab most (if not all) properties and automate casting. To do this, we need to fetch 
        // properties that are _just_ from the individual classes not any inherited or through traits. The only way 
        // to handle this is to fetch all traits first, and diff them later on.
        $class = new ReflectionClass($this);

        foreach ($class->getTraits() as $trait) {
            foreach ($trait->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                $excludedProperties[] = $property->getName();
            }
        }

        $typeMap = [
            'string' => Type::string(),
            'int' => Type::int(),
            'float' => Type::float(),
            'bool' => Type::boolean(),
            'datetime' => DateTimeType::getType(),
        ];

        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic() && !$property->getDeclaringClass()->isAbstract() && !in_array($property->getName(), $excludedProperties)) {
                // If we haven't defined mapping, don't assume its value. It'll be up to classes to define these
                $propertyName = $property->getName();

                // Properties can have multiple types
                $propertyType = $property->getType();

                // Handle _some_ union types
                if ($propertyType instanceof ReflectionUnionType) {
                    // Special case for int|float
                    $names = array_map(fn(ReflectionNamedType $type) => $type->getName(), $propertyType->getTypes());
                    sort($names);

                    // For numbers, pick the type that can contain the most value
                    if ($names === ['float', 'int'] || $names === ['float', 'int', 'null']) {
                        $propertyTypeName = 'float';
                    }
                } else {
                    $propertyTypeName = $propertyType->getName();
                }

                $gqlType = $typeMap[$propertyTypeName] ?? null;

                if ($gqlType) {
                    $types[$propertyName] = [
                        'name' => $propertyName,
                        'type' => $gqlType,
                    ];
                } else if ($propertyTypeName === 'array') {
                    $types[$propertyName] = [
                        'name' => $propertyName,
                        'type' => Type::string(),
                        'resolve' => function($field) use ($propertyName) {
                            $value = $field->{$propertyName};

                            return is_array($value) ? Json::encode($value) : $value;
                        },
                    ];
                }
            }
        }

        return $types;
    }

    public function getGqlTypeName(): string
    {
        $classNameParts = explode('\\', static::class);
        $end = array_pop($classNameParts);

        return 'Field_' . $end;
    }

    public function validate($attributeNames = null, $clearErrors = true)
    {
        $refId = null;

        // Watch out for synced field IDs for Postgres because it will fails to match `sync:123` against an int
        // But probably a good idea to check against this anyway, in general.
        if ($this->getIsRef()) {
            $refId = $this->id;
            $this->id = Formie::$plugin->getSyncs()->parseSyncId($this->id)->id ?? null;
        }

        $validates = parent::validate($attributeNames, $clearErrors);

        // Add it back
        if ($refId) {
            $this->id = $refId;
        }

        return $validates;
    }

    public function getExportLabel(ElementInterface $element): string
    {
        // Check to see if there's another field with the same label
        if ($fieldLayout = $element->getFieldLayout()) {
            foreach ($fieldLayout->getCustomFields() as $field) {
                if ($field->id === $this->id) {
                    continue;
                }

                if ($field->name === $this->name) {
                    return $this->name . ' (' . $this->handle . ')';
                }
            }
        }

        return $this->name;
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        // Find the existing handle validation rules from the base field and remove some options
        foreach ($rules as $ruleKey => $rule) {
            $attribute = $rule[0][0] ?? '';
            $reservedWords = $rule['reservedWords'] ?? null;

            if ($attribute === 'handle' && $reservedWords) {
                ArrayHelper::removeValue($rules[$ruleKey]['reservedWords'], 'username');
            }
        }

        $rules[] = [['placeholder', 'errorMessage', 'cssClasses'], 'string', 'max' => 255];

        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => self::_getReservedWords(),
        ];

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
    
    /**
     * @inheritDoc
     */
    protected function setPrePopulatedValue($value)
    {
        return $value;
    }

    protected function defineValueAsString($value, ElementInterface $element = null): string
    {
        return (string)$value;
    }

    protected function defineValueAsJson($value, ElementInterface $element = null): mixed
    {
        return Json::decode(Json::encode($value));
    }

    protected function defineValueForExport($value, ElementInterface $element = null): mixed
    {
        // A string-representation will largely suit our needs
        return $this->defineValueAsString($value, $element);
    }

    protected function defineValueForIntegration($value, $integrationField, $integration, ElementInterface $element = null, $fieldKey = ''): mixed
    {
        $fieldValue = $this->defineValueAsString($value, $element);

        // Special case for array fields, we should be using the `defineValueAsJson()` function
        if ($integrationField->getType() === IntegrationField::TYPE_ARRAY) {
            $fieldValue = $this->defineValueAsJson($value, $element);
        }

        return Integration::convertValueForIntegration($fieldValue, $integrationField);
    }

    protected function defineValueForSummary($value, ElementInterface $element = null): string
    {
        // A string-representation will largely suit our needs
        return $this->defineValueAsString($value, $element);
    }

    protected function defineValueForEmail($value, $notification, ElementInterface $element = null): string
    {
        // A string-representation will largely suit our needs
        return $this->defineValueAsString($value, $element);
    }


    // Private Methods
    // =========================================================================

    private static function normalizeConfig(array &$config = [])
    {
        // Normalise the config from Formie v1 to v2. This is a bit more reliable than a migration
        // updating all field settings, as the presence of these properties in field classes that don't
        // support them would be otherwise catastrophic, and blow up people's CP's.
        // Eventually, these can be removed at the next breakpoint, as users re-save their fields.
        if (array_key_exists('columnWidth', $config)) {
            unset($config['columnWidth']);
        }

        $supportedLimitConfigTypes = [
            formfields\MultiLineText::class,
            formfields\SingleLineText::class,
        ];

        $supportedLimitTypes = [
            formfields\Categories::class,
            formfields\Entries::class,
            formfields\FileUpload::class,
            formfields\MultiLineText::class,
            formfields\Number::class,
            formfields\Products::class,
            formfields\SingleLineText::class,
            formfields\Tags::class,
            formfields\Users::class,
            formfields\Variants::class,
        ];

        if (array_key_exists('limitType', $config)) {
            if (!in_array(static::class, $supportedLimitConfigTypes)) {
                unset($config['limitType']);
            }
        }

        if (array_key_exists('limitAmount', $config)) {
            if (!in_array(static::class, $supportedLimitConfigTypes)) {
                unset($config['limitAmount']);
            }
        }

        if (array_key_exists('limit', $config)) {
            if (!in_array(static::class, $supportedLimitTypes)) {
                unset($config['limit']);
            }
        }

        // Migrate field positions (particularly if importing from an older system)
        if (array_key_exists('instructionsPosition', $config)) {
            if ($config['instructionsPosition'] === 'verbb\\formie\\positions\\FieldsetStart') {
                $config['instructionsPosition'] = AboveInput::class;
            }

            if ($config['instructionsPosition'] === 'verbb\\formie\\positions\\FieldsetEnd') {
                $config['instructionsPosition'] = BelowInput::class;
            }
        }
    }

    /**
     * Returns the kebab-case name of the field class.
     *
     * @return string
     */
    private static function _getKebabName(): string
    {
        $classNameParts = explode('\\', static::class);
        $end = array_pop($classNameParts);

        return StringHelper::toKebabCase($end);
    }

    private static function _getReservedWords(): array
    {
        $reservedWords = [
            ['form', 'field', 'submission'],
        ];

        try {
            // Add public properties from submission class
            $reflection = new ReflectionClass(Submission::class);
            $reservedWords[] = array_map(function($prop) {
                return $prop->name;
            }, $reflection->getProperties(ReflectionProperty::IS_PUBLIC));

            // Add public properties from form class
            $reflection = new ReflectionClass(Form::class);
            $reservedWords[] = array_map(function($prop) {
                return $prop->name;
            }, $reflection->getProperties(ReflectionProperty::IS_PUBLIC));
        } catch (Throwable $e) {

        }

        return array_values(array_unique(array_merge(...$reservedWords)));
    }

    private function _getFullHandle()
    {
        $handles = [];

        // Get the namespace for each field, including parent fields
        $field = $this;

        while ($field) {
            // Be sure to prepend parent fields, as we're going deepest outward
            array_unshift($handles, $field->handle);

            $field = $field->getParentField();
        }

        return $handles;
    }

    private function _getFullNamespace()
    {
        $names = [];

        // Get the namespace for each field, including parent fields
        $field = $this;

        while ($field) {
            // Be sure to prepend parent fields, as we're going deepest outward
            array_unshift($names, $field->getNamespace());

            $field = $field->getParentField();
        }

        return $names;
    }
}
