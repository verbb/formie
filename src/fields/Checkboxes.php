<?php
namespace verbb\formie\fields;

use verbb\formie\base\FieldInterface;
use verbb\formie\base\OptionsField;
use verbb\formie\fields\data\MultiOptionsFieldData;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\HtmlTag;
use verbb\formie\positions;

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
        return sprintf('\\%s', MultiOptionsFieldData::class);
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
                'tooFew' => $this->min ? Craft::t('app', '{attribute} should contain at least {min, number} {min, plural, one{option} other{options}}.', [
                    'attribute' => Craft::t('formie', $this->label),
                    'min' => $this->min,
                ]) : null,
                'tooMany' => $this->max ? Craft::t('app', '{attribute} should contain at most {max, number} {max, plural, one{option} other{options}}.', [
                    'attribute' => Craft::t('formie', $this->label),
                    'max' => $this->max,
                ]) : null,
                'skipOnEmpty' => false,
            ]);

            $value = $element->getFieldValue($this->fieldKey);

            if (!$arrayValidator->validate($value, $error)) {
                $element->addError($this->fieldKey, $error);
            }
        }
    }

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/checkboxes/preview', [
            'field' => $this,
        ]);
    }

    public function getFrontEndJsModules(): ?array
    {
        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/fields/checkbox-radio.js'),
            'module' => 'FormieCheckboxRadio',
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

    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Options'),
                'help' => Craft::t('formie', 'Define the available options for users to select from.'),
                'name' => 'options',
                'allowMultipleDefault' => true,
                'enableBulkOptions' => true,
                'predefinedOptions' => $this->getPredefinedOptions(),
                'newRowDefaults' => [
                    'label' => '',
                    'value' => '',
                    'isDefault' => false,
                ],
                'columns' => [
                    [
                        'type' => 'label',
                        'label' => Craft::t('formie', 'Option Label'),
                        'class' => 'singleline-cell textual',
                    ],
                    [
                        'type' => 'value',
                        'label' => Craft::t('formie', 'Value'),
                        'class' => 'code singleline-cell textual',
                    ],
                    [
                        'type' => 'default',
                        'label' => Craft::t('formie', 'Default'),
                        'class' => 'thin checkbox-cell',
                    ],
                    [
                        'type' => 'disabled',
                        'label' => Craft::t('formie', 'Disabled'),
                        'class' => 'thin checkbox-cell',
                    ],
                ],
            ]),
        ];
    }

    public function defineSettingsSchema(): array
    {
        return [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Required Field'),
                'help' => Craft::t('formie', 'Whether this field should be required when filling out the form.'),
                'name' => 'required',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Error Message'),
                'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                'name' => 'errorMessage',
                'if' => '$get(required).value',
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Limit Options'),
                'help' => Craft::t('formie', 'Whether to limit the options users can choose for this field.'),
                'name' => 'limitOptions',
            ]),
            [
                '$el' => 'div',
                'attrs' => [
                    'class' => 'fui-row',
                ],
                'if' => '$get(limitOptions).value',
                'children' => [
                    [
                        '$el' => 'div',
                        'attrs' => [
                            'class' => 'fui-col-6',
                        ],
                        'children' => [
                            SchemaHelper::numberField([
                                'label' => Craft::t('formie', 'Min Value'),
                                'help' => Craft::t('formie', 'Set the minimum options that users must select.'),
                                'name' => 'min',
                            ]),
                        ],
                    ],
                    [
                        '$el' => 'div',
                        'attrs' => [
                            'class' => 'fui-col-6',
                        ],
                        'children' => [
                            SchemaHelper::numberField([
                                'label' => Craft::t('formie', 'Max Value'),
                                'help' => Craft::t('formie', 'Set the maximum options that users must select.'),
                                'name' => 'max',
                            ]),
                        ],
                    ],
                ],
            ],
            SchemaHelper::prePopulate(),
            SchemaHelper::includeInEmailField(),
            SchemaHelper::emailNotificationValue([
                'options' => [
                    ['label' => Craft::t('formie', 'Label'), 'value' => 'label'],
                    ['label' => Craft::t('formie', 'Value'), 'value' => 'value'],
                ],
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Add Toggle Checkbox'),
                'help' => Craft::t('formie', 'Whether to add an additional checkbox to toggle all checkboxes in this field by.'),
                'name' => 'toggleCheckbox',
                'options' => [
                    ['label' => Craft::t('formie', 'None'), 'value' => ''],
                    ['label' => Craft::t('formie', 'Top of List'), 'value' => 'top'],
                    ['label' => Craft::t('formie', 'Bottom of List'), 'value' => 'bottom'],
                ],
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Toggle Checkbox Label'),
                'help' => Craft::t('formie', 'Enter the label for the toggle checkbox field.'),
                'name' => 'toggleCheckboxLabel',
                'if' => '$get(toggleCheckbox).value',
            ]),
        ];
    }

    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Layout'),
                'help' => Craft::t('formie', 'Select which layout to use for these fields.'),
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

    public function defineAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
            SchemaHelper::inputAttributesField([
                'help' => Craft::t('formie', 'Add attributes to be outputted on this field’s input. Note that these attributes will be added to every checkbox option.'),
            ]),
        ];
    }

    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        $form = $context['form'] ?? null;

        $id = $this->getHtmlId($form);

        if ($key === 'field') {
            $tag = parent::defineHtmlTag($key, $context);
            $tag->attributes['data']['min-options'] = ($this->limitOptions && $this->min) ? $this->min : null;
            $tag->attributes['data']['max-options'] = ($this->limitOptions && $this->max) ? $this->max : null;

            return $tag;
        }

        if ($key === 'fieldContainer') {
            return new HtmlTag('fieldset', [
                'class' => [
                    'fui-fieldset',
                    'fui-layout-' . $this->layout ?? 'vertical',
                ],
                'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
            ]);
        }

        if ($key === 'fieldLabel') {
            $labelPosition = $context['labelPosition'] ?? null;

            return new HtmlTag('legend', [
                'class' => [
                    'fui-legend',
                ],
                'data' => [
                    'field-label' => true,
                    'fui-sr-only' => $labelPosition instanceof positions\Hidden ? true : false,
                ],
            ]);
        }

        if ($key === 'fieldOptions') {
            return new HtmlTag('div', [
                'class' => 'fui-layout-wrap',
            ]);
        }

        if ($key === 'fieldOption') {
            return new HtmlTag('div', [
                'class' => 'fui-checkbox',
            ]);
        }

        if ($key === 'fieldInput') {
            $optionValue = $this->getFieldInputOptionValue($context);

            return new HtmlTag('input', [
                'type' => 'checkbox',
                'id' => $this->getHtmlId($form, $optionValue),
                'class' => 'fui-input fui-checkbox-input',
                'name' => $this->getHtmlName('[]'),
                'required' => $this->required ? true : null,
                'data' => [
                    'fui-id' => $this->getHtmlDataId($form, $optionValue),
                    'required-message' => Craft::t('formie', $this->errorMessage) ?: null,
                ],
            ], $this->getInputAttributes());
        }

        if ($key === 'fieldOptionLabel') {
            $optionValue = $this->getFieldInputOptionValue($context);

            return new HtmlTag('label', [
                'class' => 'fui-checkbox-label',
                'for' => $this->getHtmlId($form, $optionValue),
            ]);
        }

        return parent::defineHtmlTag($key, $context);
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['min', 'max'], 'number'];
        $rules[] = [['max'], 'compare', 'compareAttribute' => 'min', 'operator' => '>='];

        return $rules;
    }

    protected function cpInputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
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
}
