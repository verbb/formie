<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\base\SubfieldInterface;
use verbb\formie\base\SubfieldTrait;
use verbb\formie\Formie;
use verbb\formie\gql\types\input\NameInputType;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\Name as NameModel;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\helpers\Json;

use yii\db\Schema;

class Name extends FormField implements SubfieldInterface, PreviewableFieldInterface
{
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
    public static function getPrefixOptions()
    {
        return [
            ['label' => Craft::t('formie', 'Mr.'), 'value' => 'mr'],
            ['label' => Craft::t('formie', 'Mrs.'), 'value' => 'mrs'],
            ['label' => Craft::t('formie', 'Ms.'), 'value' => 'ms'],
            ['label' => Craft::t('formie', 'Miss.'), 'value' => 'miss'],
            ['label' => Craft::t('formie', 'Dr.'), 'value' => 'dr'],
            ['label' => Craft::t('formie', 'Prof.'), 'value' => 'prof'],
        ];
    }


    // Properties
    // =========================================================================

    /**
     * @var boolean
     */
    public $useMultipleFields;

    public $prefixEnabled;
    public $prefixCollapsed;
    public $prefixLabel;
    public $prefixPlaceholder;
    public $prefixDefaultValue;
    public $prefixPrePopulate;
    public $prefixRequired;
    public $prefixErrorMessage;

    public $firstNameEnabled;
    public $firstNameCollapsed;
    public $firstNameLabel;
    public $firstNamePlaceholder;
    public $firstNameDefaultValue;
    public $firstNamePrePopulate;
    public $firstNameRequired;
    public $firstNameErrorMessage;

    public $middleNameEnabled;
    public $middleNameCollapsed;
    public $middleNameLabel;
    public $middleNamePlaceholder;
    public $middleNameDefaultValue;
    public $middleNamePrePopulate;
    public $middleNameRequired;
    public $middleNameErrorMessage;

    public $lastNameEnabled;
    public $lastNameCollapsed;
    public $lastNameLabel;
    public $lastNamePlaceholder;
    public $lastNameDefaultValue;
    public $lastNamePrePopulate;
    public $lastNameRequired;
    public $lastNameErrorMessage;


    // Public Methods
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

    /**
     * @inheritDoc
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_TEXT;
    }

    /**
     * @inheritDoc
     */
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
    public function normalizeValue($value, ElementInterface $element = null)
    {
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
    public function serializeValue($value, ElementInterface $element = null)
    {
        if ($value instanceof NameModel) {
            return Json::encode($value);
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function serializeValueForExport($value, ElementInterface $element = null)
    {
        if ($this->useMultipleFields) {
            $values = [];

            foreach ($this->getSubfieldOptions() as $subField) {
                if ($this->{$subField['handle'] . 'Enabled'}) {
                    $values[$this->handle . '_' . $subField['handle']] = $value[$subField['handle']] ?? '';
                }
            }

            return $values;
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function serializeValueForIntegration($value, ElementInterface $element = null)
    {
        if ($this->useMultipleFields) {
            return $value->toArray();
        }

        return $value;
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
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndSubfields(): array
    {
        $subFields = [];

        $rows = [
            [
                'prefix' => 'honorific-prefix',
                'firstName' => 'given-name',
                'middleName' => 'additional-name',
                'lastName' => 'family-name',
            ],
        ];

        foreach ($rows as $key => $row) {
            foreach ($row as $handle => $autocomplete) {
                $enabledProp = "{$handle}Enabled";

                if ($this->$enabledProp) {
                    $subFields[$key][$handle] = $autocomplete;
                }
            }
        }

        return array_filter($subFields);
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
    public function validateRequiredFields(ElementInterface $element)
    {
        if (!$this->useMultipleFields) {
            return;
        }

        $this->subfieldValidateRequiredFields($element);
    }

    /**
     * @inheritDoc
     */
    public function getIsTextInput(): bool
    {
        return !$this->useMultipleFields;
    }

    /**
     * @inheritDoc
     */
    public function getIsFieldset(): bool
    {
        return !!$this->useMultipleFields;
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
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
            'field' => $this
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
            SchemaHelper::toggleContainer('!settings.useMultipleFields', [
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Placeholder'),
                    'help' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                    'name' => 'placeholder',
                ]),
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Default Value'),
                    'help' => Craft::t('formie', 'Entering a default value will place the value in the field when it loads.'),
                    'name' => 'defaultValue',
                ]),
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
                        [[ 'label' => Craft::t('formie', 'Select an option'), 'value' => '' ]],
                        static::getPrefixOptions()
                    ),
                ]);
            } else {
                $subfields[] = SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Default Value'),
                    'help' => Craft::t('formie', 'Entering a default value will place the value in the field when it loads.'),
                    'name' => $nestedField['handle'] . 'DefaultValue',
                ]);
            }

            $toggleBlocks[] = SchemaHelper::toggleBlock([
                'blockLabel' => $nestedField['label'],
                'blockHandle' => $nestedField['handle'],
            ], $subfields);
        }

        $fields[] = SchemaHelper::toggleContainer('settings.useMultipleFields', [
            SchemaHelper::toggleBlocks([
                'subfields' => $this->getSubfieldOptions(),
            ], $toggleBlocks),
        ]);

        return $fields;
    }

    /**
     * @inheritDoc
     */
    public function defineSettingsSchema(): array
    {
        foreach ($this->getSubfieldOptions() as $key => $nestedField) {
            $subfields = [
                SchemaHelper::lightswitchField([
                    'label' => Craft::t('formie', 'Required Field'),
                    'help' => Craft::t('formie', 'Whether this field should be required when filling out the form.'),
                    'name' => $nestedField['handle'] . 'Required',
                ]),
                SchemaHelper::toggleContainer('settings.' . $nestedField['handle'] . 'Required', [
                    SchemaHelper::textField([
                        'label' => Craft::t('formie', 'Error Message'),
                        'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                        'name' => $nestedField['handle'] . 'ErrorMessage',
                    ]),
                ]),
                SchemaHelper::prePopulate([
                    'name' => $nestedField['handle'] . 'PrePopulate',
                ]),
            ];

            $fields[] = SchemaHelper::toggleContainer('settings.useMultipleFields', [
                SchemaHelper::toggleBlock([
                    'blockLabel' => $nestedField['label'],
                    'blockHandle' => $nestedField['handle'],
                    'showToggle' => false,
                    'showEnabled' => false,
                    'showOnlyIfEnabled' => true,
                ], $subfields),
            ]);
        }

        $fields[] = SchemaHelper::toggleContainer('!settings.useMultipleFields', [
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
            SchemaHelper::prePopulate(),
        ]);

        return $fields;
    }

    /**
     * @inheritDoc
     */
    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::labelPosition($this),
            SchemaHelper::toggleContainer('settings.useMultipleFields', [
                SchemaHelper::subfieldLabelPosition(),
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
        ];
    }

    /**
     * @inheritDoc
     */
    public function getContentGqlMutationArgumentType()
    {
        return NameInputType::getType($this);
    }
    
}
