<?php
namespace verbb\formie\fields;

use verbb\formie\base\Field;
use verbb\formie\base\PreviewableFieldInterface;
use verbb\formie\base\SortableFieldInterface;
use verbb\formie\fields\definitions\FieldReferenceValue;
use verbb\formie\fields\values\NumberFieldValue;
use verbb\formie\fields\traits\UniqueValueFieldTrait;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\ValidationMessagesHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\SlotTag;

use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;
use craft\gql\types\Number as NumberType;
use craft\helpers\Db;
use craft\helpers\Localization;
use craft\i18n\Locale;

use Faker\Generator as FakerFactory;

use GraphQL\Type\Definition\Type;

use yii\db\Schema;

use Throwable;

class Number extends Field implements SortableFieldInterface, PreviewableFieldInterface
{
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

    public static function supportsGqlConfigProvider(): bool
    {
        return true;
    }

    public static function gqlContentTypeFromConfig(array $config): Type|array
    {
        return NumberType::getType();
    }

    public static function gqlContentMutationArgumentTypeFromConfig(array $config): Type|array
    {
        return [
            'name' => $config['handle'] ?? '',
            'type' => NumberType::getType(),
            'description' => $config['instructions'] ?? null,
        ];
    }
    

    // Constants
    // =========================================================================

    public const EVENT_MODIFY_UNIQUE_QUERY = 'modifyUniqueQuery';


    // Traits
    // =========================================================================

    use UniqueValueFieldTrait;


    // Properties
    // =========================================================================

    public bool $limit = false;
    public int|float|null $min = null;
    public int|float|null $max = null;
    public ?int $decimals = null;


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

    public function fieldKind(): string
    {
        return self::KIND_TEXT;
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
        $rules[] = [$this->handle, 'number', 'min' => $this->min, 'max' => $this->max];

        foreach ($this->getUniqueValueElementValidationRules() as $rule) {
            $rules[] = $rule;
        }

        return $rules;
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewInput([
                'type' => 'number',
            ]),
        ];
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder'),
                'instructions' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                'name' => 'placeholder',
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Default Value'),
                'instructions' => Craft::t('formie', 'Set a default value for the field when it doesn’t have a value.'),
                'name' => 'defaultValue',
            ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Decimal Points'),
                'instructions' => Craft::t('formie', 'Set the number of decimal points to format the field value.'),
                'name' => 'decimals',
            ]),
            SchemaHelper::prePopulate(),
            SchemaHelper::includeInEmailFieldSummariesField(),
        ];
    }

    public function defineFormBuilderValidationSchema(): array
    {
        return [
            SchemaHelper::requiredField(),
            SchemaHelper::requiredValidationMessage(),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Limit Numbers'),
                'instructions' => Craft::t('formie', 'Whether to limit the numbers for this field.'),
                'name' => 'limit',
            ]),
            [
                '$el' => 'div',
                'attrs' => [
                    'class' => 'fui-row',
                ],
                'if' => 'limit',
                'children' => [
                    [
                        '$el' => 'div',
                        'attrs' => [
                            'class' => 'fui-col-6',
                        ],
                        'children' => [
                            SchemaHelper::numberField([
                                'label' => Craft::t('formie', 'Min Value'),
                                'instructions' => Craft::t('formie', 'Set a minimum value that users must enter.'),
                                'name' => 'min',
                            ]),
                            SchemaHelper::validationMessageField([
                                'messageKey' => ValidationMessagesHelper::KEY_NUMBER_MIN,
                                'name' => 'validationMessages.numberMin',
                                'if' => 'limit && min',
                                'tokens' => ['label', 'min'],
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
                                'instructions' => Craft::t('formie', 'Set a maximum value that users must enter.'),
                                'name' => 'max',
                            ]),
                            SchemaHelper::validationMessageField([
                                'messageKey' => ValidationMessagesHelper::KEY_NUMBER_MAX,
                                'name' => 'validationMessages.numberMax',
                                'if' => 'limit && max',
                                'tokens' => ['label', 'max'],
                            ]),
                        ],
                    ],
                ],
            ],
            SchemaHelper::validationMessageField([
                'messageKey' => ValidationMessagesHelper::KEY_NUMBER,
                'name' => 'validationMessages.number',
                'tokens' => ['label'],
            ]),
            SchemaHelper::matchField([
                'includedTypes' => [self::class],
            ]),
            SchemaHelper::matchValidationMessage(),
            ...$this->defineUniqueValueValidationSchema(),
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

    // Protected Methods
    // =========================================================================

    protected function supportedDefaults(): array
    {
        return ['decimals'];
    }

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;
        $errors = $context->errors;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        if ($key === 'fieldInput') {
            return SlotTag::make('input')
                ->core(array_merge([
                    'type' => 'number',
                    'id' => $id,
                    'name' => $this->getHtmlName(),
                    'placeholder' => Craft::t('formie', $this->placeholder) ?: null,
                    'required' => $this->required ? true : null,
                    'min' => $this->limit ? $this->min : false,
                    'max' => $this->limit ? $this->max : false,
                    'data-formie-input' => true,
                    'data-formie-number-input' => true,
                    'data-formie-input-id' => $dataId,
                    'data-formie-input-type' => 'number',
                    'data-formie-input-error-state' => $errors ? true : false,
                    'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
                ], ValidationMessagesHelper::numberValidationClientAttributes(
                    $this,
                    (bool)$this->limit,
                    $this->min,
                    $this->max,
                )))
                ->theme([
                    'class' => [
                        'formie-input',
                        'formie-number-input',
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

        $rules[] = [['defaultValue', 'min', 'max'], 'number'];
        $rules[] = [['decimals'], 'integer'];
        $rules[] = [['max'], 'compare', 'compareAttribute' => 'min', 'operator' => '>='];

        if (!$this->decimals) {
            $rules[] = [['defaultValue', 'min', 'max'], 'integer'];
        }

        foreach ($this->defineUniqueValueRules() as $rule) {
            $rules[] = $rule;
        }

        return $rules;
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
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

    protected function defineReferenceValues(): array
    {
        return [
            FieldReferenceValue::default([
                'variableTypes' => [
                    Variables::TYPE_NUMBER,
                    Variables::TYPE_TEXT,
                ],
            ]),
        ];
    }

    protected function defineValidationRules(): array
    {
        $validators = parent::defineValidationRules();
        $validators[] = [
            'type' => 'number',
            'min' => $this->min,
            'max' => $this->max,
        ];

        return $validators;
    }

    protected function defineClientInput(): array
    {
        return array_merge(parent::defineClientInput(), [
            'min' => $this->min,
            'max' => $this->max,
            'inputType' => 'number',
        ]);
    }

    protected function defineValueClass(): ?string
    {
        return NumberFieldValue::class;
    }
}
