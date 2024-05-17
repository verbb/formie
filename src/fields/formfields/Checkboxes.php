<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormFieldInterface;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\base\ElementInterface;
use craft\fields\data\MultiOptionsFieldData;
use craft\helpers\Localization;
use craft\helpers\StringHelper;
use craft\i18n\Locale;
use craft\validators\ArrayValidator;

class Checkboxes extends BaseOptionsField implements FormFieldInterface
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function valueType(): string
    {
        return MultiOptionsFieldData::class;
    }

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Checkboxes');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/checkboxes/icon.svg';
    }


    // Properties
    // =========================================================================

    public bool $multi = true;
    public ?string $layout = null;
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

        // Config normalization
        self::normalizeConfig($config);

        parent::__construct($config);
    }

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        parent::init();

        $this->multi = true;
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        return [
            'options' => [],
            'layout' => 'vertical',
        ];
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

    /**
     * @inheritDoc
     */
    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/checkboxes/input', [
            'name' => $this->handle,
            'values' => $value,
            'options' => $this->translatedOptions(),
        ]);
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
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
            $labelPosition = $context['labelPosition'] ?? null;

            return new HtmlTag('legend', [
                'class' => [
                    'fui-legend',
                ],
                'data' => [
                    'fui-sr-only' => $labelPosition instanceof HiddenPosition ? true : false,
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
                    'fui-message' => Craft::t('formie', $this->errorMessage) ?: null,
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

    protected function optionsSettingLabel(): string
    {
        return Craft::t('app', 'Checkbox Options');
    }
}
