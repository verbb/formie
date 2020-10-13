<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\helpers\SchemaHelper;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\i18n\Locale;
use craft\helpers\Db;
use craft\helpers\Localization;
use craft\helpers\Template;

use LitEmoji\LitEmoji;
use Twig\Markup;

class Number extends FormField implements PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Number');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/number/icon.svg';
    }


    // Properties
    // =========================================================================

    public $min;
    public $max;
    public $decimals;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function __construct($config = [])
    {
        // Normalize number settings
        foreach (['defaultValue', 'min', 'max'] as $name) {
            if (isset($config[$name]) && is_array($config[$name])) {
                $config[$name] = Localization::normalizeNumber($config[$name]['value'], $config[$name]['locale']);
            }
        }

        parent::__construct($config);
    }

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        // Normalize $defaultValue
        if ($this->defaultValue === '') {
            $this->defaultValue = null;
        }

        // Normalize $max
        if ($this->max === '') {
            $this->max = null;
        }

        // Normalize $min
        if ($this->min === '') {
            $this->min = null;
        }

        // Normalize $decimals
        if (!$this->decimals) {
            $this->decimals = 0;
        }
    }

    /**
     * @inheritDoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['defaultValue', 'min', 'max'], 'number'];
        $rules[] = [['decimals'], 'integer'];
        $rules[] = [
            ['max'],
            'compare',
            'compareAttribute' => 'min',
            'operator' => '>='
        ];

        if (!$this->decimals) {
            $rules[] = [['defaultValue', 'min', 'max'], 'integer'];
        }

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function getContentColumnType(): string
    {
        return Db::getNumericalColumnType($this->min, $this->max, $this->decimals);
    }

    /**
     * @inheritDoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        if ($value === null) {
            if ($this->defaultValue !== null && $this->isFresh($element)) {
                return $this->defaultValue;
            }
            return null;
        }

        // Was this submitted with a locale ID?
        if (isset($value['locale'], $value['value'])) {
            $value = Localization::normalizeNumber($value['value'], $value['locale']);
        }

        if ($value === '') {
            return null;
        }

        if (is_string($value) && is_numeric($value)) {
            if ((int)$value == $value) {
                return (int)$value;
            }
            if ((float)$value == $value) {
                return (float)$value;
            }
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function getIsTextInput(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        // If decimals is 0 (or null, empty for whatever reason), don't run this
        if ($value !== null && $this->decimals) {
            $decimalSeparator = Craft::$app->getLocale()->getNumberSymbol(Locale::SYMBOL_DECIMAL_SEPARATOR);
            try {
                $value = number_format($value, $this->decimals, $decimalSeparator, '');
            } catch (\Throwable $e) {
                // NaN
            }
        }

        return Craft::$app->getView()->renderTemplate('formie/_formfields/number/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/number/preview', [
            'field' => $this
        ]);
    }

    /**
     * @inheritDoc
     */
    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder'),
                'help' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                'name' => 'placeholder',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Default Value'),
                'help' => Craft::t('formie', 'Entering a default value will place the value in the field when it loads.'),
                'name' => 'defaultValue',
                'validation' => 'optional|number',
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
            SchemaHelper::toggleContainer('settings.required', [
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Error Message'),
                    'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                    'name' => 'errorMessage',
                ]),
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Limit Numbers'),
                'help' => Craft::t('formie', 'Whether to limit the numbers for this field.'),
                'name' => 'limit',
            ]),
            SchemaHelper::toggleContainer('settings.limit', [
                [
                    'component' => 'div',
                    'class' => 'fui-row',
                    'children' => [
                        [
                            'component' => 'div',
                            'class' => 'fui-col-6',
                            'children' => [
                                SchemaHelper::textField([
                                    'label' => Craft::t('formie', 'Min Value'),
                                    'help' => Craft::t('formie', 'Set a minimum value that users must enter.'),
                                    'name' => 'min',
                                    'validation' => 'optional|number',
                                ]),
                            ],
                        ],
                        [
                            'component' => 'div',
                            'class' => 'fui-col-6',
                            'children' => [
                                SchemaHelper::textField([
                                    'label' => Craft::t('formie', 'Max Value'),
                                    'help' => Craft::t('formie', 'Set a maximum value that users must enter.'),
                                    'name' => 'max',
                                    'validation' => 'optional|number',
                                ]),
                            ],
                        ],
                    ],
                ],
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Decimal Points'),
                'help' => Craft::t('formie', 'Set the number of decimal points to format the field value.'),
                'name' => 'decimals',
                'validation' => 'optional|number|min:0',
            ]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineAppearanceSchema(): array
    {
        return [
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
}
