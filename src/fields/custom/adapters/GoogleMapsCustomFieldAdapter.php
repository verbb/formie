<?php
namespace verbb\formie\fields\custom\adapters;

use verbb\formie\elements\Form;
use verbb\formie\fields\CustomField;
use verbb\formie\fields\custom\AbstractCustomFieldAdapter;
use verbb\formie\fields\values\CustomGoogleMapsAddressFieldValue;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\ClientModule;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\gql\GqlEntityRegistry;

use doublesecretagency\googlemaps\enums\Defaults as GoogleMapsDefaults;
use doublesecretagency\googlemaps\fields\AddressField as CraftGoogleMapsAddressField;
use doublesecretagency\googlemaps\helpers\GoogleMaps;
use doublesecretagency\googlemaps\models\Address as CraftGoogleMapsAddressValue;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class GoogleMapsCustomFieldAdapter extends AbstractCustomFieldAdapter
{
    // Static Methods
    // =========================================================================

    public static function handle(): string
    {
        return 'googleMaps';
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'Address (Google Maps)');
    }

    public static function craftFieldClasses(): array
    {
        return [
            'doublesecretagency\\googlemaps\\fields\\AddressField',
            'doublesecretagency\\googlemaps\\fields\\Map',
            'doublesecretagency\\googlemaps\\fields\\GoogleMap',
            'Doublesecretagency\\GoogleMaps\\fields\\AddressField',
            'Doublesecretagency\\GoogleMaps\\fields\\Map',
        ];
    }


    // Public Methods
    // =========================================================================

    public function getFieldTypeDefinition(): array
    {
        return array_merge(parent::getFieldTypeDefinition(), [
            'valueShape' => 'map',
        ]);
    }

    public function getFormBuilderSettingsSchema(CustomField $field): array
    {
        return [
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder'),
                'instructions' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                'name' => $this->settingName('placeholder'),
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Default Address'),
                'instructions' => Craft::t('formie', 'Set a default address or location label for the field.'),
                'name' => $this->settingName('defaultValue'),
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Default Latitude'),
                'name' => $this->settingName('defaultLat'),
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Default Longitude'),
                'name' => $this->settingName('defaultLng'),
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Default Zoom'),
                'name' => $this->settingName('defaultZoom'),
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Hide Search'),
                'instructions' => Craft::t('formie', 'Hide the location search field.'),
                'name' => $this->settingName('hideSearch'),
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Hide Map'),
                'instructions' => Craft::t('formie', 'Hide the map.'),
                'name' => $this->settingName('hideMap'),
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Hide Address'),
                'instructions' => Craft::t('formie', 'Hide the address field.'),
                'name' => $this->settingName('hideAddress'),
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Show Latitude / Longitude'),
                'instructions' => Craft::t('formie', 'Show the latitude and longitude fields.'),
                'name' => $this->settingName('showLatLng'),
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Show Current Location'),
                'instructions' => Craft::t('formie', 'Show a button to centre the map on the user’s current location.'),
                'name' => $this->settingName('showCurrentLocation'),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Field Size'),
                'instructions' => Craft::t('formie', 'Choose the size of the field to display.'),
                'name' => $this->settingName('fieldSize'),
                'options' => [
                    ['label' => Craft::t('formie', 'Normal'), 'value' => 'normal'],
                    ['label' => Craft::t('formie', 'Small'), 'value' => 'small'],
                    ['label' => Craft::t('formie', 'Large'), 'value' => 'large'],
                ],
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Preferred Country'),
                'instructions' => Craft::t('formie', 'Prioritise search results from this country. Use an ISO country code such as “au” or leave blank for all countries.'),
                'name' => $this->settingName('preferredCountry'),
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Min Zoom'),
                'instructions' => Craft::t('formie', 'The minimum level the user can zoom the map out to.'),
                'name' => $this->settingName('minZoom'),
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Max Zoom'),
                'instructions' => Craft::t('formie', 'The maximum level the user can zoom the map in to.'),
                'name' => $this->settingName('maxZoom'),
            ]),
        ];
    }

    public function getFormBuilderPreviewSchema(CustomField $field): array
    {
        return [
            SchemaHelper::previewInput([
                'placeholder' => SchemaHelper::previewBind('field.customFieldAdapterSettings.placeholder', Craft::t('formie', 'Search for a location')),
                'value' => SchemaHelper::previewBind('field.customFieldAdapterSettings.defaultValue', ''),
            ]),
        ];
    }

    public function getContentGqlType(CustomField $field): Type|array
    {
        return [
            'formatted' => Type::string(),
            'name' => Type::string(),
            'street1' => Type::string(),
            'street2' => Type::string(),
            'city' => Type::string(),
            'state' => Type::string(),
            'zip' => Type::string(),
            'neighborhood' => Type::string(),
            'county' => Type::string(),
            'country' => Type::string(),
            'countryCode' => Type::string(),
            'placeId' => Type::string(),
            'lat' => Type::float(),
            'lng' => Type::float(),
            'zoom' => Type::int(),
        ];
    }

    public function getContentGqlMutationArgumentType(CustomField $field): Type|array
    {
        $typeName = 'FormieCustomGoogleMapFieldInput';

        return [
            'name' => $field->handle,
            'type' => GqlEntityRegistry::getOrCreate($typeName, fn() => new InputObjectType([
                'name' => $typeName,
                'fields' => [
                    'formatted' => Type::string(),
                    'raw' => Type::string(),
                    'name' => Type::string(),
                    'street1' => Type::string(),
                    'street2' => Type::string(),
                    'city' => Type::string(),
                    'state' => Type::string(),
                    'zip' => Type::string(),
                    'neighborhood' => Type::string(),
                    'county' => Type::string(),
                    'country' => Type::string(),
                    'countryCode' => Type::string(),
                    'placeId' => Type::string(),
                    'lat' => Type::float(),
                    'lng' => Type::float(),
                    'zoom' => Type::int(),
                ],
            ])),
            'description' => $field->instructions->isEmpty() ? null : $field->instructions->toPlainText(),
        ];
    }

    public function getClientInput(CustomField $field): array
    {
        return [
            'inputType' => 'map',
            'provider' => static::handle(),
            'placeholder' => $this->getPlaceholder($field) ?: null,
            'mapSettings' => $this->getMapClientSettings($field),
        ];
    }

    public function getClientModules(CustomField $field): array
    {
        return [
            new ClientModule([
                'id' => 'custom-google-maps',
                'renderTargets' => [ClientModule::RENDER_TARGET_FRONTEND],
            ]),
        ];
    }

    public function getValueClass(CustomField $field): ?string
    {
        return CustomGoogleMapsAddressFieldValue::class;
    }

    public function normalizeValue(mixed $value, CustomField $field, ?ElementInterface $element): mixed
    {
        $value = parent::normalizeValue($value, $field, $element);

        if ($value instanceof CustomGoogleMapsAddressFieldValue) {
            return $value;
        }

        if ($value instanceof CraftGoogleMapsAddressValue) {
            return CustomGoogleMapsAddressFieldValue::fromGoogleMapsAddress($value);
        }

        if (is_array($value)) {
            if (isset($value['address']) && !isset($value['formatted'])) {
                $value['formatted'] = $value['address'];
            }

            return new CustomGoogleMapsAddressFieldValue($value);
        }

        return new CustomGoogleMapsAddressFieldValue([
            'formatted' => trim((string)$value) ?: $this->getDefaultAddress($field),
            'lat' => $this->getNullableFloatSetting($field, 'defaultLat'),
            'lng' => $this->getNullableFloatSetting($field, 'defaultLng'),
            'zoom' => $this->getNullableIntSetting($field, 'defaultZoom'),
        ]);
    }

    public function serializeValue(mixed $value, CustomField $field, ?ElementInterface $element): mixed
    {
        $value = $this->normalizeValue($value, $field, $element);

        return $value instanceof CustomGoogleMapsAddressFieldValue ? $value->toValueArray() : $value;
    }

    public function isValueEmpty(mixed $value, CustomField $field, ?ElementInterface $element): bool
    {
        $value = $this->normalizeValue($value, $field, $element);

        return $value instanceof CustomGoogleMapsAddressFieldValue ? $value->isEmpty() : parent::isValueEmpty($value, $field, $element);
    }

    public function getInputHtml(CustomField $field, Form $form, mixed $value): string
    {
        $value = $this->normalizeValue($value, $field, $form->getCurrentSubmission());

        return $this->renderInputs(
            field: $field,
            value: $value,
            name: $field->getHtmlName(),
            searchName: $field->getHtmlName('formatted'),
            formattedName: $field->getHtmlName('formatted'),
            rawName: $field->getHtmlName('raw'),
            latName: $field->getHtmlName('lat'),
            lngName: $field->getHtmlName('lng'),
            zoomName: $field->getHtmlName('zoom'),
            handlePrefix: $field->getHtmlName(),
            id: $field->getHtmlId($form),
            dataId: $field->getHtmlDataId($form),
        );
    }

    public function getCpInputHtml(CustomField $field, mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $value = $this->normalizeValue($value, $field, $element);

        return $this->createCraftGoogleMapsAddressField($field)->getInputHtml($this->createCraftGoogleMapsAddressValue($value), $element);
    }

    public function getValueAsString(mixed $value, CustomField $field, ?ElementInterface $element = null): string
    {
        $value = $this->normalizeValue($value, $field, $element);

        return $value instanceof CustomGoogleMapsAddressFieldValue ? (string)$value : parent::getValueAsString($value, $field, $element);
    }

    public function getValueAsArray(mixed $value, CustomField $field, ?ElementInterface $element = null): mixed
    {
        $value = $this->normalizeValue($value, $field, $element);

        return $value instanceof CustomGoogleMapsAddressFieldValue ? $value->toValueArray() : parent::getValueAsArray($value, $field, $element);
    }


    // Protected Methods
    // =========================================================================

    protected function renderInputs(CustomField $field, CustomGoogleMapsAddressFieldValue $value, string $name, string $searchName, string $formattedName, string $rawName, string $latName, string $lngName, string $zoomName, string $handlePrefix, string $id, string $dataId): string
    {
        $provider = static::handle();
        $placeholder = $this->getPlaceholder($field) ?: Craft::t('formie', 'Search for a location');
        $hideMap = $this->getBooleanSetting($field, 'hideMap');
        $hideSearch = $this->getBooleanSetting($field, 'hideSearch');
        $hideAddress = $this->getBooleanSetting($field, 'hideAddress');
        $showLatLng = $this->getBooleanSetting($field, 'showLatLng');
        $subfieldInputs = $hideAddress ? '' : $this->renderSubfieldInputs($value, $handlePrefix);
        $searchInput = $hideSearch ? '' : Html::textInput($hideAddress ? $formattedName : null, $value->formatted, [
            'id' => $id . '-search',
            'placeholder' => $placeholder,
            'required' => $field->required,
            'class' => ['formie-input', 'formie-custom-google-maps-search-input'],
            'data-formie-input' => true,
            'data-formie-custom-field-input' => true,
            'data-formie-custom-google-maps-search' => true,
            'data-formie-custom-google-maps-field' => 'formatted',
            'data-formie-input-id' => $dataId,
            'data-formie-input-type' => $provider,
        ]);
        $latInput = $showLatLng ? Html::textInput($latName, $value->lat, [
            'placeholder' => Craft::t('formie', 'Latitude'),
            'class' => ['formie-input'],
            'data-formie-custom-map-lat' => true,
            'data-formie-custom-google-maps-field' => 'lat',
        ]) : Html::hiddenInput($latName, $value->lat, [
            'data-formie-custom-map-lat' => true,
            'data-formie-custom-google-maps-field' => 'lat',
        ]);
        $lngInput = $showLatLng ? Html::textInput($lngName, $value->lng, [
            'placeholder' => Craft::t('formie', 'Longitude'),
            'class' => ['formie-input'],
            'data-formie-custom-map-lng' => true,
            'data-formie-custom-google-maps-field' => 'lng',
        ]) : Html::hiddenInput($lngName, $value->lng, [
            'data-formie-custom-map-lng' => true,
            'data-formie-custom-google-maps-field' => 'lng',
        ]);
        $map = $hideMap ? '' : Html::tag('div', '', [
            'class' => ['formie-custom-google-maps-canvas'],
            'style' => 'height: 240px; width: 100%; margin-top: 0.75rem;',
            'data-formie-custom-google-maps-canvas' => true,
        ]);
        $currentLocationButton = $this->getBooleanSetting($field, 'showCurrentLocation') ? Html::button(Craft::t('formie', 'Use my location'), [
            'type' => 'button',
            'class' => ['formie-button', 'formie-custom-google-maps-current-location'],
            'data-formie-custom-google-maps-current-location' => true,
        ]) : '';

        // Keep the submitted shape aligned with the Google Maps Address field so
        // CP rendering and integrations can reuse the plugin's existing model.
        return Html::tag('div',
            $searchInput .
            $subfieldInputs .
            $currentLocationButton .
            $map .
            $latInput .
            $lngInput .
            Html::hiddenInput($zoomName, $value->zoom, [
                'data-formie-custom-map-zoom' => true,
                'data-formie-custom-google-maps-field' => 'zoom',
            ]) .
            Html::hiddenInput($formattedName, $value->formatted, ['data-formie-custom-google-maps-field' => 'formatted']) .
            Html::hiddenInput($rawName, is_array($value->raw) ? Json::encode($value->raw) : $value->raw, ['data-formie-custom-google-maps-field' => 'raw']) .
            Html::hiddenInput($handlePrefix . '[name]', $value->name, ['data-formie-custom-google-maps-field' => 'name']) .
            Html::hiddenInput($handlePrefix . '[placeId]', $value->placeId, ['data-formie-custom-google-maps-field' => 'placeId']) .
            Html::hiddenInput($handlePrefix . '[countryCode]', $value->countryCode, ['data-formie-custom-google-maps-field' => 'countryCode']) .
            Html::hiddenInput($handlePrefix . '[neighborhood]', $value->neighborhood, ['data-formie-custom-google-maps-field' => 'neighborhood']) .
            Html::hiddenInput($handlePrefix . '[county]', $value->county, ['data-formie-custom-google-maps-field' => 'county']),
            [
                'data-formie-custom-map' => true,
                'data-formie-custom-google-maps' => true,
                'data-formie-custom-map-provider' => $provider,
                'data-formie-custom-map-name' => $name,
                'data-formie-custom-map-hide-search' => $hideSearch ? '1' : '0',
                'data-formie-custom-map-hide-map' => $hideMap ? '1' : '0',
                'data-formie-custom-map-show-current-location' => $this->getBooleanSetting($field, 'showCurrentLocation') ? '1' : '0',
                'data-formie-custom-map-field-size' => $this->getStringSetting($field, 'fieldSize', 'normal'),
                'data-formie-custom-map-preferred-country' => $this->getStringSetting($field, 'preferredCountry'),
                'data-formie-custom-map-min-zoom' => $this->getStringSetting($field, 'minZoom'),
                'data-formie-custom-map-max-zoom' => $this->getStringSetting($field, 'maxZoom'),
                'data-formie-custom-google-maps-settings' => Json::encode($this->getGoogleMapsClientSettings($field)),
                'class' => ['formie-custom-google-maps'],
            ]
        );
    }

    protected function renderSubfieldInputs(CustomGoogleMapsAddressFieldValue $value, string $handlePrefix): string
    {
        $html = '';

        foreach (GoogleMapsDefaults::SUBFIELDCONFIG as $subfield) {
            if (!($subfield['enabled'] ?? false)) {
                continue;
            }

            $handle = (string)$subfield['handle'];

            if (in_array($handle, ['countryCode', 'placeId', 'neighborhood', 'county'], true)) {
                continue;
            }

            $html .= Html::textInput($handlePrefix . '[' . $handle . ']', $value->{$handle} ?? null, [
                'placeholder' => Craft::t('google-maps', (string)($subfield['label'] ?? $handle)),
                'class' => ['formie-input', 'formie-custom-google-maps-subfield'],
                'style' => 'width: 100%;',
                'autocomplete' => 'chrome-off',
                'data-formie-custom-google-maps-field' => $handle,
            ]);
        }

        return $html;
    }

    protected function getPlaceholder(CustomField $field): string
    {
        return trim((string)$this->getSetting($field, 'placeholder', ''));
    }

    protected function getDefaultAddress(CustomField $field): string
    {
        return trim((string)$this->getSetting($field, 'defaultValue', ''));
    }

    protected function getMapClientSettings(CustomField $field): array
    {
        return [
            'hideSearch' => $this->getBooleanSetting($field, 'hideSearch'),
            'hideMap' => $this->getBooleanSetting($field, 'hideMap'),
            'hideAddress' => $this->getBooleanSetting($field, 'hideAddress'),
            'showLatLng' => $this->getBooleanSetting($field, 'showLatLng'),
            'showCurrentLocation' => $this->getBooleanSetting($field, 'showCurrentLocation'),
            'fieldSize' => $this->getStringSetting($field, 'fieldSize', 'normal'),
            'preferredCountry' => $this->getStringSetting($field, 'preferredCountry'),
            'minZoom' => $this->getNullableIntSetting($field, 'minZoom'),
            'maxZoom' => $this->getNullableIntSetting($field, 'maxZoom'),
            'defaultLat' => $this->getNullableFloatSetting($field, 'defaultLat'),
            'defaultLng' => $this->getNullableFloatSetting($field, 'defaultLng'),
            'defaultZoom' => $this->getNullableIntSetting($field, 'defaultZoom'),
        ];
    }

    protected function getGoogleMapsClientSettings(CustomField $field): array
    {
        $fieldParams = [];

        if (class_exists(GoogleMaps::class)) {
            $fieldParams = [
                'apiUrl' => GoogleMaps::getApiUrl([
                    'loading' => 'async',
                    'libraries' => 'places',
                ]),
            ];
        }

        return array_merge($fieldParams, [
            'defaultLat' => $this->getNullableFloatSetting($field, 'defaultLat'),
            'defaultLng' => $this->getNullableFloatSetting($field, 'defaultLng'),
            'defaultZoom' => $this->getNullableIntSetting($field, 'defaultZoom') ?? 11,
            'minZoom' => $this->getNullableIntSetting($field, 'minZoom'),
            'maxZoom' => $this->getNullableIntSetting($field, 'maxZoom'),
            'country' => $this->getStringSetting($field, 'preferredCountry'),
        ]);
    }

    protected function createCraftGoogleMapsAddressField(CustomField $field): CraftGoogleMapsAddressField
    {
        $defaultLat = $this->getNullableFloatSetting($field, 'defaultLat') ?? GoogleMapsDefaults::COORDINATES['lat'];
        $defaultLng = $this->getNullableFloatSetting($field, 'defaultLng') ?? GoogleMapsDefaults::COORDINATES['lng'];
        $defaultZoom = $this->getNullableIntSetting($field, 'defaultZoom') ?? GoogleMapsDefaults::COORDINATES['zoom'];

        $addressField = new CraftGoogleMapsAddressField();
        $addressField->id = $field->id;
        $addressField->handle = $field->handle;
        $addressField->name = $field->label;
        $addressField->required = $field->required;
        $addressField->showMap = !$this->getBooleanSetting($field, 'hideMap');
        $addressField->mapOnStart = $this->getBooleanSetting($field, 'hideMap') ? 'close' : 'open';
        $addressField->mapOnSearch = 'open';
        $addressField->visibilityToggle = 'both';
        $addressField->coordinatesMode = $this->getBooleanSetting($field, 'showLatLng') ? 'editable' : 'hidden';
        $addressField->requireCoordinates = $field->required;
        $addressField->coordinatesDefault = [
            'lat' => $defaultLat,
            'lng' => $defaultLng,
            'zoom' => $defaultZoom,
        ];
        $addressField->subfieldConfig = GoogleMapsDefaults::SUBFIELDCONFIG;

        return $addressField;
    }

    protected function createCraftGoogleMapsAddressValue(CustomGoogleMapsAddressFieldValue $value): CraftGoogleMapsAddressValue
    {
        return new CraftGoogleMapsAddressValue(array_merge($value->toValueArray(), [
            'enabledSubfields' => array_column(GoogleMapsDefaults::SUBFIELDCONFIG, 'handle'),
        ]));
    }

    protected function getBooleanSetting(CustomField $field, string $name): bool
    {
        return (bool)$this->getSetting($field, $name, false);
    }

    protected function getStringSetting(CustomField $field, string $name, string $default = ''): string
    {
        return trim((string)$this->getSetting($field, $name, $default));
    }

    protected function getNullableFloatSetting(CustomField $field, string $name): ?float
    {
        $value = $this->getSetting($field, $name);

        return $value !== null && $value !== '' ? (float)$value : null;
    }

    protected function getNullableIntSetting(CustomField $field, string $name): ?int
    {
        $value = $this->getSetting($field, $name);

        return $value !== null && $value !== '' ? (int)$value : null;
    }

    protected function getSourceLabel(): ?string
    {
        return Craft::t('formie', 'Google Maps plugin');
    }

    protected function getSvgIcon(): ?string
    {
        return '<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="map-marker-alt" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M192 0C86 0 0 86 0 192c0 77.4 27 99 172.3 309.7 9.5 13.8 29.9 13.8 39.5 0C357 291 384 269.4 384 192 384 86 298 0 192 0zm0 464C52.7 262.6 48 256.9 48 192c0-79.5 64.5-144 144-144s144 64.5 144 144c0 64.6-4.4 70.1-144 272zm0-352c-44.2 0-80 35.8-80 80s35.8 80 80 80 80-35.8 80-80-35.8-80-80-80zm0 112c-17.6 0-32-14.4-32-32s14.4-32 32-32 32 14.4 32 32-14.4 32-32 32z"></path></svg>';
    }
}
