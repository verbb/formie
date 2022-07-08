<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\NestedFieldRow;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFieldValueEvent;
use verbb\formie\events\ModifyFieldEmailValueEvent;
use verbb\formie\events\ModifyFieldIntegrationValueEvent;
use verbb\formie\events\ParseMappedFieldValueEvent;
use verbb\formie\fields\formfields\BaseOptionsField;
use verbb\formie\fields\formfields\Hidden;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\gql\types\generators\FieldAttributeGenerator;
use verbb\formie\gql\types\generators\KeyValueGenerator;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;
use craft\gql\types\DateTime as DateTimeType;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\helpers\StringHelper;
use craft\validators\HandleValidator;
use craft\web\twig\TemplateLoaderException;

use GraphQL\Type\Definition\Type;

use yii\base\Event;

use Twig\Error\LoaderError as TwigLoaderError;
use Twig\Markup;

use ReflectionClass;
use ReflectionProperty;
use Exception;
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


    // Public Properties
    // =========================================================================

    public $columnWidth;
    public $limit;
    public $limitType;
    public $limitAmount;
    public $matchField;
    public $placeholder;
    public $defaultValue;
    public $prePopulate;
    public $errorMessage;
    public $labelPosition;
    public $instructionsPosition;
    public $cssClasses;
    public $containerAttributes;
    public $inputAttributes;
    public $includeInEmail = true;
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
     * @var string
     */
    public $rowUid;

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
    /**
     * @var NestedFieldInterface
     */
    private $_container;
    private $_namespace = 'fields';


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
     * @inheritdoc
     */
    public function getValueAsString($value, ElementInterface $element = null)
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

    /**
     * @inheritdoc
     */
    public function getValueAsJson($value, ElementInterface $element = null)
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

    /**
     * @inheritdoc
     */
    public function getValueForExport($value, ElementInterface $element = null)
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

    /**
     * @inheritdoc
     */
    public function getValueForIntegration($value, $integrationField, $integration, ElementInterface $element = null, $fieldKey = '')
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

    /**
     * @inheritdoc
     */
    public function getValueForSummary($value, ElementInterface $element = null)
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

    /**
     * @inheritdoc
     */
    public function getValueForEmail($value, $notification, ElementInterface $element = null)
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

    /**
     * @inheritDoc
     */
    public function validateMatchField(ElementInterface $element)
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
    public function getGqlFieldContext()
    {
        return $this->isNested ? $this->getContainer() : $this->getForm();
    }

    /**
     * Set the container for a nested field.
     *
     * @param NestedFieldInterface $container
     */
    public function setContainer(NestedFieldInterface $container)
    {
        $this->_container = $container;
    }

    /**
     * Return the container if this is a nested field.
     *
     * @param NestedFieldInterface $container
     */
    public function getContainer()
    {
        return $this->_container;
    }

    /**
     * @return Form|null
     */
    public function getForm()
    {
        if (!$this->formId) {
            // Try and fetch the form via the UID from the context
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
        return StringHelper::toKebabCase($form->formId . ' ' . $this->handle);
    }

    /**
     * @inheritDoc
     */
    public function getHtmlDataId(Form $form)
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
    public function getContextUid()
    {
        return str_replace('formie:', '', $this->context);
    }

    /**
     * @inheritDoc
     */
    public function getType()
    {
        return static::class;
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
    public function hasNestedFields(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getIsCosmetic(): bool
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
        $config = $this->getAttributes(['id', 'name', 'handle']);

        // TODO: remove schema version condition after next beakpoint
        $schemaVersion = Craft::$app->getInstalledSchemaVersion();

        if (version_compare($schemaVersion, '3.7.0', '>=')) {
            $config = array_merge($config, $this->getAttributes(['columnSuffix']));
        }

        return $config;
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
            'type' => $this->getType(),
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

        $inputOptions = $this->getFrontEndInputOptions($form, $value, $options);
        $html = $form->renderTemplate(static::getFrontEndInputTemplatePath(), $inputOptions);

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

        // Allow the use of falsey namespaces
        if ($fieldNamespace !== null) {
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
    public function hasConditions()
    {
        $conditionSettings = Json::decode($this->conditions) ?? [];

        return ($this->enableConditions && $conditionSettings);
    }

    /**
     * @inheritDoc
     */
    public function getConditionsJson($element = null)
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

                // A little extra work for Group/Repeater fields, which conditions would be set with `new1`.
                // When going back to a previous page this will be replaced with the blockId and the condition won't work.
                if ($element instanceof NestedFieldRow && $element->id) {
                    $condition['field'] = preg_replace('/\[new\d*\]/', "[$element->id]", $condition['field']);
                }
            }

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
    public function isConditionallyHidden($submission)
    {
        $isFieldHidden = false;
        $isPageHidden = false;

        // Check if the field itself is hidden
        if ($this->enableConditions) {
            $conditionSettings = Json::decode($this->conditions) ?? [];
            $conditions = $conditionSettings['conditions'] ?? [];

            if ($conditionSettings && $conditions) {
                // A `true` result means the field passed the evaluation and that it has a value, whilst a `false` result means
                // it didn't (for instance the field doesn't have a value)
                $result = ConditionsHelper::getConditionalTestResult($conditionSettings, $submission);

                // Depending on if we show or hide the field when evaluating. If `false` and set to show, it means
                // the field is hidden and the conditions to show it aren't met. Therefore, report back that this field is hidden.
                if (($result && $conditionSettings['showRule'] !== 'show') || (!$result && $conditionSettings['showRule'] === 'show')) {
                    $isFieldHidden = true;
                }
            }
        }

        // Also check if the field is in a hidden page
        if (!$isFieldHidden) {
            if ($page = $this->getPage($submission)) {
                $isPageHidden = $page->isConditionallyHidden($submission);
            }
        }

        return $isFieldHidden || $isPageHidden;
    }

    /**
     * @inheritDoc
     */
    public function getEmailHtml(Submission $submission, Notification $notification, $value, array $options = null)
    {
        $inputOptions = $this->getEmailOptions($submission, $notification, $value, $options);
        $html = $notification->renderTemplate(static::getEmailTemplatePath(), $inputOptions);

        return Template::raw($html);
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

    /**
     * @inheritDoc
     */
    public function getGqlTypeName()
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


    // Protected Methods
    // =========================================================================

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
            'reservedWords' => self::_getReservedWords(),
        ];

        $rules[] = [
            ['limitType'], 'in', 'range' => [
                'characters',
                'words',
            ],
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

    /**
     * @inheritdoc
     */
    protected function defineValueAsString($value, ElementInterface $element = null)
    {
        return (string)$value;
    }

    /**
     * @inheritdoc
     */
    protected function defineValueAsJson($value, ElementInterface $element = null)
    {
        return Json::decode(Json::encode($value));
    }

    /**
     * @inheritdoc
     */
    protected function defineValueForExport($value, ElementInterface $element = null)
    {
        // A string-representaion will largely suit our needs
        return $this->defineValueAsString($value, $element);
    }

    /**
     * @inheritdoc
     */
    protected function defineValueForIntegration($value, $integrationField, $integration, ElementInterface $element = null, $fieldKey = '')
    {
        $fieldValue = $this->defineValueAsString($value, $element);

        // Special case for array fields, we should be using the `defineValueAsJson()` function
        if ($integrationField->getType() === IntegrationField::TYPE_ARRAY) {
            $fieldValue = $this->defineValueAsJson($value, $element);
        }

        return Integration::convertValueForIntegration($fieldValue, $integrationField);
    }

    /**
     * @inheritdoc
     */
    protected function defineValueForSummary($value, ElementInterface $element = null)
    {
        // A string-representaion will largely suit our needs
        return $this->defineValueAsString($value, $element);
    }

    /**
     * @inheritdoc
     */
    protected function defineValueForEmail($value, $notification, ElementInterface $element = null)
    {
        // A string-representaion will largely suit our needs
        return $this->defineValueAsString($value, $element);
    }

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
            'includeInEmail' => Type::boolean(),
            'enableContentEncryption' => Type::boolean(),
            'enableConditions' => Type::boolean(),
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

    /**
     * @inheritdoc
     */
    private static function _getReservedWords()
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
}
