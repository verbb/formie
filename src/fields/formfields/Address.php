<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\base\Integration;
use verbb\formie\base\SubfieldInterface;
use verbb\formie\base\SubfieldTrait;
use verbb\formie\elements\Form;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\Address as AddressModel;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use CommerceGuys\Addressing\Country\CountryRepository;

use yii\db\Schema;

class Address extends FormField implements SubfieldInterface
{
    // Public Properties
    // =========================================================================

    use SubfieldTrait;


    // Public Properties
    // =========================================================================

    public $enableAutocomplete;
    public $autocompleteIntegration;

    public $autocompleteEnabled;
    public $autocompleteCollapsed;
    public $autocompleteLabel;
    public $autocompletePlaceholder;
    public $autocompleteDefaultValue;
    public $autocompleteRequired;
    public $autocompleteErrorMessage;

    public $address1Enabled;
    public $address1Collapsed;
    public $address1Label;
    public $address1Placeholder;
    public $address1DefaultValue;
    public $address1Required;
    public $address1ErrorMessage;

    public $address2Enabled;
    public $address2Collapsed;
    public $address2Label;
    public $address2Placeholder;
    public $address2DefaultValue;
    public $address2Required;
    public $address2ErrorMessage;

    public $address3Enabled;
    public $address3Collapsed;
    public $address3Label;
    public $address3Placeholder;
    public $address3DefaultValue;
    public $address3Required;
    public $address3ErrorMessage;

    public $cityEnabled;
    public $cityCollapsed;
    public $cityLabel;
    public $cityPlaceholder;
    public $cityDefaultValue;
    public $cityRequired;
    public $cityErrorMessage;

    public $stateEnabled;
    public $stateCollapsed;
    public $stateLabel;
    public $statePlaceholder;
    public $stateDefaultValue;
    public $stateRequired;
    public $stateErrorMessage;

    public $zipEnabled;
    public $zipCollapsed;
    public $zipLabel;
    public $zipPlaceholder;
    public $zipDefaultValue;
    public $zipRequired;
    public $zipErrorMessage;

