<?php
namespace verbb\formie\fields;

use verbb\formie\base\FieldInterface;
use verbb\formie\base\OptionsField;
use verbb\formie\base\SortableFieldInterface;
use verbb\formie\fields\traits\AutocompleteFieldTrait;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\ValidationMessagesHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\SlotTag;

use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Localization;
use craft\i18n\Locale;
use craft\validators\ArrayValidator;

use Faker\Generator as FakerFactory;

class Dropdown extends OptionsField implements SortableFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Dropdown');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/dropdown/icon.svg';
    }


    // Traits
    // =========================================================================

    use AutocompleteFieldTrait;
    

    // Properties
    // =========================================================================

    public bool $optgroups = true;
    public bool $limitOptions = false;
    public int|float|null $min = null;
    public int|float|null $max = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Normalize number settings
        foreach (['min', 'max'] as $name) {
            if (isset($config[$name]) && is_array($config[$name])) {
                $config[$name] = Localization::normalizeNumber($config[$name]['value'], $config[$name]['locale']);
            }
        }

        parent::__construct($config);
    }

    public function getDefaultOptions(): array
    {
        return [
            [
                'label' => Craft::t('formie', 'Select an option'),
                'value' => '',
                'optgroup' => false,
                'default' => true,
            ],
        ];
    }

    public function getFieldOptions(): array
    {
        $options = parent::getFieldOptions();

        if ($this->placeholder) {
            array_unshift($options, ['label' => $this->placeholder, 'value' => '']);
        }

        return $options;
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        if ($this->limitOptions) {
            $rules[] = [$this->handle, 'validateLimitOptions', 'skipOnEmpty' => false];
        }

        return $rules;
    }

    public function validateLimitOptions(ElementInterface $element): void
    {
        if ($this->limitOptions) {
            $arrayValidator = new ArrayValidator([
                'min' => $this->min ?: null,
                'max' => $this->max ?: null,
                'tooFew' => $this->min ? $this->getValidationMessage(ValidationMessagesHelper::KEY_MIN_OPTIONS, [
                    'min' => $this->min,
                ]) : null,
                'tooMany' => $this->max ? $this->getValidationMessage(ValidationMessagesHelper::KEY_MAX_OPTIONS, [
                    'max' => $this->max,
                ]) : null,
                'skipOnEmpty' => false,
            ]);

            $value = $element->getFieldValue($this->valueKey());

            if (!$arrayValidator->validate($value, $error)) {
                $element->addError($this->valueKey(), $error);
            }
        }
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewSelect(),
        ];
    }

    public function getFormBuilderSettings(): array
    {
        $settings = parent::getFormBuilderSettings();

        foreach ($settings['options'] as &$option) {
            if (isset($option['optgroup']) && $option['optgroup']) {
                $option['optgroup'] = true;
                $option['label'] = ArrayHelper::remove($option, 'optgroup');
            } else {
                $option['optgroup'] = false;
            }
        }

        return $settings;
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            ...$this->defineOptionDynamicGeneralSchema(),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Allow Multiple'),
                'instructions' => Craft::t('formie', 'Whether this field should allow multiple options to be selected.'),
                'name' => 'multi',
            ]),
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Static Options'),
                'instructions' => Craft::t('formie', 'Add, remove, or reorder option rows manually.'),
                'name' => 'options',
                'if' => 'optionsMode == "static"',
                'enableOptionRowMenu' => true,
                'enableBulkOptions' => true,
                'predefinedOptions' => $this->getPredefinedOptions(),
                'newRowDefaults' => [
                    'optgroup' => false,
                    'default' => false,
                ],
                'columns' => [
                    [
                        'type' => 'checkbox',
                        'name' => 'optgroup',
                        'label' => Craft::t('formie', 'Optgroup?'),
                    ],
                    [
                        'type' => 'text',
                        'name' => 'label',
                        'label' => Craft::t('formie', 'Option Label'),
                        'required' => true,
                    ],
                    [
                        'type' => 'value',
                        'name' => 'value',
                        'label' => Craft::t('formie', 'Value'),
                        'source' => 'label',
                    ],
                    [
                        'type' => $this->multi ? 'checkbox' : 'radio',
                        'name' => 'default',
                        'label' => Craft::t('formie', 'Default'),
                        'allowUnselect' => !$this->multi,
                    ],
                ],
            ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::prePopulate(),
            $this->defineAutocompleteSettingSchema(),
            SchemaHelper::includeInEmailFieldSummariesField(),
            SchemaHelper::emailFieldSummaryValue([
                'options' => [
                    ['label' => Craft::t('formie', 'Label'), 'value' => 'label'],
                    ['label' => Craft::t('formie', 'Value'), 'value' => 'value'],
                ],
            ]),
        ];
    }

    public function defineFormBuilderValidationSchema(): array
    {
        return [
            SchemaHelper::requiredField(),
            SchemaHelper::requiredValidationMessage(),
            SchemaHelper::limitOptionsField(['if' => 'multiple']),
            SchemaHelper::optionsLimitMinField(),
            SchemaHelper::minOptionsValidationMessage(),
            SchemaHelper::optionsLimitMaxField(),
            SchemaHelper::maxOptionsValidationMessage(),
        ];
    }

    public function defineFormBuilderAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
            SchemaHelper::errorMessagePosition($this),
        ];
    }

    public function defineFormBuilderAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
            SchemaHelper::inputAttributesField(),
        ];
    }

    public function defineFormBuilderConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }
    

    // Protected Methods
    // =========================================================================

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;
        $errors = $context->errors;

        if ($key === 'fieldInput') {
            $optionValue = $this->getFieldInputOptionValue($context->toArray());

            return SlotTag::make('select')
                ->core(array_merge([
                    'id' => $this->getHtmlId($form, $optionValue),
                    'name' => $this->getHtmlName(($this->multi || $this->hasMultiNamespace ? '[]' : null)),
                    'multiple' => $this->multi ? true : null,
                    'required' => $this->required ? true : null,
                    'data-formie-select' => true,
                    'data-formie-dropdown-input' => true,
                    'data-formie-input-id' => $this->getHtmlDataId($form, $optionValue),
                    'data-formie-input-type' => 'select',
                    'data-formie-input-error-state' => $errors ? true : false,
                ], ValidationMessagesHelper::requiredClientAttributes($this)))
                ->theme([
                    'class' => [
                        'formie-select',
                        'formie-dropdown-input',
                        $errors ? 'formie-input-error' : false,
                    ],
                ])
                ->instanceAttributes($this->getInputAttributes());
        }

        return parent::defineFieldSlotTag($key, $context);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules = array_merge($rules, $this->defineAutocompleteRules());
        $rules[] = [['min', 'max'], 'number'];
        $rules[] = [['max'], 'compare', 'compareAttribute' => 'min', 'operator' => '>='];

        return $rules;
    }

    protected function defineClientInput(): array
    {
        return array_merge(parent::defineClientInput(), [
            'min' => $this->min,
            'max' => $this->max,
            'autocomplete' => $this->getAutocompleteCoreAttribute(),
        ]);
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/dropdown/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'options' => $this->translatedOptions(),
        ]);
    }

    protected function defineValueForEmailPreview(FakerFactory $faker): mixed
    {
        $values = $faker->randomElement($this->options)['value'] ?? '';

        if ($this->multi) {
            $values = [$values];
        }

        return $values;
    }

    protected function optionsSettingLabel(): string
    {
        return Craft::t('app', 'Dropdown Options');
    }

    protected function definePrimaryOptionVariableSourceTypes(): array
    {
        return $this->multi
            ? [Variables::TYPE_TEXT, Variables::TYPE_CALCULATIONS, Variables::TYPE_BOOLEAN]
            : [Variables::TYPE_TEXT];
    }
}
