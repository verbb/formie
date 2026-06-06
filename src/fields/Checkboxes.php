<?php
namespace verbb\formie\fields;

use verbb\formie\base\FieldInterface;
use verbb\formie\base\OptionsField;
use verbb\formie\fields\values\MultiOptionFieldValue;
use verbb\formie\fields\definitions\FieldClientModules;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\ValidationMessagesHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\ClientModule;
use verbb\formie\models\SlotTag;
use verbb\formie\positions\Hidden as HiddenPosition;

use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Localization;
use craft\i18n\Locale;
use craft\validators\ArrayValidator;

use Faker\Generator as FakerFactory;

use GraphQL\Type\Definition\Type;

use yii\db\Schema;

class Checkboxes extends OptionsField
{
    // Static Methods
    // =========================================================================

    public static function phpType(): string
    {
        return sprintf('\\%s', MultiOptionFieldValue::class);
    }

    public static function dbType(): string
    {
        return Schema::TYPE_JSON;
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'Checkboxes');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/checkboxes/icon.svg';
    }


    // Properties
    // =========================================================================

    public bool $multi = true;
    public ?string $layout = 'vertical';
    public ?string $toggleCheckbox = null;
    public ?string $toggleCheckboxLabel = null;
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

    public function getFieldOptions(): array
    {
        $options = [];

        foreach ($this->options() as $option) {
            $disabled = $option['disabled'] ?? false;

            if (!$disabled) {
                $options[] = $option;
            }
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
            SchemaHelper::previewChoiceList('checkbox'),
        ];
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'limitOptions' => [
                'name' => 'limitOptions',
                'type' => Type::boolean(),
            ],
            'min' => [
                'name' => 'min',
                'type' => Type::int(),
            ],
            'max' => [
                'name' => 'max',
                'type' => Type::int(),
            ],
            'toggleCheckbox' => [
                'name' => 'toggleCheckbox',
                'type' => Type::string(),
            ],
            'toggleCheckboxLabel' => [
                'name' => 'toggleCheckboxLabel',
                'type' => Type::string(),
            ],
        ]);
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Options'),
                'instructions' => Craft::t('formie', 'Define the available options for users to select from.'),
                'name' => 'options',
                'enableBulkOptions' => true,
                'predefinedOptions' => $this->getPredefinedOptions(),
                'newRowDefaults' => [
                    'default' => false,
                ],
                'columns' => [
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
                        'type' => 'checkbox',
                        'name' => 'default',
                        'label' => Craft::t('formie', 'Default'),
                    ],
                    [
                        'type' => 'checkbox',
                        'name' => 'disabled',
                        'label' => Craft::t('formie', 'Disabled'),
                    ],
                ],
            ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::prePopulate(),
            SchemaHelper::includeInEmailFieldSummariesField(),
            SchemaHelper::emailFieldSummaryValue([
                'options' => [
                    ['label' => Craft::t('formie', 'Label'), 'value' => 'label'],
                    ['label' => Craft::t('formie', 'Value'), 'value' => 'value'],
                ],
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Add Toggle Checkbox'),
                'instructions' => Craft::t('formie', 'Whether to add an additional checkbox to toggle all checkboxes in this field by.'),
                'name' => 'toggleCheckbox',
                'options' => [
                    ['label' => Craft::t('formie', 'None'), 'value' => ''],
                    ['label' => Craft::t('formie', 'Top of List'), 'value' => 'top'],
                    ['label' => Craft::t('formie', 'Bottom of List'), 'value' => 'bottom'],
                ],
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Toggle Checkbox Label'),
                'instructions' => Craft::t('formie', 'Enter the label for the toggle checkbox field.'),
                'name' => 'toggleCheckboxLabel',
                'if' => 'toggleCheckbox',
            ]),
        ];
    }

    public function defineFormBuilderValidationSchema(): array
    {
        return [
            SchemaHelper::requiredField(),
            SchemaHelper::requiredValidationMessage(),
            SchemaHelper::limitOptionsField(),
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
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Layout'),
                'instructions' => Craft::t('formie', 'Select which layout to use for these fields.'),
                'name' => 'layout',
                'options' => [
                    ['label' => Craft::t('formie', 'Vertical'), 'value' => 'vertical'],
                    ['label' => Craft::t('formie', 'Horizontal'), 'value' => 'horizontal'],
                ],
            ]),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
        ];
    }

    public function defineFormBuilderAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
            SchemaHelper::inputAttributesField([
                'instructions' => Craft::t('formie', 'Add attributes to be outputted on this field’s input. Note that these attributes will be added to every checkbox option.'),
            ]),
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

    protected function supportedDefaults(): array
    {
        return ['layout'];
    }

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;
        $id = $this->getHtmlId($form);
        $labelPosition = is_object($this->labelPosition) ? get_class($this->labelPosition) : (string)$this->labelPosition;
        $labelPosition = strtolower($labelPosition);
        $resolvedLabelPosition = str_contains($labelPosition, 'left') ? 'left' : (str_contains($labelPosition, 'right') ? 'right' : (str_contains($labelPosition, 'hidden') ? 'hidden' : 'above'));
        $isHiddenLabel = $context->get('labelPosition') instanceof HiddenPosition || $resolvedLabelPosition === 'hidden';

        if ($key === 'field') {
            $tag = parent::defineFieldSlotTag($key, $context);

            if ($tag && $this->limitOptions) {
                $tag->attributes['data-formie-min-options'] = $this->min ?: null;
                $tag->attributes['data-formie-max-options'] = $this->max ?: null;
                $tag->attributes = array_merge(
                    $tag->attributes,
                    ValidationMessagesHelper::optionsLimitClientAttributes($this, true, $this->min, $this->max),
                );
            }

            return $tag;
        }

        if ($key === 'fieldLayout') {
            return SlotTag::make('fieldset')
                ->core([
                    'data-formie-field-layout' => true,
                    'data-formie-checkboxes-field-layout' => true,
                    'data-formie-layout' => $this->layout ?? 'vertical',
                    'data-formie-label-position' => $resolvedLabelPosition,
                    'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
                ])
                ->theme([
                    'class' => [
                        'formie-field-layout',
                        'formie-checkboxes-field-layout',
                        'formie-layout-' . ($this->layout ?? 'vertical'),
                        "formie-field-layout-label-{$resolvedLabelPosition}",
                    ],
                ]);
        }

        if ($key === 'fieldLabel') {
            return SlotTag::make('legend')
                ->core([
                    'data-formie-label' => true,
                    'data-formie-field-label' => true,
                    'data-formie-checkboxes-field-label' => true,
                    'data-formie-label-position' => $resolvedLabelPosition,
                    'data-formie-sr-only' => $isHiddenLabel ? true : false,
                ])
                ->theme([
                    'class' => [
                        'formie-label',
                        'formie-field-label',
                        'formie-checkboxes-field-label',
                        $isHiddenLabel ? 'formie-sr-only' : false,
                    ],
                ]);
        }

        if ($key === 'fieldOptions') {
            return SlotTag::make('div')
                ->core([
                    'data-formie-field-options' => true,
                    'data-formie-checkboxes-options' => true,
                    'data-formie-layout' => $this->layout ?? 'vertical',
                ])
                ->theme([
                    'class' => [
                        'formie-field-options',
                        'formie-checkboxes-options',
                        'formie-layout-' . ($this->layout ?? 'vertical'),
                    ],
                ]);
        }

        if ($key === 'fieldOption') {
            return SlotTag::make('div')
                ->core([
                    'data-formie-field-option' => true,
                    'data-formie-checkbox-option' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-field-option',
                        'formie-checkbox-option',
                    ],
                ]);
        }

        if ($key === 'fieldInput') {
            $optionValue = $this->getFieldInputOptionValue($context->toArray());

            return SlotTag::make('input')
                ->core(array_merge([
                    'type' => 'checkbox',
                    'id' => $this->getHtmlId($form, $optionValue),
                    'name' => $this->getHtmlName('[]'),
                    'required' => $this->required ? true : null,
                    'data-formie-input' => true,
                    'data-formie-checkbox-input' => true,
                    'data-formie-input-id' => $this->getHtmlDataId($form, $optionValue),
                    'data-formie-input-type' => 'checkbox',
                ], ValidationMessagesHelper::requiredClientAttributes($this)))
                ->theme([
                    'class' => [
                        'formie-input',
                        'formie-checkbox-input',
                    ],
                ])
                ->instanceAttributes($this->getInputAttributes());
        }

        if ($key === 'fieldOptionLabel') {
            $optionValue = $this->getFieldInputOptionValue($context->toArray());

            return SlotTag::make('label')
                ->core([
                    'data-formie-field-option-label' => true,
                    'data-formie-checkbox-option-label' => true,
                    'for' => $this->getHtmlId($form, $optionValue),
                ])
                ->theme([
                    'class' => [
                        'formie-field-option-label',
                        'formie-checkbox-option-label',
                    ],
                ]);
        }

        return parent::defineFieldSlotTag($key, $context);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['min', 'max'], 'number'];
        $rules[] = [['max'], 'compare', 'compareAttribute' => 'min', 'operator' => '>='];

        return $rules;
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/checkboxes/input', [
            'name' => $this->handle,
            'values' => $value,
            'options' => $this->translatedOptions(),
        ]);
    }

    protected function defineValueForEmailPreview(FakerFactory $faker): mixed
    {
        $values = $faker->randomElement($this->options)['value'] ?? '';

        return [$values];
    }

    protected function optionsSettingLabel(): string
    {
        return Craft::t('app', 'Checkbox Options');
    }

    protected function definePrimaryOptionVariableSourceTypes(): array
    {
        return [
            Variables::TYPE_TEXT,
            Variables::TYPE_CALCULATIONS,
            Variables::TYPE_BOOLEAN,
        ];
    }

    protected function defineValidationRules(): array
    {
        $validators = parent::defineValidationRules();

        if ($this->limitOptions) {
            $validators[] = [
                'type' => 'minmaxOptions',
                'min' => $this->min ?: null,
                'max' => $this->max ?: null,
            ];
        }

        return $validators;
    }

    protected function defineClientInput(): array
    {
        return array_merge(parent::defineClientInput(), [
            'min' => $this->min,
            'max' => $this->max,
        ]);
    }

    protected function defineClientModules(): array
    {
        $modules = parent::defineClientModules();
        $modules[] = new ClientModule([
            'id' => 'checkbox-radio',
            'renderTargets' => [ClientModule::RENDER_TARGET_FRONTEND],
        ]);

        return $modules;
    }
}
