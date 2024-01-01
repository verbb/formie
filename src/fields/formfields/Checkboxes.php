<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\OptionsField;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\HtmlTag;

use Craft;
use craft\base\ElementInterface;
use craft\fields\data\MultiOptionsFieldData;
use craft\helpers\Localization;
use craft\helpers\StringHelper;
use craft\i18n\Locale;
use craft\validators\ArrayValidator;

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

        foreach ($this->options as $option) {
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
                    'attribute' => Craft::t('formie', $this->name),
                    'min' => $this->min,
                ]) : null,
                'tooMany' => $this->max ? Craft::t('app', '{attribute} should contain at most {max, number} {max, plural, one{option} other{options}}.', [
                    'attribute' => Craft::t('formie', $this->name),
                    'max' => $this->max,
                ]) : null,
                'skipOnEmpty' => false,
            ]);

            $value = $element->getFieldValue($this->handle);

            if (!$arrayValidator->validate($value, $error)) {
                $element->addError($this->handle, $error);
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
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/fields/checkbox-radio.js', true),
            'module' => 'FormieCheckboxRadio',
        ];
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
            return new HtmlTag('legend', [
                'class' => 'fui-legend',
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
            $optionValue = $context['option']['value'] ?? '';
            $id = $this->getHtmlId($form, StringHelper::toKebabCase($optionValue));
            $dataId = $this->getHtmlDataId($form, StringHelper::toKebabCase($optionValue));

            return new HtmlTag('input', [
                'type' => 'checkbox',
                'id' => $id,
                'class' => 'fui-input fui-checkbox-input',
                'name' => $this->getHtmlName('[]'),
                'required' => $this->required ? true : null,
                'data' => [
                    'fui-id' => $dataId,
                    'fui-message' => Craft::t('formie', $this->errorMessage) ?: null,
                ],
            ], $this->getInputAttributes());
        }

        if ($key === 'fieldOptionLabel') {
            $optionValue = $context['option']['value'] ?? '';
            $id = $this->getHtmlId($form, StringHelper::toKebabCase($optionValue));

            return new HtmlTag('label', [
                'class' => 'fui-checkbox-label',
                'for' => $id,
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

    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/checkboxes/input', [
            'name' => $this->handle,
            'values' => $value,
            'options' => $this->translatedOptions(),
        ]);
    }

    protected function optionsSettingLabel(): string
    {
        return Craft::t('app', 'Checkbox Options');
    }
}
