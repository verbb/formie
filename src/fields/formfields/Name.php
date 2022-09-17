<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\SubfieldInterface;
use verbb\formie\base\SubfieldTrait;
use verbb\formie\events\ModifyFrontEndSubfieldsEvent;
use verbb\formie\events\ModifyNamePrefixOptionsEvent;
use verbb\formie\gql\types\generators\FieldAttributeGenerator;
use verbb\formie\gql\types\input\NameInputType;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\Name as NameModel;
use verbb\formie\positions\AboveInput;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Component;
use craft\helpers\Html;
use craft\helpers\Json;

use yii\base\Event;
use yii\db\Schema;

use GraphQL\Type\Definition\Type;

class Name extends FormField implements SubfieldInterface, PreviewableFieldInterface
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_FRONT_END_SUBFIELDS = 'modifyFrontEndSubfields';
    public const EVENT_MODIFY_PREFIX_OPTIONS = 'modifyPrefixOptions';


    // Traits
    // =========================================================================

    use SubfieldTrait {
        validateRequiredFields as subfieldValidateRequiredFields;
    }


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Name');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/name/icon.svg';
    }

    /**
     * Returns a list of available prefixes.
     *
     * @return array[]
     */
    public static function getPrefixOptions(): array
    {
        $options = [
            ['label' => Craft::t('formie', 'Mr.'), 'value' => 'mr'],
            ['label' => Craft::t('formie', 'Mrs.'), 'value' => 'mrs'],
            ['label' => Craft::t('formie', 'Ms.'), 'value' => 'ms'],
            ['label' => Craft::t('formie', 'Miss.'), 'value' => 'miss'],
            ['label' => Craft::t('formie', 'Mx.'), 'value' => 'mx'],
            ['label' => Craft::t('formie', 'Dr.'), 'value' => 'dr'],
            ['label' => Craft::t('formie', 'Prof.'), 'value' => 'prof'],
        ];

        $event = new ModifyNamePrefixOptionsEvent([
            'options' => $options,
        ]);

        Event::trigger(static::class, self::EVENT_MODIFY_PREFIX_OPTIONS, $event);

        return $event->options;
    }


    // Properties
    // =========================================================================

    public bool $useMultipleFields = false;

    public bool $prefixEnabled = false;
    public bool $prefixCollapsed = true;
    public ?string $prefixLabel = null;
    public ?string $prefixPlaceholder = null;
    public ?string $prefixDefaultValue = null;
    public ?string $prefixPrePopulate = null;
    public bool $prefixRequired = false;
    public ?string $prefixErrorMessage = null;

    public bool $firstNameEnabled = true;
    public bool $firstNameCollapsed = true;
    public ?string $firstNameLabel = null;
    public ?string $firstNamePlaceholder = null;
    public ?string $firstNameDefaultValue = null;
    public ?string $firstNamePrePopulate = null;
    public bool $firstNameRequired = false;
    public ?string $firstNameErrorMessage = null;

    public bool $middleNameEnabled = false;
    public bool $middleNameCollapsed = true;
    public ?string $middleNameLabel = null;
    public ?string $middleNamePlaceholder = null;
    public ?string $middleNameDefaultValue = null;
    public ?string $middleNamePrePopulate = null;
    public bool $middleNameRequired = false;
    public ?string $middleNameErrorMessage = null;

    public bool $lastNameEnabled = true;
    public bool $lastNameCollapsed = true;
    public ?string $lastNameLabel = null;
    public ?string $lastNamePlaceholder = null;
    public ?string $lastNameDefaultValue = null;
    public ?string $lastNamePrePopulate = null;
    public bool $lastNameRequired = false;
    public ?string $lastNameErrorMessage = null;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getContentColumnType(): array|string
    {
        return Schema::TYPE_TEXT;
    }

    public function hasSubfields(): bool
    {
        if ($this->useMultipleFields) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        $value = parent::normalizeValue($value, $element);
        $value = Json::decodeIfJson($value);

        if (is_array($value)) {
            $name = new NameModel($value);
            $name->isMultiple = true;

            return $name;
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if ($value instanceof NameModel) {
            $value = Json::encode($value);
        }

        return parent::serializeValue($value, $element);
    }

    /**
     * @inheritDoc
     */
    public function getExtraBaseFieldConfig(): array
    {
        return [
            'prefixOptions' => static::getPrefixOptions(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        return [
            'prefixEnabled' => false,
            'prefixCollapsed' => true,
            'prefixLabel' => Craft::t('formie', 'Prefix'),
            'prefixDefaultValue' => '',
            'prefixPrePopulate' => '',

            'firstNameEnabled' => true,
            'firstNameCollapsed' => true,
            'firstNameLabel' => Craft::t('formie', 'First Name'),
            'firstNameDefaultValue' => '',
            'firstNamePrePopulate' => '',

            'middleNameEnabled' => false,
            'middleNameCollapsed' => true,
            'middleNameLabel' => Craft::t('formie', 'Middle Name'),
            'middleNameDefaultValue' => '',
            'middleNamePrePopulate' => '',

            'lastNameEnabled' => true,
            'lastNameCollapsed' => true,
            'lastNameLabel' => Craft::t('formie', 'Last Name'),
            'lastNameDefaultValue' => '',
            'lastNamePrePopulate' => '',

            'instructionsPosition' => AboveInput::class,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndSubfields($context): array
    {
        $subFields = [];

        $rowConfigs = [
            [
                [
                    'type' => Dropdown::class,
                    'name' => $this->prefixLabel,
                    'handle' => 'prefix',
                    'required' => $this->prefixRequired,
                    'placeholder' => $this->prefixPlaceholder,
                    'errorMessage' => $this->prefixErrorMessage,
                    'defaultValue' => $this->getDefaultValue('prefix'),
                    'labelPosition' => $this->subfieldLabelPosition,
                    'options' => $this->prefixOptions,
                    'inputAttributes' => [
                        [
                            'label' => 'autocomplete',
                            'value' => 'honorific-prefix',
                        ],
                    ],
                ],
                [
                    'type' => SingleLineText::class,
                    'name' => $this->firstNameLabel,
                    'handle' => 'firstName',
                    'required' => $this->firstNameRequired,
                    'placeholder' => $this->firstNamePlaceholder,
                    'errorMessage' => $this->firstNameErrorMessage,
                    'defaultValue' => $this->getDefaultValue('firstName'),
                    'labelPosition' => $this->subfieldLabelPosition,
                    'inputAttributes' => [
                        [
                            'label' => 'autocomplete',
                            'value' => 'given-name',
                        ],
                    ],
                ],
                [
                    'type' => SingleLineText::class,
                    'name' => $this->middleNameLabel,
                    'handle' => 'middleName',
                    'required' => $this->middleNameRequired,
                    'placeholder' => $this->middleNamePlaceholder,
                    'errorMessage' => $this->middleNameErrorMessage,
                    'defaultValue' => $this->getDefaultValue('middleName'),
                    'labelPosition' => $this->subfieldLabelPosition,
                    'inputAttributes' => [
                        [
                            'label' => 'autocomplete',
                            'value' => 'additional-name',
                        ],
                    ],
                ],
                [
                    'type' => SingleLineText::class,
                    'name' => $this->lastNameLabel,
                    'handle' => 'lastName',
                    'required' => $this->lastNameRequired,
                    'placeholder' => $this->lastNamePlaceholder,
                    'errorMessage' => $this->lastNameErrorMessage,
                    'defaultValue' => $this->getDefaultValue('lastName'),
                    'labelPosition' => $this->subfieldLabelPosition,
                    'inputAttributes' => [
                        [
                            'label' => 'autocomplete',
                            'value' => 'family-name',
                        ],
                    ],
                ],
            ],
        ];

        foreach ($rowConfigs as $key => $rowConfig) {
            foreach ($rowConfig as $config) {
                $handle = $config['handle'];
                $enabledProp = "{$handle}Enabled";

                if ($this->$enabledProp) {
                    $subField = Component::createComponent($config, FormFieldInterface::class);

                    // Ensure we set the parent field instance to handle the nested nature of subfields
                    $subField->setParentField($this);

                    $subFields[$key][] = $subField;
                }
            }
        }

        $event = new ModifyFrontEndSubfieldsEvent([
            'field' => $this,
            'rows' => $subFields,
        ]);

        Event::trigger(static::class, self::EVENT_MODIFY_FRONT_END_SUBFIELDS, $event);

        return $event->rows;
    }

    /**
     * @inheritDoc
     */
    public function getSubfieldOptions(): array
    {
        return [
            [
                'label' => Craft::t('formie', 'Prefix'),
                'handle' => 'prefix',
            ],
            [
                'label' => Craft::t('formie', 'First Name'),
                'handle' => 'firstName',
            ],
            [
                'label' => Craft::t('formie', 'Middle Name'),
                'handle' => 'middleName',
            ],
            [
                'label' => Craft::t('formie', 'Last Name'),
                'handle' => 'lastName',
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function validateRequiredFields(ElementInterface $element): void
    {
        if (!$this->useMultipleFields) {
            return;
        }

        $this->subfieldValidateRequiredFields($element);
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/name/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'element' => $element,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/name/preview', [
            'field' => $this,
        ]);
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'prefixOptions' => [
                'name' => 'prefixOptions',
                'type' => Type::listOf(FieldAttributeGenerator::generateType()),
            ],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function defineGeneralSchema(): array
    {
        $fields = [
            SchemaHelper::labelField(),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Use Multiple Name Fields'),
                'help' => Craft::t('formie', 'Whether this field should use multiple fields for users to enter their details.'),
                'name' => 'useMultipleFields',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder'),
                'help' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                'name' => 'placeholder',
                'if' => '$get(useMultipleFields).value != true',
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Default Value'),
                'help' => Craft::t('formie', 'Entering a default value will place the value in the field when it loads.'),
                'name' => 'defaultValue',
                'variables' => 'userVariables',
                'if' => '$get(useMultipleFields).value != true',
            ]),
        ];

        $toggleBlocks = [];

        foreach ($this->getSubfieldOptions() as $key => $nestedField) {
            $subfields = [
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Label'),
                    'help' => Craft::t('formie', 'The label that describes this field.'),
                    'name' => $nestedField['handle'] . 'Label',
                    'validation' => 'requiredIf:' . $nestedField['handle'] . 'Enabled',
                    'required' => true,
                ]),

                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Placeholder'),
                    'help' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                    'name' => $nestedField['handle'] . 'Placeholder',
                ]),
            ];

            if ($nestedField['handle'] === 'prefix') {
                $subfields[] = SchemaHelper::selectField([
                    'label' => Craft::t('formie', 'Default Value'),
                    'help' => Craft::t('formie', 'Entering a default value will place the value in the field when it loads.'),
                    'name' => $nestedField['handle'] . 'DefaultValue',
                    'options' => array_merge(
                        [['label' => Craft::t('formie', 'Select an option'), 'value' => '']],
                        static::getPrefixOptions()
                    ),
                ]);
            } else {
                $subfields[] = SchemaHelper::variableTextField([
                    'label' => Craft::t('formie', 'Default Value'),
                    'help' => Craft::t('formie', 'Entering a default value will place the value in the field when it loads.'),
                    'name' => $nestedField['handle'] . 'DefaultValue',
                    'variables' => 'userVariables',
                ]);
            }

            $toggleBlock = SchemaHelper::toggleBlock([
                'blockLabel' => $nestedField['label'],
                'blockHandle' => $nestedField['handle'],
            ], $subfields);

            $toggleBlock['if'] = '$get(useMultipleFields).value';

            $toggleBlocks[] = $toggleBlock;
        }

        $fields[] = SchemaHelper::toggleBlocks([
            'subfields' => $this->getSubfieldOptions(),
        ], $toggleBlocks);

        return $fields;
    }

    /**
     * @inheritDoc
     */
    public function defineSettingsSchema(): array
    {
        $fields = [
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
        ];

        foreach ($this->getSubfieldOptions() as $key => $nestedField) {
            $subfields = [
                SchemaHelper::lightswitchField([
                    'label' => Craft::t('formie', 'Required Field'),
                    'help' => Craft::t('formie', 'Whether this field should be required when filling out the form.'),
                    'name' => $nestedField['handle'] . 'Required',
                ]),
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Error Message'),
                    'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                    'name' => $nestedField['handle'] . 'ErrorMessage',
                    'if' => '$get(' . $nestedField['handle'] . 'Required).value',
                ]),
                SchemaHelper::prePopulate([
                    'name' => $nestedField['handle'] . 'PrePopulate',
                ]),
            ];

            $toggleBlock = SchemaHelper::toggleBlock([
                'blockLabel' => $nestedField['label'],
                'blockHandle' => $nestedField['handle'],
                'showToggle' => false,
                'showEnabled' => false,
            ], $subfields);

            $toggleBlock['if'] = '$get(' . $nestedField['handle'] . 'Enabled).value && $get(useMultipleFields).value';

            $fields[] = $toggleBlock;
        }

        return $fields;
    }

    /**
     * @inheritDoc
     */
    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::subfieldLabelPosition([
                'if' => '$get(useMultipleFields).value',
            ]),
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

    /**
     * @inheritDoc
     */
    public function getContentGqlMutationArgumentType(): array|Type
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
                return new HtmlTag('legend', [
                    'class' => 'fui-legend',
                ]);
            }
        }

        if ($key === 'fieldInput') {
            return new HtmlTag('input', array_merge([
                'type' => 'text',
                'id' => $id,
                'class' => [
                    'fui-input',
                    $errors ? 'fui-error' : false,
                ],
                'name' => $this->getHtmlName(),
                'placeholder' => Craft::t('formie', $this->placeholder) ?: null,
                'required' => $this->required ? true : null,
                'data' => [
                    'fui-id' => $dataId,
                    'fui-message' => Craft::t('formie', $this->errorMessage) ?: null,
                ],
                'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
            ], $this->getInputAttributes()));
        }

        return parent::defineHtmlTag($key, $context);
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [
            ['subfieldLabelPosition'],
            'in',
            'range' => Formie::$plugin->getFields()->getLabelPositions(),
            'skipOnEmpty' => true,
        ];

        return $rules;
    }

    protected function defineValueForExport($value, ElementInterface $element = null): mixed
    {
        if ($this->useMultipleFields) {
            $values = [];

            foreach ($this->getSubfieldOptions() as $subField) {
                if ($this->{$subField['handle'] . 'Enabled'}) {
                    $values[$this->getExportLabel($element) . ': ' . $subField['label']] = $value[$subField['handle']] ?? '';
                }
            }

            return $values;
        }

        return $value;
    }

}
