<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormFieldInterface;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Localization;
use craft\helpers\StringHelper;
use craft\i18n\Locale;
use craft\validators\ArrayValidator;

class Dropdown extends BaseOptionsField implements FormFieldInterface
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Dropdown');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/dropdown/icon.svg';
    }


    // Properties
    // =========================================================================

    public bool $multiple = false;
    public bool $multi = false;
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

        // Config normalization
        self::normalizeConfig($config);

        parent::__construct($config);
    }

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        // Mirror to native `multi` attribute
        $this->setMultiple($this->multiple);

        parent::init();
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        return [
            'options' => [
                [
                    'label' => Craft::t('formie', 'Select an option'),
                    'value' => '',
                    'isOptgroup' => false,
                    'isDefault' => true,
                ],
            ],
        ];
    }

    public function getFieldOptions(): array
    {
        $options = [];

        // Add a placeholder first, if it exists
        if ($this->placeholder) {
            $disabled = $option['disabled'] ?? false;

            if (!$disabled) {
                $options[] = ['label' => $this->placeholder, 'value' => ''];
            }
        }

        return array_merge($options, $this->options);
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
        return Craft::$app->getView()->renderTemplate('formie/_formfields/dropdown/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'options' => $this->translatedOptions(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/dropdown/preview', [
            'field' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getSavedSettings(): array
    {
        $settings = parent::getSavedSettings();

        foreach ($settings['options'] as &$option) {
            if (isset($option['optgroup']) && $option['optgroup']) {
                $option['isOptgroup'] = true;
                $option['label'] = ArrayHelper::remove($option, 'optgroup');
            } else {
                $option['isOptgroup'] = false;
            }
        }

        return $settings;
    }

    /**
     * Returns the multi property.
     *
     * @return bool
     */
    public function getMultiple(): bool
    {
        return $this->multi;
    }

    /**
     * Sets the multi property.
     *
     * @param $value
     */
    public function setMultiple($value): void
    {
        $this->multi = $value;
    }

    /**
     * @inheritDoc
     */
    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Allow Multiple'),
                'help' => Craft::t('formie', 'Whether this field should allow multiple options to be selected.'),
                'name' => 'multiple',
            ]),
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Options'),
                'help' => Craft::t('formie', 'Define the available options for users to select from.'),
                'name' => 'options',
                'allowMultipleDefault' => 'settings.multiple',
                'enableBulkOptions' => true,
                'predefinedOptions' => $this->getPredefinedOptions(),
                'newRowDefaults' => [
                    'label' => '',
                    'value' => '',
                    'isOptgroup' => false,
                    'isDefault' => false,
                ],
                'columns' => [
                    [
                        'type' => 'optgroup',
                        'label' => Craft::t('formie', 'Optgroup?'),
                        'class' => 'thin checkbox-cell',
                    ],
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
                'if' => '$get(multiple).value',
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
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
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
            SchemaHelper::inputAttributesField(),
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
        $errors = $context['errors'] ?? null;

        if ($key === 'fieldInput') {
            $optionValue = $context['option']['value'] ?? '';
            $id = $this->getHtmlId($form, StringHelper::toKebabCase($optionValue));
            $dataId = $this->getHtmlDataId($form, StringHelper::toKebabCase($optionValue));

            return new HtmlTag('select', [
                'id' => $id,
                'class' => [
                    'fui-select',
                    $errors ? 'fui-error' : false,
                ],
                'name' => $this->getHtmlName(($this->multi || $this->hasMultiNamespace ? '[]' : null)),
                'multiple' => $this->multiple ? true : null,
                'required' => $this->required ? true : null,
                'data' => [
                    'fui-id' => $dataId,
                    'fui-message' => Craft::t('formie', $this->errorMessage) ?: null,
                ],
            ], $this->getInputAttributes());
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
        return Craft::t('app', 'Dropdown Options');
    }
}
