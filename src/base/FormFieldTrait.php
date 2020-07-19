<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\formfields\BaseOptionsField;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\Template;
use craft\helpers\StringHelper;
use craft\validators\HandleValidator;

use Twig\Markup;
use ReflectionClass;
use Throwable;

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
    public $errorMessage;
    public $labelPosition;
    public $instructionsPosition;
    public $cssClasses;
    public $containerAttributes;
    public $inputAttributes;

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


    // Private Properties
    // =========================================================================

    /**
     * @var Form
     */
    private $_form;


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
        return 'fields';
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
        return [
            'form' => $form,
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'options' => $options,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getEmailHtml(Submission $submission, $value, array $options = null)
    {
        if (!static::getEmailTemplatePath()) {
            // Nice an simple for most cases - no need for a template file
            try {
                $content = (string)$value;
                $hideName = $options['hideName'] ?? false;
                if (!$hideName) {
                    $content = Html::tag('strong', $this->name) . '<br>' . $content;
                }

                return Html::tag('p', $content);
            } catch (Throwable $e) {
                Formie::error('Failed to render email field content: ' . $e->getMessage());
            }

            return '';
        }

        $view = Craft::$app->getView();
        $oldTemplatesPath = $view->getTemplatesPath();
        $templatesPath = Formie::$plugin->getRendering()->getEmailComponentTemplatePath($submission->notification, static::getEmailTemplatePath());
        $view->setTemplatesPath($templatesPath);

        $inputOptions = $this->getEmailOptions($submission, $value, $options);
        $html = Craft::$app->getView()->renderTemplate(static::getEmailTemplatePath(), $inputOptions);

        $view->setTemplatesPath($oldTemplatesPath);

        return Template::raw($html);
    }

    /**
     * @inheritDoc
     */
    public function getEmailOptions(Submission $submission, $value, array $options = null): array
    {
        return [
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
    public function afterCreateField()
    {

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
