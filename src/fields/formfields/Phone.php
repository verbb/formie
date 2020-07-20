<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\SubfieldInterface;
use verbb\formie\base\SubfieldTrait;
use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\Phone as PhoneModel;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use yii\db\Schema;

class Phone extends FormField implements SubfieldInterface
{
    // Traits
    // =========================================================================

    use SubfieldTrait;


    // Public Properties
    // =========================================================================

    /**
     * @var boolean
     * @deprecated use $countryEnabled
     */
    public $showCountryCode;

    public $validate;
    public $validateType;

    public $countryEnabled = true;
    public $countryCollapsed;
    public $countryLabel;
    public $countryPlaceholder;
    public $countryDefaultValue;

    public $numberCollapsed;
    public $numberLabel;
    public $numberPlaceholder;
    public $numberDefaultValue;


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Phone Number');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/phone/icon.svg';
    }

    /**
     * Returns a list of countries and their extensions.
     *
     * @return array
     */
    public static function getCountries()
    {
        return Formie::$plugin->getPhone()->getCountries();
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getContentColumnType(): string
    {
        if ($this->countryEnabled) {
            return Schema::TYPE_TEXT;
        } else {
            return Schema::TYPE_STRING;
        }
    }

    /**
     * @inheritDoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        $value = Json::decodeIfJson($value);

        if (is_array($value)) {
            $phone = new PhoneModel($value);
            $phone->hasCountryCode = isset($value['country']);

            return $phone;
        } else {
            $phone = new PhoneModel();
            $phone->number = $value;
            $phone->hasCountryCode = false;

            return $phone;
        }
    }

    /**
     * @inheritDoc
     */
    public function serializeValue($value, ElementInterface $element = null)
    {
        if ($value instanceof PhoneModel) {
            return Json::encode($value);
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        return [
            'validateType' => 'international',

            'countryEnabled' => true,
            'countryCollapsed' => true,
            'countryLabel' => Craft::t('formie', 'Country'),
            'countryPlaceholder' => '',
            'countryDefaultValue' => '',

            'numberCollapsed' => true,
            'numberLabel' => Craft::t('formie', 'Number'),
            'numberPlaceholder' => '',
            'numberDefaultValue' => '',
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndSubfields(): array
    {
        $row = [];
        if ($this->countryEnabled) {
            $row['country'] = 'tel-country-code';
        }

        $row['number'] = 'tel-national';

        return [
            $row,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getSubfieldOptions(): array
    {
        return [
            [
                'label' => Craft::t('formie', 'Country'),
                'handle' => 'country',
            ],
            [
                'label' => Craft::t('formie', 'Number'),
                'handle' => 'number',
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function validateRequiredFields(ElementInterface $element)
    {
        if ($this->countryEnabled && $this->required) {
            $value = $element->getFieldValue($this->handle);

            if (StringHelper::isBlank($value->country)) {
                $element->addError(
                    $this->handle,
                    Craft::t('formie', '"{label}" cannot be blank.', [
                        'label' => $this->countryLabel,
                    ])
                );
            }

            if (StringHelper::isBlank($value->number)) {
                $element->addError(
                    $this->handle,
                    Craft::t('formie', '"{label}" cannot be blank.', [
                        'label' => $this->numberLabel,
                    ])
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getExtraBaseFieldConfig(): array
    {
        return [
            'countries' => static::getCountries(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getIsTextInput(): bool
    {
        return !$this->countryEnabled;
    }

    /**
     * @inheritDoc
     */
    public function getIsFieldset(): bool
    {
        return !!$this->countryEnabled;
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/phone/input', [
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
        return Craft::$app->getView()->renderTemplate('formie/_formfields/phone/preview', [
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
                'label' => Craft::t('formie', 'Show Country Code Dropdown'),
                'help' => Craft::t('formie', 'Whether to show an additional dropdown for selecting the country code.'),
                'name' => 'countryEnabled',
            ]),
            SchemaHelper::toggleContainer('!settings.countryEnabled', [
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
                    'validation' => 'requiredIf:countryEnabled',
                    'required' => true,
                ]),
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Placeholder'),
                    'help' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                    'name' => $nestedField['handle'] . 'Placeholder',
                ]),
            ];

            if ($nestedField['handle'] === 'country') {
                $subfields[] = SchemaHelper::selectField([
                    'label' => Craft::t('formie', 'Default Value'),
                    'help' => Craft::t('formie', 'Entering a default value will place the value in the field when it loads.'),
                    'name' => $nestedField['handle'] . 'DefaultValue',
                    'options' => array_merge(
                        [[ 'label' => Craft::t('formie', 'Select an option'), 'value' => '' ]],
                        static::getCountries()
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
                'showEnabled' => false,
            ], $subfields);
        }

        $fields[] = SchemaHelper::toggleContainer('settings.countryEnabled', $toggleBlocks);

        return $fields;
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

            // TODO: implement more involved validation
            // SchemaHelper::lightswitchField([
            //     'label' => Craft::t('formie', 'Validate'),
            //     'help' => Craft::t('formie', 'Whether to validate the phone number.'),
            //     'name' => 'validate',
            // ]),
            // SchemaHelper::toggleContainer('settings.validate', [
            //     SchemaHelper::selectField([
            //         'label' => Craft::t('formie', 'Validate Country Type'),
            //         'help' => Craft::t('formie', 'Select either International, or limit to a specific country.'),
            //         'name' => 'validateType',
            //         'options' => array_merge(
            //             [[ 'label' => Craft::t('formie', 'International'), 'value' => 'international' ]],
            //             [[ 'label' => Craft::t('formie', 'Country'), 'value' => 'country' ]],
            //         ),
            //     ]),
            //     SchemaHelper::toggleContainer('settings.validateType', [
            //         SchemaHelper::selectField([
            //             'label' => Craft::t('formie', 'Country'),
            //             'help' => Craft::t('formie', 'Select a country to validate against.'),
            //             'name' => 'limitCountry',
            //             'options' => array_merge(
            //                 [[ 'label' => Craft::t('formie', 'Select an option'), 'value' => '' ]],
            //                 static::getCountries(),
            //             ),
            //         ]),
            //     ]),
            // ]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::labelPosition($this),
            SchemaHelper::toggleContainer('settings.countryEnabled', [
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
            SchemaHelper::inputAttributesField(),
        ];
    }
}