    public $countryEnabled;
    public $countryCollapsed;
    public $countryLabel;
    public $countryPlaceholder;
    public $countryDefaultValue;
    public $countryRequired;
    public $countryErrorMessage;


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Address');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/address/icon.svg';
    }

    /**
     * Returns an array of countries.
     *
     * @return array
     */
    public static function getCountries()
    {
        $locale = Craft::$app->getLocale()->getLanguageID();

        $repo = new CountryRepository($locale);

        $countries = [];
        foreach ($repo->getList() as $value => $label) {
            $countries[] = compact('value', 'label');
        }

        return $countries;
    }


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
    public function normalizeValue($value, ElementInterface $element = null)
    {
        $value = Json::decodeIfJson($value);

        return new AddressModel($value);
    }

    /**
     * @inheritDoc
     */
    public function serializeValue($value, ElementInterface $element = null)
    {
        if ($value instanceof AddressModel) {
            return Json::encode($value);
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function serializeValueForExport($value, ElementInterface $element = null)
    {
        $values = [];

        foreach ($this->getSubfieldOptions() as $subField) {
            if ($this->{$subField['handle'] . 'Enabled'}) {
                $values[$this->handle . '_' . $subField['handle']] = $value[$subField['handle']] ?? '';
            }
        }

        return $values;
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
    public function getFieldDefaults(): array
    {
        return [
            'autocompleteEnabled' => true,
            'autocompleteCollapsed' => true,
            'autocompleteLabel' => Craft::t('formie', 'Auto-Complete'),

            'address1Enabled' => true,
            'address1Collapsed' => true,
            'address1Label' => Craft::t('formie', 'Address 1'),
            'address1DefaultValue' => '',

            'address2Enabled' => false,
            'address2Collapsed' => true,
            'address2Label' => Craft::t('formie', 'Address 2'),
            'address2DefaultValue' => '',

            'address3Enabled' => false,
            'address3Collapsed' => true,
            'address3Label' => Craft::t('formie', 'Address 3'),
            'address3DefaultValue' => '',

            'cityEnabled' => true,
            'cityCollapsed' => true,
            'cityLabel' => Craft::t('formie', 'City'),
            'cityDefaultValue' => '',

            'stateEnabled' => true,
            'stateCollapsed' => true,
            'stateLabel' => Craft::t('formie', 'State / Province'),
            'stateDefaultValue' => '',

            'zipEnabled' => true,
            'zipCollapsed' => true,
            'zipLabel' => Craft::t('formie', 'ZIP / Postal Code'),
            'zipDefaultValue' => '',

            'countryEnabled' => true,
            'countryCollapsed' => true,
            'countryLabel' => Craft::t('formie', 'Country'),
            'countryDefaultValue' => '',
        ];
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();
        $rules[] = [$this->handle, 'validateRequiredFields', 'skipOnEmpty' => false];

        return $rules;
    }

    /**
     * Validates the required address sub-fields.
     *
     * @param ElementInterface $element
     */
    public function validateRequiredFields(ElementInterface $element)
    {
        $subFields = [
            'address1',
            'address2',
            'address3',
            'city',
            'zip',
            'state',
            'country',
        ];

        if ($this->enableAutocomplete) {
            $subFields[] = 'autocomplete';
        }

        /* @var AddressModel $value */
        $value = $element->getFieldValue($this->handle);

        foreach ($subFields as $subField) {
            $labelProp = "{$subField}Label";
            $enabledProp = "{$subField}Enabled";
            $requiredProp = "{$subField}Required";
            $fieldValue = $value->$subField ?? '';

            if ($this->$enabledProp && ($this->required || $this->$requiredProp) && StringHelper::isBlank($fieldValue)) {
                $element->addError(
                    $this->handle,
                    Craft::t('formie', '"{label}" cannot be blank.', [
                        'label' => $this->$labelProp
                    ])
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndSubfields(): array
    {
        $subFields = [];

        $rows = [];

        if ($this->enableAutocomplete) {
            $rows[] = ['autocomplete' => 'autocomplete'];
        }

        $rows = array_merge($rows, [
            ['address1' => 'address-line1'],
            ['address2' => 'address-line2'],
            ['address3' => 'address-line3'],
            [
                'city' => 'address-level2',
                'zip' => 'postal-code',
            ],
            [
                'state' => 'address-level1',
                'country' => 'country',
            ],
        ]);

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
                'label' => Craft::t('formie', 'Address 1'),
                'handle' => 'address1',
            ],
            [
                'label' => Craft::t('formie', 'Address 2'),
                'handle' => 'address2',
            ],
            [
                'label' => Craft::t('formie', 'Address 3'),
                'handle' => 'address3',
            ],
            [
                'label' => Craft::t('formie', 'City'),
                'handle' => 'city',
            ],
            [
                'label' => Craft::t('formie', 'State / Province'),
                'handle' => 'state',
            ],
            [
                'label' => Craft::t('formie', 'ZIP / Postal Code'),
                'handle' => 'zip',
            ],
            [
                'label' => Craft::t('formie', 'Country'),
                'handle' => 'country',
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getIsFieldset(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/address/input', [
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
        return Craft::$app->getView()->renderTemplate('formie/_formfields/address/preview', [
            'field' => $this
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getAutocompleteHtml($options): string
    {
        if (!$this->enableAutocomplete || !$this->autocompleteIntegration) {
            return '';
        }

        $integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle($this->autocompleteIntegration);

        if (!$integration) {
            return '';
        }

        return $integration->getFrontEndHtml($this, $options);
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndJsVariables(Form $form)
    {
        if (!$this->enableAutocomplete || !$this->autocompleteIntegration) {
            return null;
        }

        $integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle($this->autocompleteIntegration);

        if (!$integration) {
            return null;
        }

        return $integration->getFrontEndJsVariables($form, $this);
    }

    /**
     * @inheritDoc
     */
    public function defineGeneralSchema(): array
    {
        $addressProviderOptions = [];
        $addressProviders = Formie::$plugin->getIntegrations()->getAllIntegrationsForType(Integration::TYPE_ADDRESS_PROVIDER);

        foreach ($addressProviders as $addressProvider) {
            if ($addressProvider->enabled) {
                $addressProviderOptions[] = [ 'label' => $addressProvider->getName(), 'value' => $addressProvider->getHandle() ];
            }
        }

        $fields = [
            SchemaHelper::labelField(),
        ];

        if ($addressProviderOptions) {
            $fields[] = SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Enable Auto-Complete'),
                'help' => Craft::t('formie', 'Whether this field should use auto-complete integration. This will render a single field to enter an address.'),
                'name' => 'enableAutocomplete',
            ]);

            $fields[] = SchemaHelper::toggleContainer('settings.enableAutocomplete', [
                SchemaHelper::selectField([
                    'label' => Craft::t('formie', 'Auto-Complete Integration'),
                    'help' => Craft::t('formie', 'Select which address provider this field should use.'),
                    'name' => 'autocompleteIntegration',
                    'validation' => 'requiredIf:enableAutocomplete',
                    'required' => true,
                    'options' => array_merge(
                        [[ 'label' => Craft::t('formie', 'Select an option'), 'value' => '' ]],
                        $addressProviderOptions
                    ),
                ]),
            ]);
        }

        $toggleBlocks = [
            SchemaHelper::toggleContainer('settings.enableAutocomplete', [
                SchemaHelper::toggleBlock([
                    'blockLabel' => Craft::t('formie', 'Auto-Complete'),
                    'blockHandle' => 'autocomplete',
                ], [
                    SchemaHelper::textField([
                        'label' => Craft::t('formie', 'Label'),
                        'help' => Craft::t('formie', 'The label that describes this field.'),
                        'name' => 'autocompleteLabel',
                        'validation' => 'requiredIf:enableAutocomplete',
                        'required' => true,
                    ]),
                    SchemaHelper::textField([
                        'label' => Craft::t('formie', 'Placeholder'),
                        'help' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                        'name' => 'autocompletePlaceholder',
                    ]),
                    SchemaHelper::textField([
                        'label' => Craft::t('formie', 'Default Value'),
                        'help' => Craft::t('formie', 'Entering a default value will place the value in the field when it loads.'),
                        'name' => 'autocompleteDefaultValue',
                    ]),
                ]),
            ]),
        ];

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
            ], $subfields);
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
        $fields = [];

        $fields = [
            SchemaHelper::toggleContainer('settings.enableAutocomplete', [
                SchemaHelper::toggleBlock([
                    'blockLabel' => Craft::t('formie', 'Auto-Complete'),
                    'blockHandle' => 'autocomplete',
                    'showToggle' => false,
                    'showEnabled' => false,
                    'showOnlyIfEnabled' => true,
                ], [
                    SchemaHelper::lightswitchField([
                        'label' => Craft::t('formie', 'Required Field'),
                        'help' => Craft::t('formie', 'Whether this field should be required when filling out the form.'),
                        'name' => 'autocompleteRequired',
                    ]),
                    SchemaHelper::toggleContainer('settings.autocompleteRequired', [
                        SchemaHelper::textField([
                            'label' => Craft::t('formie', 'Error Message'),
                            'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                            'name' => 'autocompleteErrorMessage',
                        ]),
                    ]),
                ]),
            ]),
        ];

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
            ];

            $fields[] = SchemaHelper::toggleBlock([
                'blockLabel' => $nestedField['label'],
                'blockHandle' => $nestedField['handle'],
                'showToggle' => false,
                'showEnabled' => false,
                'showOnlyIfEnabled' => true,
            ], $subfields);
        }

        return $fields;
    }

    /**
     * @inheritDoc
     */
    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::labelPosition($this),
            SchemaHelper::subfieldLabelPosition(),
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
}
