<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\formfields\BaseOptionsField;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\gql\types\generators\FieldAttributeGenerator;
use verbb\formie\gql\types\generators\KeyValueGenerator;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;
use craft\gql\types\DateTime as DateTimeType;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\helpers\StringHelper;
use craft\validators\HandleValidator;

use Exception;
use GraphQL\Type\Definition\Type;
use ReflectionClass;
use Throwable;
use Twig\Error\LoaderError as TwigLoaderError;
use Twig\Markup;

trait FormFieldTrait
{
    // Public Properties
    // =========================================================================

    public $columnWidth;
    public $limit;
    public $limitType;
    public $limitAmount;
    public $placeholder;
    public $defaultValue;
    public $prePopulate;
    public $errorMessage;
    public $labelPosition;
    public $instructionsPosition;
    public $cssClasses;
    public $containerAttributes;
    public $inputAttributes;
    public $enableConditions;
    public $conditions;
    public $enableContentEncryption = false;
    public $visibility;

    /**
     * @var int
     */
    public $formId;

    /**
     * @var int
     */
    public $rowId;

    /**
     * @var int
     */
    public $rowIndex;

    /**
     * @var bool
     */
    public $isNested = false;


    // Private Properties
    // =========================================================================

    /**
     * @var Form
     */
    private $_form;
    private $_namespace = 'fields';


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


