<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\FixedParentFieldInterface;
use verbb\formie\base\FixedParentField;
use verbb\formie\base\PreviewableFieldInterface;
use verbb\formie\base\SortableFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\fields\definitions\FieldClientChildren;
use verbb\formie\fields\definitions\FieldReferenceValue;
use verbb\formie\fields\definitions\FieldValueClass;
use verbb\formie\gql\types\NameType;
use verbb\formie\gql\types\generators\FieldAttributeGenerator;
use verbb\formie\gql\types\input\NameInputType;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\ValidationMessagesHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\fields\values\NameFieldValue;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\SlotTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;
use verbb\formie\positions\AboveInput;
use verbb\formie\positions\Hidden as HiddenPosition;
use verbb\formie\query\NestedFieldQueryHelper;

use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Component;
use craft\helpers\Html;
use craft\helpers\Json;

use Faker\Generator as FakerFactory;

use GraphQL\Type\Definition\Type;

use yii\base\Event;
use yii\db\ExpressionInterface;
use yii\db\Schema;

class Name extends FixedParentField implements SortableFieldInterface, PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Name');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/name/icon.svg';
    }

    public static function supportsGqlConfigProvider(): bool
    {
        return true;
    }

    public static function gqlContentTypeFromConfig(array $config): Type|array
    {
        $settings = Formie::$plugin->getFields()->getFieldConfigSettings($config);

        return !empty($settings['useMultipleFields']) ? NameType::getType() : Type::string();
    }

    public static function gqlContentMutationArgumentTypeFromConfig(array $config): Type|array
    {
        $settings = Formie::$plugin->getFields()->getFieldConfigSettings($config);

        return !empty($settings['useMultipleFields']) ? NameInputType::getTypeFromConfig($config) : Type::string();
    }

    public static function dbType(): string
    {
        return Schema::TYPE_JSON;
    }

    public static function queryCondition(array $instances, mixed $value, array &$params): array|string|ExpressionInterface|false|null
    {
        $nestedCondition = NestedFieldQueryHelper::buildQueryCondition($instances, $value);

        // Name can be either nested (multi field) or scalar (single field).
        // If nested mapping cannot produce a condition, fall back to scalar.
        if ($nestedCondition !== null) {
            return $nestedCondition;
        }

        return Field::queryCondition($instances, $value, $params);
    }


    // Properties
    // =========================================================================

    public bool $useMultipleFields = false;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        unset(
            $config['prefixEnabled'],
            $config['prefixCollapsed'],
            $config['prefixEnabled'],
            $config['prefixCollapsed'],
            $config['prefixLabel'],
            $config['prefixPlaceholder'],
            $config['prefixDefaultValue'],
            $config['prefixPrePopulate'],
            $config['prefixRequired'],
            $config['prefixErrorMessage'],

            $config['firstNameEnabled'],
            $config['firstNameCollapsed'],
            $config['firstNameLabel'],
            $config['firstNamePlaceholder'],
            $config['firstNameDefaultValue'],
            $config['firstNamePrePopulate'],
            $config['firstNameRequired'],
            $config['firstNameErrorMessage'],

            $config['middleNameEnabled'],
            $config['middleNameCollapsed'],
            $config['middleNameLabel'],
            $config['middleNamePlaceholder'],
            $config['middleNameDefaultValue'],
            $config['middleNamePrePopulate'],
            $config['middleNameRequired'],
            $config['middleNameErrorMessage'],

            $config['lastNameEnabled'],
            $config['lastNameCollapsed'],
            $config['lastNameLabel'],
            $config['lastNamePlaceholder'],
            $config['lastNameDefaultValue'],
            $config['lastNamePrePopulate'],
            $config['lastNameRequired'],
            $config['lastNameErrorMessage'],
        );

        $config['instructionsPosition'] = $config['instructionsPosition'] ?? AboveInput::class;

        parent::__construct($config);
    }

    public function fieldKind(): string
    {
        return self::KIND_NAME;
    }

    public function hasFieldLayout(): bool
    {
        // Single-name mode renders and stores a scalar value. Preserve any existing
        // multi-part layout so switching back can reuse child field UIDs/settings.
        return $this->useMultipleFields;
    }

    public function getIsRequired(): ?bool
    {
        if (!$this->useMultipleFields) {
            return $this->required;
        }

        return parent::getIsRequired();
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        // Single-value Name fields can still receive legacy structured payloads if the field
        // used to be multi-part, or if defaults/prefill values were saved before a config toggle.
        // Collapse those payloads back to a plain string so text inputs never render `[]`.
        if (!$this->useMultipleFields) {
            if (is_string($value)) {
                if ($this->enableContentEncryption || str_contains($value, 'base64:')) {
                    $value = StringHelper::decdec($value);
                }

                $value = StringHelper::normalizePlainText($value);
                $value = $this->sanitizePlainTextValueIfConfigured($value);
            }

            $value = Json::decodeIfJson($value);

            if ($value instanceof NameFieldValue) {
                return $value->isEmpty() ? null : (string)$value;
            }

            if (is_array($value)) {
                $isMultipleValue = (bool)array_intersect(array_keys($value), ['prefix', 'prefixOption', 'firstName', 'middleName', 'lastName']);
                $name = new NameFieldValue($value + ['isMultiple' => $isMultipleValue]);

                return $name->isEmpty() ? null : (string)$name;
            }

            if ($value === null) {
                return null;
            }

            return (string)$value;
        }

        $value = parent::normalizeValue($value, $element);
        $value = Json::decodeIfJson($value);

        if (is_array($value)) {
            $name = new NameFieldValue($value);
            $name->isMultiple = true;

            // Normalize prefix to null, due to it being a dropdown
            if ($name->prefix === '') {
                $name->prefix = null;
            }

            // Reset any disabled fields that might have content to null
            foreach ($this->getFields() as $field) {
                if ($field->getIsDisabled() && property_exists($name, $field->handle)) {
                    $name->{$field->handle} = null;
                }
            }

            return $name->isEmpty() ? null : $name;
        }

        return null;
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($this->useMultipleFields) {
            return parent::serializeValue($value, $element);
        }

        return $value;
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewContainerParent([
                'if' => 'field.useMultipleFields',
            ]),
            SchemaHelper::previewInput([
                'if' => 'field.useMultipleFields == false',
            ]),
        ];
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'useMultipleFields' => [
                'name' => 'useMultipleFields',
                'type' => Type::boolean(),
            ],
        ]);
    }

    public function getContentGqlType(): Type|array
    {
        return $this->useMultipleFields ? NameType::getType() : Type::string();
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Use Multiple Name Fields'),
                'instructions' => Craft::t('formie', 'Whether this field should use multiple fields for users to enter their details.'),
                'name' => 'useMultipleFields',
            ]),
            SchemaHelper::nestedFieldsConfigurationField([
                'if' => 'useMultipleFields',
                'label' => Craft::t('formie', 'Sub-Field Configuration'),
                'instructions' => Craft::t('formie', 'Configure the sub-fields for this field. Move to rearrange columns and rows, and click to edit sub-field settings.'),
                'children' => [
                    [
                        '$cmp' => 'NestedLayout',
                        'props' => [
                            'parentType' => static::class,
                            'layoutKey' => 'rows',
                        ],
                    ],
                ],
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder'),
                'instructions' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                'name' => 'placeholder',
                'if' => 'useMultipleFields != true',
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Default Value'),
                'instructions' => Craft::t('formie', 'Set a default value for the field when it doesn’t have a value.'),
                'name' => 'defaultValue',
                'variableConfig' => [
                    'content' => Variables::CONTENT_SINGLE_LINE,
                    'types' => [Variables::TYPE_TEXT],
                    'groups' => [
                        Variables::STATIC_FORM,
                        Variables::STATIC_GENERAL,
                        Variables::STATIC_SITE,
                    ],
                ],
                'if' => 'useMultipleFields != true',
            ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::prePopulate([
                'if' => 'useMultipleFields != true',
            ]),
            SchemaHelper::includeInEmailFieldSummariesField(),
        ];
    }

    public function defineFormBuilderValidationSchema(): array
    {
        return [
            SchemaHelper::requiredField(['if' => 'useMultipleFields != true']),
            SchemaHelper::requiredValidationMessage(['if' => 'required && useMultipleFields != true']),
        ];
    }

    public function defineFormBuilderAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::subFieldLabelPosition([
                'if' => 'useMultipleFields',
            ]),
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
            SchemaHelper::enableContentEncryptionField(),
        ];
    }

    public function defineFormBuilderConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    public function getContentGqlMutationArgumentType(): Type|array
    {
        if ($this->useMultipleFields) {
            return NameInputType::getType($this);
        }

        return Type::string();
    }
    

    // Protected Methods
    // =========================================================================

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;
        $errors = $context->errors;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        if ($this->useMultipleFields) {
            if ($key === 'fieldLayout') {
                return SlotTag::make('fieldset')
                    ->core([
                        'data-formie-field-layout' => true,
                        'data-formie-name-field-layout' => true,
                        'data-formie-subfield-fieldset' => true,
                    ])
                    ->theme([
                        'class' => [
                            'formie-field-layout',
                            'formie-name-field-layout',
                            'formie-subfield-fieldset',
                        ],
                    ]);
            }

            if ($key === 'fieldLabel') {
                $labelPosition = $context->get('labelPosition');

                return SlotTag::make('legend')
                    ->core([
                        'data-formie-label' => true,
                        'data-formie-field-label' => true,
                        'data-formie-name-field-label' => true,
                        'data-formie-sr-only' => $labelPosition instanceof HiddenPosition ? true : false,
                    ])
                    ->theme([
                        'class' => [
                            'formie-label',
                            'formie-field-label',
                            'formie-name-field-label',
                            $labelPosition instanceof HiddenPosition ? 'formie-sr-only' : false,
                        ],
                    ]);
            }
        }

        if ($key === 'fieldInput') {
            return SlotTag::make('input')
                ->core(array_merge([
                    'type' => 'text',
                    'id' => $id,
                    'name' => $this->getHtmlName(),
                    'placeholder' => Craft::t('formie', $this->placeholder) ?: null,
                    'autocomplete' => 'name',
                    'required' => $this->required ? true : null,
                    'data-formie-input' => true,
                    'data-formie-name-input' => true,
                    'data-formie-input-id' => $dataId,
                    'data-formie-input-type' => 'text',
                    'data-formie-input-error-state' => $errors ? true : false,
                    'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
                ], ValidationMessagesHelper::requiredClientAttributes($this)))
                ->theme([
                    'class' => [
                        'formie-input',
                        'formie-name-input',
                        $errors ? 'formie-input-error' : false,
                    ],
                ])
                ->instanceAttributes($this->getInputAttributes());
        }

        return parent::defineFieldSlotTag($key, $context);
    }

    protected function defineSubFields(): array
    {
        return [
            [
                'fields' => [
                    [
                        'type' => subfields\NamePrefix::class,
                        'label' => Craft::t('formie', 'Prefix'),
                        'handle' => 'prefix',
                        'enabled' => false,
                        'labelPosition' => $this->subFieldLabelPosition,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'honorific-prefix',
                            ],
                        ],
                    ],
                    [
                        'type' => subfields\NameFirst::class,
                        'label' => Craft::t('formie', 'First Name'),
                        'handle' => 'firstName',
                        'enabled' => true,
                        'labelPosition' => $this->subFieldLabelPosition,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'given-name',
                            ],
                        ],
                    ],
                    [
                        'type' => subfields\NameMiddle::class,
                        'label' => Craft::t('formie', 'Middle Name'),
                        'handle' => 'middleName',
                        'enabled' => false,
                        'labelPosition' => $this->subFieldLabelPosition,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'additional-name',
                            ],
                        ],
                    ],
                    [
                        'type' => subfields\NameLast::class,
                        'label' => Craft::t('formie', 'Last Name'),
                        'handle' => 'lastName',
                        'enabled' => true,
                        'labelPosition' => $this->subFieldLabelPosition,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'family-name',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/name/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'element' => $element,
        ]);
    }

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
    {
        // Always return a string for the "full name" value.
        return (string)$value;
    }

    protected function defineValueAsArray(mixed $value, ElementInterface $element = null): mixed
    {
        if ($this->useMultipleFields) {
            return parent::defineValueAsArray($value, $element);
        }

        return (string)$value !== '' ? [(string)$value] : [];
    }

    protected function defineValueForExport(mixed $value, ElementInterface $element = null): mixed
    {
        if ($this->useMultipleFields) {
            return parent::defineValueForExport($value, $element);
        }

        return $value;
    }

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): string
    {
        // Always return a string for the summary, which makes sense given a "name" value.
        return (string)$value;
    }

    protected function defineValueForCondition(mixed $value, Submission $submission): mixed
    {
        if ($this->useMultipleFields) {
            return parent::defineValueForCondition($value, $submission);
        }

        return $this->serializeValue($value, $submission);
    }

    protected function defineValueForEmailPreview(FakerFactory $faker): mixed
    {
        if ($this->useMultipleFields) {
            return new NameFieldValue([
                'isMultiple' => true,
                'prefix' => strtolower(str_replace(['.', ','], '', $faker->title)),
                'firstName' => $faker->firstName,
                'middleName' => $faker->firstName,
                'lastName' => $faker->lastName,
            ]);
        }
        
        return $faker->name;
    }

    protected function defineClientInput(): array
    {
        return array_merge(parent::defineClientInput(), [
            'multiple' => (bool)$this->useMultipleFields,
        ]);
    }

    protected function supportsPlainTextHtmlSanitization(): bool
    {
        return !$this->useMultipleFields;
    }

    protected function dbTypeForValueSql(): array|string|null
    {
        // Single-value name fields serialize as scalar strings even though
        // multi-part names use JSON storage for their child values.
        return $this->useMultipleFields ? parent::dbTypeForValueSql() : Schema::TYPE_STRING;
    }

    protected function defineValueClass(): ?string
    {
        return NameFieldValue::class;
    }

    protected function defineReferenceValues(): array
    {
        return [
            FieldReferenceValue::default([
                'handle' => '__toString',
                'label' => Craft::t('formie', 'Full Name'),
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'prefix',
                'label' => Craft::t('formie', 'Prefix'),
                'if' => 'useMultipleFields == true',
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'firstName',
                'label' => Craft::t('formie', 'First Name'),
                'if' => 'useMultipleFields == true',
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'middleName',
                'label' => Craft::t('formie', 'Middle Name'),
                'if' => 'useMultipleFields == true',
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'lastName',
                'label' => Craft::t('formie', 'Last Name'),
                'if' => 'useMultipleFields == true',
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
        ];
    }

}
