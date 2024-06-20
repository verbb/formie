<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\SubFieldInterface;
use verbb\formie\base\SubField;
use verbb\formie\gql\types\AddressType;
use verbb\formie\gql\types\generators\FieldAttributeGenerator;
use verbb\formie\gql\types\input\AddressInputType;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\Address as AddressModel;
use verbb\formie\models\HtmlTag;
use verbb\formie\positions\AboveInput;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\errors\InvalidFieldException;
use craft\helpers\Component;
use craft\helpers\Json;

use Faker\Generator as FakerFactory;

use GraphQL\Type\Definition\Type;

use yii\base\Event;
use yii\db\Schema;

class Address extends SubField implements PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Address');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/address/icon.svg';
    }

    public static function dbType(): string
    {
        return Schema::TYPE_JSON;
    }


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        unset(
            $config['autocompleteIntegration'],
            $config['autocompleteEnabled'],
            $config['autocompleteCollapsed'],
            $config['autocompleteLabel'],
            $config['autocompletePlaceholder'],
            $config['autocompleteDefaultValue'],
            $config['autocompletePrePopulate'],
            $config['autocompleteRequired'],
            $config['autocompleteErrorMessage'],
            $config['autocompleteCurrentLocation'],

            $config['address1Enabled'],
            $config['address1Collapsed'],
            $config['address1Label'],
            $config['address1Placeholder'],
            $config['address1DefaultValue'],
            $config['address1PrePopulate'],
            $config['address1Required'],
            $config['address1ErrorMessage'],
            $config['address1Hidden'],

            $config['address2Enabled'],
            $config['address2Collapsed'],
            $config['address2Label'],
            $config['address2Placeholder'],
            $config['address2DefaultValue'],
            $config['address2PrePopulate'],
            $config['address2Required'],
            $config['address2ErrorMessage'],
            $config['address2Hidden'],

            $config['address3Enabled'],
            $config['address3Collapsed'],
            $config['address3Label'],
            $config['address3Placeholder'],
            $config['address3DefaultValue'],
            $config['address3PrePopulate'],
            $config['address3Required'],
            $config['address3ErrorMessage'],
            $config['address3Hidden'],

            $config['cityEnabled'],
            $config['cityCollapsed'],
            $config['cityLabel'],
            $config['cityPlaceholder'],
            $config['cityDefaultValue'],
            $config['cityPrePopulate'],
            $config['cityRequired'],
            $config['cityErrorMessage'],
            $config['cityHidden'],

            $config['stateEnabled'],
            $config['stateCollapsed'],
            $config['stateLabel'],
            $config['statePlaceholder'],
            $config['stateDefaultValue'],
            $config['statePrePopulate'],
            $config['stateRequired'],
            $config['stateErrorMessage'],
            $config['stateHidden'],

            $config['zipEnabled'],
            $config['zipCollapsed'],
            $config['zipLabel'],
            $config['zipPlaceholder'],
            $config['zipDefaultValue'],
            $config['zipPrePopulate'],
            $config['zipRequired'],
            $config['zipErrorMessage'],
            $config['zipHidden'],

            $config['countryEnabled'],
            $config['countryCollapsed'],
            $config['countryLabel'],
            $config['countryPlaceholder'],
            $config['countryDefaultValue'],
            $config['countryPrePopulate'],
            $config['countryRequired'],
            $config['countryErrorMessage'],
            $config['countryHidden'],
            $config['countryOptionLabel'],
            $config['countryOptionValue'],
        );

        $config['instructionsPosition'] = $config['instructionsPosition'] ?? AboveInput::class;

        parent::__construct($config);
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        $value = parent::normalizeValue($value, $element);
        $value = Json::decodeIfJson($value);

        if ($value instanceof AddressModel) {
            return $value;
        }

        if (is_array($value)) {
            $address = new AddressModel($value);

            // Normalize country to null, due to it being a dropdown
            if ($address->country === '') {
                $address->country = null;
            }

            // Reset any disabled fields that might have content to null
            foreach ($this->getFields() as $field) {
                if ($field->getIsDisabled() && property_exists($address, $field->handle)) {
                    $address->{$field->handle} = null;
                }
            }

            return $address;
        }

        return new AddressModel();
    }

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/address/preview', [
            'field' => $this,
        ]);
    }

    public function getAutoCompleteHtml(array $renderOptions = []): string
    {
        $integration = $this->getAddressProviderIntegration();

        if (!$integration) {
            return '';
        }

        return $integration->getFrontEndHtml($this, $renderOptions);
    }

    public function getFrontEndJsModules(): ?array
    {
        $integration = $this->getAddressProviderIntegration();

        if (!$integration) {
            return null;
        }

        return $integration?->getFrontEndJsVariables($this);
    }

    public function getAddressProviderIntegration(): ?IntegrationInterface
    {
        $autoComplete = $this->getFieldByHandle('autoComplete');

        if (!$autoComplete || !$autoComplete->enabled || !$autoComplete->integrationHandle) {
            return null;
        }

        return Formie::$plugin->getIntegrations()->getIntegrationByHandle($autoComplete->integrationHandle);
    }

    public function supportsCurrentLocation(): bool
    {
        $integration = $this->getAddressProviderIntegration();

        return $integration && $integration::supportsCurrentLocation();
    }

    public function hasCurrentLocation(): bool
    {
        $autoCompleteCurrentLocation = $this->getFieldByHandle('autoComplete')?->currentLocation ?? false;

        return $this->supportsCurrentLocation() && $autoCompleteCurrentLocation;
    }

    public function getContentGqlType(): Type|array
    {
        return AddressType::getType();
    }

    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::subFieldsConfigurationField([], [
                'type' => static::class,
            ]),
        ];
    }

    public function defineSettingsSchema(): array
    {
        return [
            SchemaHelper::includeInEmailField(),
        ];
    }

    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::subFieldLabelPosition(),
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
        return AddressInputType::getType($this);
    }

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        $form = $context['form'] ?? null;

        $id = $this->getHtmlId($form);
        
        if ($key === 'fieldContainer') {
            return new HtmlTag('fieldset', [
                'class' => 'fui-fieldset',
                'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
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

        if ($key === 'locationLink') {
            return new HtmlTag('a', [
                'href' => 'javascript:;',
                'class' => 'fui-link fui-address-location-link',
                'text' => Craft::t('formie', 'Use my location'),
                'data-fui-address-location-btn' => true,
            ]);
        }

        return parent::defineHtmlTag($key, $context);
    }


    // Protected Methods
    // =========================================================================

    protected function defineSubFields(): array
    {
        $addressProviderOptions = $this->_getAddressProviderOptions();

        $fields = [
            [
                'fields' => [
                    [
                        'type' => subfields\Address1::class,
                        'label' => Craft::t('formie', 'Address 1'),
                        'handle' => 'address1',
                        'labelPosition' => $this->subFieldLabelPosition,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'address-line1',
                            ],
                            [
                                'label' => 'data-address1',
                                'value' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'fields' => [
                    [
                        'type' => subfields\Address2::class,
                        'label' => Craft::t('formie', 'Address 2'),
                        'handle' => 'address2',
                        'enabled' => false,
                        'labelPosition' => $this->subFieldLabelPosition,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'address-line2',
                            ],
                            [
                                'label' => 'data-address2',
                                'value' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'fields' => [
                    [
                        'type' => subfields\Address3::class,
                        'label' => Craft::t('formie', 'Address 3'),
                        'handle' => 'address3',
                        'enabled' => false,
                        'labelPosition' => $this->subFieldLabelPosition,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'address-line3',
                            ],
                            [
                                'label' => 'data-address3',
                                'value' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'fields' => [
                    [
                        'type' => subfields\AddressCity::class,
                        'label' =>  Craft::t('formie', 'City'),
                        'handle' => 'city',
                        'enabled' => true,
                        'labelPosition' => $this->subFieldLabelPosition,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'address-level2',
                            ],
                            [
                                'label' => 'data-city',
                                'value' => true,
                            ],
                        ],
                    ],
                    [
                        'type' => subfields\AddressZip::class,
                        'label' => Craft::t('formie', 'ZIP / Postal Code'),
                        'handle' => 'zip',
                        'enabled' => true,
                        'labelPosition' => $this->subFieldLabelPosition,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'postal-code',
                            ],
                            [
                                'label' => 'data-zip',
                                'value' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'fields' => [
                    [
                        'type' => subfields\AddressState::class,
                        'label' => Craft::t('formie', 'State / Province'),
                        'handle' => 'state',
                        'enabled' => true,
                        'labelPosition' => $this->subFieldLabelPosition,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'address-level1',
                            ],
                            [
                                'label' => 'data-state',
                                'value' => true,
                            ],
                        ],
                    ],
                    [
                        'type' => subfields\AddressCountry::class,
                        'label' => Craft::t('formie', 'Country'),
                        'handle' => 'country',
                        'enabled' => true,
                        'placeholder' => Craft::t('formie', 'Select an option'),
                        'labelPosition' => $this->subFieldLabelPosition,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'country',
                            ],
                            [
                                'label' => 'data-country',
                                'value' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        if ($addressProviderOptions) {
            array_unshift($fields, [
                'fields' => [
                    [
                        'type' => subfields\AddressAutoComplete::class,
                        'label' => Craft::t('formie', 'Auto-Complete'),
                        'handle' => 'autoComplete',
                        'enabled' => false,
                        'labelPosition' => $this->subFieldLabelPosition,
                        'inputAttributes' => [
                            [
                                'label' => 'autocomplete',
                                'value' => 'autocomplete',
                            ],
                            [
                                'label' => 'data-autocomplete',
                                'value' => true,
                            ],
                            [
                                'label' => 'type',
                                'value' => 'search',
                            ],
                            [
                                'label' => 'aria-autocomplete',
                                'value' => 'list',
                            ],
                        ],
                    ],
                ],
            ]);
        }

        return $fields;
    }

    protected function cpInputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/address/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'element' => $element,
        ]);
    }

    protected function defineValueForEmailPreview(FakerFactory $faker): mixed
    {
        return new AddressModel([
            'address1' => $faker->streetAddress,
            'address2' => $faker->buildingNumber,
            'address3' => $faker->streetSuffix,
            'city' => $faker->city,
            'zip' => $faker->postcode,
            'state' => $faker->state,
            'country' => AddressModel::nameToCode($faker->country),
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _getAddressProviderOptions(): array
    {
        $addressProviderOptions = [];
        $addressProviders = Formie::$plugin->getIntegrations()->getAllIntegrationsForType(Integration::TYPE_ADDRESS_PROVIDER);

        foreach ($addressProviders as $addressProvider) {
            if ($addressProvider->getEnabled()) {
                $addressProviderOptions[] = ['label' => $addressProvider->getName(), 'value' => $addressProvider->getHandle()];
            }
        }

        return $addressProviderOptions;
    }
}
