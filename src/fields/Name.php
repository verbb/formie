<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\SubFieldInterface;
use verbb\formie\base\SubField;
use verbb\formie\events\ModifyFrontEndSubFieldsEvent;
use verbb\formie\gql\types\NameType;
use verbb\formie\gql\types\generators\FieldAttributeGenerator;
use verbb\formie\gql\types\input\NameInputType;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Name as NameModel;
use verbb\formie\positions\AboveInput;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\helpers\Component;
use craft\helpers\Html;
use craft\helpers\Json;

use Faker\Generator as FakerFactory;

use GraphQL\Type\Definition\Type;

use yii\base\Event;
use yii\db\Schema;

class Name extends SubField implements PreviewableFieldInterface
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

    public static function dbType(): string
    {
        return Schema::TYPE_JSON;
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

    public function hasSubFields(): bool
    {
        if ($this->useMultipleFields) {
            return true;
        }

        return false;
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        // Quit early if a non-multi Name field, it's just plain text
        if (!$this->useMultipleFields) {
            return $value;
        }

        $value = parent::normalizeValue($value, $element);
        $value = Json::decodeIfJson($value);

        if (is_array($value)) {
            $name = new NameModel($value);
            $name->isMultiple = true;

            // Normalize prefix to null, due to it being a dropdown
            if ($name->prefix === '') {
                $name->prefix = null;
            }

            return $name;
        }

        return $value;
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($this->useMultipleFields) {
            return parent::serializeValue($value, $element);
        }

        return $value;
    }

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/name/preview', [
            'field' => $this,
        ]);
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

    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Use Multiple Name Fields'),
                'help' => Craft::t('formie', 'Whether this field should use multiple fields for users to enter their details.'),
                'name' => 'useMultipleFields',
            ]),
            SchemaHelper::subFieldsConfigurationField([
                'if' => '$get(useMultipleFields).value',
            ], [
                'type' => static::class,
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder'),
                'help' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                'name' => 'placeholder',
                'if' => '$get(useMultipleFields).value != true',
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Default Value'),
                'help' => Craft::t('formie', 'Set a default value for the field when it doesn’t have a value.'),
                'name' => 'defaultValue',
                'variables' => 'userVariables',
                'if' => '$get(useMultipleFields).value != true',
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
                'if' => '$get(useMultipleFields).value != true',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Error Message'),
                'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                'name' => 'errorMessage',
                'if' => '$get(required).value && $get(useMultipleFields).value != true',
            ]),
            SchemaHelper::prePopulate([
                'if' => '$get(useMultipleFields).value != true',
            ]),
            SchemaHelper::includeInEmailField(),
        ];
    }

    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::subFieldLabelPosition([
                'if' => '$get(useMultipleFields).value',
            ]),
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
            SchemaHelper::enableContentEncryptionField(),
        ];
    }

    public function defineConditionsSchema(): array
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

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        $form = $context['form'] ?? null;
        $errors = $context['errors'] ?? null;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        if ($this->useMultipleFields) {
            if ($key === 'fieldContainer') {
                return new HtmlTag('fieldset', [
                    'class' => 'fui-fieldset fui-subfield-fieldset',
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
                        'fui-sr-only' => $labelPosition instanceof HiddenPosition ? true : false,
                    ],
                ]);
            }
        }

        if ($key === 'fieldInput') {
            return new HtmlTag('input', [
                'type' => 'text',
                'id' => $id,
                'class' => [
                    'fui-input',
                    $errors ? 'fui-error' : false,
                ],
                'name' => $this->getHtmlName(),
                'placeholder' => Craft::t('formie', $this->placeholder) ?: null,
                'autocomplete' => 'name',
                'required' => $this->required ? true : null,
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

    protected function cpInputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
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
        if ($this->useMultipleFields) {
            return parent::defineValueAsString($value, $element);
        }

        return (string)$value;
    }

    protected function defineValueAsJson(mixed $value, ElementInterface $element = null): mixed
    {
        if ($this->useMultipleFields) {
            return parent::defineValueAsJson($value, $element);
        }

        return $value;
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

    protected function defineValueForEmailPreview(FakerFactory $faker): mixed
    {
        if ($this->useMultipleFields) {
            return new NameModel([
                'isMultiple' => true,
                'prefix' => $faker->title,
                'firstName' => $faker->firstName,
                'middleName' => $faker->firstName,
                'lastName' => $faker->lastName,
            ]);
        }
        
        return $faker->name;
    }

}
