<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\AddressProvider;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\FixedParentFieldInterface;
use verbb\formie\base\FixedParentField;
use verbb\formie\base\PreviewableFieldInterface;
use verbb\formie\fields\definitions\FieldClientModules;
use verbb\formie\fields\definitions\FieldReferenceValue;
use verbb\formie\fields\definitions\FieldValueClass;
use verbb\formie\gql\types\AddressType;
use verbb\formie\gql\types\generators\FieldAttributeGenerator;
use verbb\formie\gql\types\input\AddressInputType;
use verbb\formie\fields\values\AddressFieldValue;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\Variables;
use verbb\formie\integrations\addressproviders\Google;
use verbb\formie\models\ClientModuleContext;
use verbb\formie\models\SlotTag;
use verbb\formie\positions\AboveInput;
use verbb\formie\positions\Hidden as HiddenPosition;

use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;
use craft\errors\InvalidFieldException;
use craft\db\Query;
use craft\helpers\Component;
use craft\helpers\Json;

use Faker\Generator as FakerFactory;

use GraphQL\Type\Definition\Type;

use yii\base\Event;
use yii\db\Schema;

class Address extends FixedParentField implements PreviewableFieldInterface
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

    public static function supportsGqlConfigProvider(): bool
    {
        return true;
    }

    public static function gqlContentTypeFromConfig(array $config): Type|array
    {
        return AddressType::getType();
    }

    public static function gqlContentMutationArgumentTypeFromConfig(array $config): Type|array
    {
        return AddressInputType::getTypeFromConfig($config);
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

    public function fieldKind(): string
    {
        return self::KIND_ADDRESS;
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        $value = parent::normalizeValue($value, $element);
        $value = Json::decodeIfJson($value);

        if ($value instanceof AddressFieldValue) {
            return $value->isEmpty() ? null : $value;
        }

        if (is_array($value)) {
            $address = new AddressFieldValue($value);

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

            return $address->isEmpty() ? null : $address;
        }

        return null;
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewContainerParent(),
        ];
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

    public function createLocationLinkSlotTag(RenderContext $context): SlotTag
    {
        // "Use my location" control for the auto-complete sub-field label (not resolved on the parent Address field itself).
        return SlotTag::make('a')
            ->core([
                'href' => 'javascript:;',
                'text' => Craft::t('formie', 'Use my location'),
                'data-formie-address-location' => true,
            ])
            ->theme([
                'class' => [
                    'formie-address-location',
                ],
            ]);
    }

    public function getContentGqlType(): Type|array
    {
        return AddressType::getType();
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::nestedFieldsConfigurationField([
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
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::includeInEmailFieldSummariesField(),
        ];
    }

    public function defineFormBuilderAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::subFieldLabelPosition(),
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
        return AddressInputType::getType($this);
    }


    // Protected Methods
    // =========================================================================

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;

        $id = $this->getHtmlId($form);
        
        if ($key === 'fieldLayout') {
            return SlotTag::make('fieldset')
                ->core([
                    'data-formie-field-layout' => true,
                    'data-formie-address-field-layout' => true,
                    'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
                ])
                ->theme([
                    'class' => [
                        'formie-field-layout',
                        'formie-address-field-layout',
                    ],
                ]);
        }

        if ($key === 'fieldLabel') {
            $labelPosition = $context->get('labelPosition');

            return SlotTag::make('legend')
                ->core([
                    'data-formie-label' => true,
                    'data-formie-field-label' => true,
                    'data-formie-address-field-label' => true,
                    'data-formie-sr-only' => $labelPosition instanceof HiddenPosition ? true : false,
                ])
                ->theme([
                    'class' => [
                        'formie-label',
                        'formie-field-label',
                        'formie-address-field-label',
                        $labelPosition instanceof HiddenPosition ? 'formie-sr-only' : false,
                    ],
                ]);
        }

        return parent::defineFieldSlotTag($key, $context);
    }

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

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
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
        return new AddressFieldValue([
            'address1' => $faker->streetAddress,
            'address2' => $faker->buildingNumber,
            'address3' => $faker->streetSuffix,
            'city' => $faker->city,
            'zip' => $faker->postcode,
            'state' => $faker->state,
            'country' => AddressFieldValue::nameToCode($faker->country),
        ]);
    }

    protected function defineReferenceValues(): array
    {
        return [
            FieldReferenceValue::default([
                'handle' => '__toString',
                'label' => Craft::t('formie', 'Formatted Address'),
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'address1',
                'label' => Craft::t('formie', 'Address 1'),
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'address2',
                'label' => Craft::t('formie', 'Address 2'),
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'address3',
                'label' => Craft::t('formie', 'Address 3'),
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'city',
                'label' => Craft::t('formie', 'City'),
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'state',
                'label' => Craft::t('formie', 'State / Province'),
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'zip',
                'label' => Craft::t('formie', 'ZIP / Postal Code'),
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'country',
                'label' => Craft::t('formie', 'Country'),
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
        ];
    }

    protected function defineClientModules(): array
    {
        $modules = parent::defineClientModules();
        $modules[] = function(ClientModuleContext $context) {
            $integration = $this->getAddressProviderIntegration();

            if (!$integration) {
                return null;
            }

            $clientModule = $integration->getClientModule(new ClientModuleContext([
                'form' => $context->form,
                'field' => $this,
                'integration' => $integration,
                'renderTarget' => $context->renderTarget,
            ]));

            if (!$clientModule?->id) {
                return null;
            }

            if (!$clientModule->type) {
                $clientModule->type = 'address';
            }

            if (!$clientModule->targets) {
                $clientModule->targets = $context->getTargets();
            }

            if ($integration instanceof Google) {
                $autoComplete = $this->getFieldByHandle('autoComplete');
                $countryDefaultValue = $autoComplete->countryDefaultValue ?? null;

                if ($countryDefaultValue) {
                    $clientModule->config['countryDefaultValue'] = $countryDefaultValue;
                }
            }

            return $clientModule;
        };

        return $modules;
    }

    protected function defineValueClass(): ?string
    {
        return AddressFieldValue::class;
    }


    // Private Methods
    // =========================================================================

    private function _getAddressProviderOptions(): array
    {
        $addressProviderOptions = [];
        $integrationRows = (new Query())
            ->select(['handle', 'name', 'type', 'enabled'])
            ->from(Table::FORMIE_INTEGRATIONS)
            ->where(['enabled' => true])
            ->all();

        foreach ($integrationRows as $integrationRow) {
            $integrationType = $integrationRow['type'] ?? null;
            $handle = $integrationRow['handle'] ?? null;
            $name = $integrationRow['name'] ?? null;

            if (!$integrationType || !is_subclass_of($integrationType, AddressProvider::class)) {
                continue;
            }

            if (!$handle || !$name) {
                continue;
            }

            $addressProviderOptions[] = ['label' => $name, 'value' => $handle];
        }

        return $addressProviderOptions;
    }

}
