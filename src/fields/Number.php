<?php
namespace verbb\formie\fields;

use verbb\formie\base\Field;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;

use Craft;
use craft\base\ElementInterface;
use craft\base\InlineEditableFieldInterface;
use craft\base\PreviewableFieldInterface;
use craft\base\SortableFieldInterface;
use craft\gql\types\Number as NumberType;
use craft\helpers\Db;
use craft\helpers\Localization;
use craft\i18n\Locale;

use Faker\Generator as FakerFactory;

use GraphQL\Type\Definition\Type;

use yii\db\Schema;

use Throwable;

class Number extends Field implements InlineEditableFieldInterface, PreviewableFieldInterface, SortableFieldInterface
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_UNIQUE_QUERY = 'modifyUniqueQuery';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Number');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/number/icon.svg';
    }

    public static function dbType(): string
    {
        // Don't use integer columns, so we can handle large numbers as strings
        return Schema::TYPE_JSON;
    }


    // Properties
    // =========================================================================

    public bool $limit = false;
    public int|float|null $min = null;
    public int|float|null $max = null;
    public ?int $decimals = null;
    public bool $uniqueValue = false;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Normalize number settings
        foreach (['defaultValue', 'min', 'max'] as $name) {
            if (isset($config[$name]) && is_array($config[$name])) {
                $config[$name] = Localization::normalizeNumber($config[$name]['value'], $config[$name]['locale']);
            }
        }

        parent::__construct($config);
    }

    public function init(): void
    {
        parent::init();

        // Normalize $defaultValue
        if ($this->defaultValue === '') {
            $this->defaultValue = null;
        }

        // Normalize $decimals
        if (!$this->decimals) {
            $this->decimals = 0;
        }
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value === null) {
            if ($this->defaultValue !== null) {
                return (string)$this->defaultValue;
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

        return (string)$value;
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();
        $rules[] = ['number', 'min' => $this->min, 'max' => $this->max];

        if ($this->uniqueValue) {
            $rules[] = 'validateUniqueValue';
        }

        return $rules;
    }

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/number/preview', [
            'field' => $this,
        ]);
    }

    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder'),
                'help' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                'name' => 'placeholder',
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Default Value'),
                'help' => Craft::t('formie', 'Set a default value for the field when it doesn’t have a value.'),
                'name' => 'defaultValue',
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
                'label' => Craft::t('formie', 'Limit Numbers'),
                'help' => Craft::t('formie', 'Whether to limit the numbers for this field.'),
                'name' => 'limit',
            ]),
            [
                '$el' => 'div',
                'attrs' => [
                    'class' => 'fui-row',
                ],
                'if' => '$get(limit).value',
                'children' => [
                    [
                        '$el' => 'div',
                        'attrs' => [
                            'class' => 'fui-col-6',
                        ],
                        'children' => [
                            SchemaHelper::numberField([
                                'label' => Craft::t('formie', 'Min Value'),
                                'help' => Craft::t('formie', 'Set a minimum value that users must enter.'),
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
                                'help' => Craft::t('formie', 'Set a maximum value that users must enter.'),
                                'name' => 'max',
                            ]),
                        ],
                    ],
                ],
            ],
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Decimal Points'),
                'help' => Craft::t('formie', 'Set the number of decimal points to format the field value.'),
                'name' => 'decimals',
            ]),
            SchemaHelper::matchField([
                'fieldTypes' => [self::class],
            ]),
            SchemaHelper::prePopulate(),
            SchemaHelper::includeInEmailField(),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Unique Value'),
                'help' => Craft::t('formie', 'Whether to limit user input to unique values only. This will require that a value entered in this field does not already exist in a submission for this field and form.'),
                'name' => 'uniqueValue',
            ]),
        ];
    }

    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
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

    public function getContentGqlType(): array|Type
    {
        return NumberType::getType();
    }

    public function getContentGqlMutationArgumentType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => NumberType::getType(),
            'description' => $this->instructions,
        ];
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'limit' => [
                'name' => 'limit',
                'type' => Type::boolean(),
            ],
            // We're forced to use a int-representation of the min/max values, due to the parent `min/max` definition
            // So cast it properly here as an int, but also provide `minValue/maxValue` as the proper type.
            'min' => [
                'name' => 'min',
                'type' => Type::int(),
                'resolve' => function($field) {
                    return (int)$field->min;
                },
            ],
            'max' => [
                'name' => 'max',
                'type' => Type::int(),
                'resolve' => function($field) {
                    return (int)$field->max;
                },
            ],
            'minValue' => [
                'name' => 'minValue',
                'type' => Type::float(),
                'resolve' => function($field) {
                    return $field->min;
                },
            ],
            'maxValue' => [
                'name' => 'maxValue',
                'type' => Type::float(),
                'resolve' => function($field) {
                    return $field->max;
                },
            ],
            'decimals' => [
                'name' => 'decimals',
                'type' => Type::int(),
            ],
        ]);
    }

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        $form = $context['form'] ?? null;
        $errors = $context['errors'] ?? null;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        if ($key === 'fieldInput') {
            return new HtmlTag('input', [
                'type' => 'number',
                'id' => $id,
                'class' => [
                    'fui-input',
                    $errors ? 'fui-error' : false,
                ],
                'name' => $this->getHtmlName(),
                'placeholder' => Craft::t('formie', $this->placeholder) ?: null,
                'required' => $this->required ? true : null,
                'min' => $this->limit ? $this->min : false,
                'max' => $this->limit ? $this->max : false,
                'data' => [
                    'fui-id' => $dataId,
                    'required-message' => Craft::t('formie', $this->errorMessage) ?: null,
                ],
                'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
            ], $this->getInputAttributes());
        }

        return parent::defineHtmlTag($key, $context);
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['defaultValue', 'min', 'max'], 'number'];
        $rules[] = [['decimals'], 'integer'];
        $rules[] = [['max'], 'compare', 'compareAttribute' => 'min', 'operator' => '>='];

        if (!$this->decimals) {
            $rules[] = [['defaultValue', 'min', 'max'], 'integer'];
        }

        return $rules;
    }

    protected function cpInputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        // If decimals is 0 (or null, empty for whatever reason), don't run this
        if ($value !== null && $this->decimals) {
            $decimalSeparator = Craft::$app->getLocale()->getNumberSymbol(Locale::SYMBOL_DECIMAL_SEPARATOR);
            
            try {
                $value = number_format($value, $this->decimals, $decimalSeparator, '');
            } catch (Throwable $e) {
                // NaN
            }
        }

        return Craft::$app->getView()->renderTemplate('formie/_formfields/number/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    protected function defineValueForEmailPreview(FakerFactory $faker): mixed
    {
        return $faker->randomDigit;
    }
}