    // Public Methods
    // =========================================================================

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
        return $this->id && strpos($this->id, 'sync:') === 0;
    }

    /**
     * @inheritDoc
     */
    public function getValue(ElementInterface $element)
    {
        return $element->getFieldValue($this->handle);
    }

    /**
     * @inheritdoc
     */
    public function serializeValue($value, ElementInterface $element = null)
    {
        $value = parent::serializeValue($value, $element);

        // Handle if we need to save field content as encrypted
        if ($this->enableContentEncryption) {
            if (is_string($value)) {
                $value = StringHelper::encenc($value);
            }
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function serializeValueForWebhook($value, ElementInterface $element = null)
    {
        return parent::serializeValue($value, $element);
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        $value = parent::normalizeValue($value, $element);

        // Check if the string contains a previously encypted version, or the field is enabled
        // This might occur if the field was set to encrypted, but changed later. We still need to
        // decrypt field content
        if (is_string($value)) {
            if ($this->enableContentEncryption || strpos($value, 'base64:') !== false) {
                $value = StringHelper::decdec($value);
            }
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function populateValue($value)
    {
        $this->defaultValue = $value;
    }

    /**
     * @inheritDoc
     */
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

        if ($class->isSubclassOf(FormField::class)) {
            while (true) {
                $traits = array_merge($traits, $parent->getTraits());
                $parent = $parent->getParentClass();

                if ($parent->name !== FormField::class) {
                    break;
                }
            }
        }

        if ($class->isSubclassOf(BaseOptionsField::class)) {
            while (true) {
                $traits = array_merge($traits, $parent->getTraits());
                $parent = $parent->getParentClass();

                if ($parent->name !== BaseOptionsField::class) {
                    break;
                }
            }
        }

        foreach ($traits as $trait) {
            foreach ($trait->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                if (!$property->isStatic() && !$property->getDeclaringClass()->isAbstract()) {
                    $names[] = $property->getName();
                }
            }
        }

        $names = array_unique($names);
        ArrayHelper::removeValue($names, 'rowId');
        ArrayHelper::removeValue($names, 'rowIndex');

        return $names;
    }

    /**
     * @inheritDoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['columnWidth', 'limitAmount'], 'number', 'integerOnly' => true];
        $rules[] = [['placeholder', 'errorMessage', 'cssClasses'], 'string', 'max' => 255];

        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => [
                'form',
                'field',
                'submission',
            ]
        ];

        $rules[] = [['limitType'], 'in', 'range' => [
            'characters',
            'words',
        ]];

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
     * @return Form|null
     */
    public function getForm()
    {
        if (!$this->formId) {
            return null;
        }

        if ($this->_form) {
            return $this->_form;
        }

        return $this->_form = Form::find()->id($this->formId)->one();
    }

    /**
     * @inheritDoc
     */
    public function hasLabel(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function renderLabel(): bool
    {
        return $this->hasLabel();
    }

    /**
     * @inheritDoc
     */
    public function getHtmlId(Form $form)
    {
        return StringHelper::toKebabCase($form->handle . ' ' . $this->handle);
    }

    /**
     * @inheritDoc
     */
    public function getHtmlWrapperId(Form $form)
    {
        return StringHelper::toKebabCase($this->namespace . ' ' . $this->getHtmlId($form) . ' wrap');
    }

    /**
     * @inheritDoc
     */
    public function getIsTextInput(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getIsSelect(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getIsFieldset(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function hasSubfields(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function getSavedFieldConfig(): array
    {
        return $this->getAttributes(['id', 'name', 'handle']);
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
            'limitType' => 'characters',
        ];

        // Combine any class-specified defaults
        if (method_exists($this, 'getFieldDefaults')) {
            $defaults = array_merge($defaults, $this->getFieldDefaults());
        }

        return $defaults;
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function getDefaultValue($attributePrefix = '')
    {
        $defaultValueAttribute = 'defaultValue';
        $prePopulateAttribute = 'prePopulate';

        // Handle nested fields that supply their own attribute to fetch default values from
        if ($attributePrefix) {
            $defaultValueAttribute = "{$attributePrefix}DefaultValue";
            $prePopulateAttribute = "{$attributePrefix}PrePopulate";
        }

        $value = $this->$defaultValueAttribute;

        // Check for a query string is configured
        if ($this->$prePopulateAttribute) {
            $queryParam = Craft::$app->getRequest()->getParam($this->$prePopulateAttribute);

            if ($queryParam !== null) {
                return $queryParam;
            }
        }

        // Parse the default value for variables
        if (!is_array($value) && !is_object($value)) {
            $value = Variables::getParsedValue($value);
        }

        return $value;
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

            if (method_exists($this, $methodName) && $this->$methodName()) {
                $tabLabel = Craft::t('formie', $definedTab);

                $fieldSchema = $this->$methodName();

                // Formulate uses the name instead of the label for the validation error, so change that
                SchemaHelper::setFieldValidationName($fieldSchema);

                $fields[] = [
                    'component' => 'tab-panel',
                    'data-tab-panel' => $tabLabel,
                    'children' => $fieldSchema,
                ];

                $tabs[] = [
                    'label' => $tabLabel,
                    'fields' => SchemaHelper::extractFieldsFromSchema($fieldSchema),
                ];
            }
        }

        // Return the DOM schema for Vue to render
        return [
            'tabs' => $tabs,
            'fields' => [
                [
                    'component' => 'tab-panels',
                    'class' => 'fui-modal-content',
                    'children' => $fields,
                ],
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getBaseFieldConfig(): array
    {
        $labelPositions = Formie::$plugin->getFields()->getLabelPositionsArray($this);
        $instructionsPositions = Formie::$plugin->getFields()->getInstructionsPositionsArray($this);

        $config = [
            'type' => static::class,
            'label' => $this->displayName(),
            'defaults' => $this->getAllFieldDefaults(),
            'icon' => $this->getSvgIcon(),
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
        if ($config['supportsNested'] = $this instanceof NestedFieldInterface) {
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

    /**
     * @inheritDoc
     */
    public function setNamespace($value)
    {
        $this->_namespace = $value;
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndInputHtml(Form $form, $value, array $options = null): Markup
    {
        if (!static::getFrontEndInputTemplatePath()) {
            return Template::raw('');
        }

        $view = Craft::$app->getView();
        $oldTemplatesPath = $view->getTemplatesPath();
        $templatesPath = Formie::$plugin->getRendering()->getFormComponentTemplatePath($form, static::getFrontEndInputTemplatePath());
        $view->setTemplatesPath($templatesPath);

        $inputOptions = $this->getFrontEndInputOptions($form, $value, $options);
        $html = Craft::$app->getView()->renderTemplate(static::getFrontEndInputTemplatePath(), $inputOptions);

        $view->setTemplatesPath($oldTemplatesPath);

        return Template::raw($html);
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndInputOptions(Form $form, $value, array $options = null): array
    {
        // Check to see if we're overriding the field
        $field = $options['field'] ?? $this;

        return [
            'form' => $form,
            'name' => $this->handle,
            'value' => $value,
            'field' => $field,
            'options' => $options,
        ];
    }

    /**
     * @inheritDoc
     */
    public function applyRenderOptions(array $options = null)
    {
        // Expand this as we allow more field options in render functions
        $fieldNamespace = $options['fieldNamespace'] ?? null;

        if ($fieldNamespace) {
            $this->setNamespace($fieldNamespace);
        }
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndJsModules()
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getConfigJson()
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

    /**
     * @inheritDoc
     */
    public function getConditionsJson()
    {
        if ($this->enableConditions) {
            $conditionSettings = Json::decode($this->conditions) ?? [];
            $conditions = $conditionSettings['conditions'] ?? [];

            $namespace = $this->getNamespace();

            // Prep the conditions for JS
            foreach ($conditions as &$condition) {
                ArrayHelper::remove($condition, 'id');

                // Dot-notation to name input syntax
                $condition['field'] = $namespace . '[' . str_replace(['{', '}', '.'], ['', '', ']['], $condition['field']) . ']';
            }

            $conditionSettings['conditions'] = $conditions;

            return Json::encode($conditionSettings);
        }

        return null;
    }

    /**
     * Returns whether the field has passed conditional evaluation and is hidden.
     */
    public function isConditionallyHidden($submission)
    {
        if ($this->enableConditions) {
            $conditionSettings = Json::decode($this->conditions) ?? [];

            if ($conditionSettings) {
                // A `true` result means the field passed the evaluation and that it has a value, whilst a `false` result means
                // it didn't (for instance the field doesn't have a value)
                $result = ConditionsHelper::getConditionalTestResult($conditionSettings, $submission);

                // Depending on if we show or hide the field when evaluating. If `false` and set to show, it means
                // the field is hidden and the conditions to show it aren't met. Therefore, report back that this field is hidden.
                if (($result && $conditionSettings['showRule'] !== 'show') || (!$result && $conditionSettings['showRule'] === 'show')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getEmailHtml(Submission $submission, Notification $notification, $value, array $options = null)
    {
        $view = Craft::$app->getView();
        $oldTemplatesPath = $view->getTemplatesPath();

        try {
            $templatesPath = Formie::$plugin->getRendering()->getEmailComponentTemplatePath($notification, static::getEmailTemplatePath());

            $view->setTemplatesPath($templatesPath);

            $inputOptions = $this->getEmailOptions($submission, $notification, $value, $options);
            $html = Craft::$app->getView()->renderTemplate(static::getEmailTemplatePath(), $inputOptions);
            $html = Template::raw($html);
        } catch (Exception $e) {
            // Nice an simple for most cases - no need for a template file
            try {
                $content = (string)($value ? $value : Craft::t('formie', 'No response.'));
                $hideName = $options['hideName'] ?? false;

                if (!$hideName) {
                    $content = Html::tag('strong', Craft::t('site', $this->name)) . '<br>' . $content;
                }

                $html = Html::tag('p', $content);
            } catch (Throwable $e) {
                $html = '';
                Formie::error('Failed to render email field content: ' . $e->getMessage());
            }
        }

        $view->setTemplatesPath($oldTemplatesPath);

        return $html;
    }

    /**
     * @inheritDoc
     */
    public function getEmailOptions(Submission $submission, Notification $notification, $value, array $options = null): array
    {
        return [
            'notification' => $notification,
            'submission' => $submission,
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'options' => $options,
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
    public function afterCreateField(array $data)
    {

    }

    /**
     * @inheritDoc
     */
    public function getSettingGqlTypes()
    {
        // Prepare a key-value of handle and type settings for GQL
        $fieldSchema = $this->getFieldSchema();

        // Now we have our Schema-based types, we should convert those to GQL types
        $fieldTypes = SchemaHelper::extractFieldInfoFromSchema($fieldSchema['fields']);
        $gqlSettingTypes = [];

        foreach ($this->getSettings() as $attribute => $setting) {
            $fieldInfo = $fieldTypes[$attribute] ?? [];
            $schemaType = $fieldInfo['type'] ?? $fieldInfo['component'] ?? 'text';

            $gqlAttribute = $this->getSettingGqlType($attribute, $schemaType, $fieldInfo);

            if ($gqlAttribute) {
                $gqlSettingTypes[$attribute] = $gqlAttribute;
            }
        }

        return $gqlSettingTypes;
    }

    public function getGqlTypeName()
    {
        $classNameParts = explode('\\', static::class);
        $end = array_pop($classNameParts);

        return 'Field_' . $end;
    }


    // Protected Methods
    // =========================================================================

    /**
     * Returns the GraphQL-equivalent datatype based on a provided field's handle or schema type
     */
    protected function getSettingGqlType($attribute, $type, $fieldInfo)
    {
        // Define any non-string properties
        $attributesDefinitions = [
            'containerAttributes' => Type::listOf(FieldAttributeGenerator::generateType()),
            'inputAttributes' => Type::listOf(FieldAttributeGenerator::generateType()),
            'required' => Type::boolean(),
            'limit' => Type::boolean(),
            'multiple' => Type::boolean(),
            'limitAmount' => Type::int(),
        ];

        $attributesDefinition = $attributesDefinitions[$attribute] ?? null;

        if ($attributesDefinition) {
            return [
                'name' => $attribute,
                'type' => $attributesDefinition,
            ];
        }

        $typeDefinitions = [
            'lightswitch' => Type::boolean(),
            'date' => DateTimeType::getType(),
        ];

        $typeDefinition = $typeDefinitions[$type] ?? null;

        if ($typeDefinition) {
            return [
                'name' => $attribute,
                'type' => $typeDefinition,
            ];
        }

        if ($type === 'table-block') {
            $columns = [
                'label' => Type::string(),
                'heading' => Type::string(),
                'value' => Type::string(),
                'handle' => Type::string(),
                'width' => Type::string(),
                'type' => Type::string(),
                'isOptgroup' => Type::boolean(),
                'optgroup' => Type::boolean(),
                'isDefault' => Type::boolean(),
                'default' => Type::boolean(),
            ];

            $fieldColumns = $fieldInfo['columns'] ?? [];

            // Figure something out with table defaults. It almost can't be done because we're
            // getting this information from the class, not an instance of the field.
            if (!is_array($fieldColumns)) {

            }

            $typeArray = KeyValueGenerator::generateTypes($this, $columns);

            return Type::listOf(array_pop($typeArray));
        }

        // Special case for these as they're not schema-defined fields
        if (strstr($attribute, 'Enabled') || strstr($attribute, 'Collapsed')) {
            return Type::boolean();
        }

        return Type::string();
    }


    // Private Methods
    // =========================================================================

    /**
     * Returns the kebab-case name of the field class.
     *
     * @return string
     */
    private static function _getKebabName()
    {
        $classNameParts = explode('\\', static::class);
        $end = array_pop($classNameParts);

        return StringHelper::toKebabCase($end);
    }
}
