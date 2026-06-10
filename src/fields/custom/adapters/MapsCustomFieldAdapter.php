<?php
namespace verbb\formie\fields\custom\adapters;

use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\fields\CustomField;
use verbb\formie\fields\custom\AbstractCustomFieldAdapter;
use verbb\formie\fields\values\CustomMapFieldValue;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\ClientModule;
use verbb\formie\models\SlotTag;
use verbb\formie\theme\context\RenderContext;
use verbb\formie\web\twig\Extension as FormieTwigExtension;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\gql\GqlEntityRegistry;

use ether\simplemap\enums\MapTiles;
use ether\simplemap\fields\MapField as CraftMapsField;
use ether\simplemap\models\Map as CraftMapsValue;
use ether\simplemap\SimpleMap;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class MapsCustomFieldAdapter extends AbstractCustomFieldAdapter
{
    // Static Methods
    // =========================================================================

    public static function handle(): string
    {
        return 'maps';
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'Maps');
    }

    public static function craftFieldClasses(): array
    {
        return [
            'ether\\simplemap\\fields\\MapField',
            'ether\\simplemap\\fields\\Map',
            'verbb\\maps\\fields\\Map',
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
                    ['label' => Craft::t('formie', 'Mini'), 'value' => 'mini'],
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
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Enable what3words Grid'),
                'instructions' => Craft::t('formie', 'Show the what3words grid overlay when Maps has what3words enabled.'),
                'name' => $this->settingName('showW3WGrid'),
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Show what3words Field'),
                'instructions' => Craft::t('formie', 'Show the what3words value for the selected location when Maps has what3words enabled.'),
                'name' => $this->settingName('showW3WField'),
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
            'address' => Type::string(),
            'lat' => Type::float(),
            'lng' => Type::float(),
            'zoom' => Type::int(),
            'parts' => [
                'number' => Type::string(),
                'address' => Type::string(),
                'city' => Type::string(),
                'postcode' => Type::string(),
                'county' => Type::string(),
                'state' => Type::string(),
                'country' => Type::string(),
            ],
            'what3words' => Type::string(),
        ];
    }

    public function getContentGqlMutationArgumentType(CustomField $field): Type|array
    {
        $typeName = 'FormieCustomMapsFieldInput';

        return [
            'name' => $field->handle,
            'type' => GqlEntityRegistry::getOrCreate($typeName, fn() => new InputObjectType([
                'name' => $typeName,
                'fields' => [
                    'address' => Type::string(),
                    'lat' => Type::float(),
                    'lng' => Type::float(),
                    'zoom' => Type::int(),
                    'parts' => Type::string(),
                    'what3words' => Type::string(),
                ],
            ])),
            'description' => $field->instructions,
        ];
    }

    public function getClientInput(CustomField $field): array
    {
        return [
            'inputType' => 'map',
            'provider' => static::handle(),
            'placeholder' => Craft::t('site', $this->getPlaceholder($field)) ?: null,
            'mapSettings' => $this->getMapClientSettings($field),
        ];
    }

    public function getClientModules(CustomField $field): array
    {
        return [
            new ClientModule([
                'id' => 'custom-maps',
                'renderTargets' => [ClientModule::RENDER_TARGET_FRONTEND],
            ]),
        ];
    }

    public function getValueClass(CustomField $field): ?string
    {
        return CustomMapFieldValue::class;
    }

    public function normalizeValue(mixed $value, CustomField $field, ?ElementInterface $element): mixed
    {
        $value = parent::normalizeValue($value, $field, $element);

        if ($value instanceof CustomMapFieldValue) {
            return $value;
        }

        if (is_array($value)) {
            return new CustomMapFieldValue($value);
        }

        $mapValue = new CustomMapFieldValue();
        $mapValue->address = trim((string)$value) ?: $this->getDefaultAddress($field);
        $mapValue->lat = $this->getNullableFloatSetting($field, 'defaultLat');
        $mapValue->lng = $this->getNullableFloatSetting($field, 'defaultLng');
        $mapValue->zoom = $this->getNullableIntSetting($field, 'defaultZoom');
        $mapValue->parts = null;
        $mapValue->what3words = null;

        return $mapValue;
    }

    public function serializeValue(mixed $value, CustomField $field, ?ElementInterface $element): mixed
    {
        $value = $this->normalizeValue($value, $field, $element);

        return $value instanceof CustomMapFieldValue ? $value->toValueArray() : $value;
    }

    public function isValueEmpty(mixed $value, CustomField $field, ?ElementInterface $element): bool
    {
        $value = $this->normalizeValue($value, $field, $element);

        return $value instanceof CustomMapFieldValue ? $value->isEmpty() : parent::isValueEmpty($value, $field, $element);
    }

    public function getInputHtml(CustomField $field, Form $form, mixed $value): string
    {
        $value = $this->normalizeValue($value, $field, $form->getCurrentSubmission());

        return $this->renderInputs(
            field: $field,
            value: $value,
            name: $field->getHtmlName(),
            addressName: $field->getHtmlName('address'),
            latName: $field->getHtmlName('lat'),
            lngName: $field->getHtmlName('lng'),
            zoomName: $field->getHtmlName('zoom'),
            partsName: $field->getHtmlName('parts'),
            what3wordsName: $field->getHtmlName('what3words'),
            id: $field->getHtmlId($form),
            dataId: $field->getHtmlDataId($form),
            form: $form,
        );
    }

    public function getCpInputHtml(CustomField $field, mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $value = $this->normalizeValue($value, $field, $element);

        return $this->createCraftMapsField($field)->getInputHtml($this->createCraftMapsValue($value, $field), $element);
    }

    public function getValueAsString(mixed $value, CustomField $field, ?ElementInterface $element = null): string
    {
        $value = $this->normalizeValue($value, $field, $element);

        return $value instanceof CustomMapFieldValue ? (string)$value : parent::getValueAsString($value, $field, $element);
    }

    public function getValueAsArray(mixed $value, CustomField $field, ?ElementInterface $element = null): mixed
    {
        $value = $this->normalizeValue($value, $field, $element);

        return $value instanceof CustomMapFieldValue ? $value->toValueArray() : parent::getValueAsArray($value, $field, $element);
    }


    // Protected Methods
    // =========================================================================

    protected function renderInputs(CustomField $field, CustomMapFieldValue $value, string $name, string $addressName, string $latName, string $lngName, string $zoomName, string $partsName, string $what3wordsName, string $id, string $dataId, ?Form $form): string
    {
        $provider = static::handle();
        $placeholder = Craft::t('site', $this->getPlaceholder($field)) ?: Craft::t('formie', 'Search for a location');
        $showLatLng = $this->getBooleanSetting($field, 'showLatLng');
        $hideAddress = $this->getBooleanSetting($field, 'hideAddress');
        $hideMap = $this->getBooleanSetting($field, 'hideMap');
        $hideSearch = $this->getBooleanSetting($field, 'hideSearch');
        $settings = $this->getMapsPluginClientSettings($field);
        $searchInput = !$hideSearch ? $this->renderTextInput($field, $form, array_filter([
            'id' => $id . '-search',
            'name' => $hideAddress ? $addressName : null,
            'value' => $value->address,
            'placeholder' => $placeholder,
            'required' => $field->required,
            'data-formie-input' => true,
            'data-formie-custom-field-input' => true,
            'data-formie-custom-maps-search' => true,
            'data-formie-custom-map-address' => $hideAddress ? true : null,
            'data-formie-input-id' => $dataId,
            'data-formie-input-type' => $provider,
        ], static fn($value) => $value !== null)) : '';
        $addressInput = $hideAddress ? ($hideSearch ? Html::hiddenInput($addressName, $value->address, [
            'data-formie-custom-map-address' => true,
        ]) : '') : $this->renderTextInput($field, $form, [
            'id' => $id,
            'name' => $addressName,
            'value' => $value->address,
            'placeholder' => Craft::t('formie', 'Full Address'),
            'required' => $field->required,
            'data-formie-input' => true,
            'data-formie-custom-field-input' => true,
            'data-formie-custom-map-address' => true,
            'data-formie-input-id' => $dataId,
            'data-formie-input-type' => $provider,
        ]);
        $latInput = $showLatLng ? $this->renderTextInput($field, $form, [
            'name' => $latName,
            'value' => $value->lat,
            'placeholder' => Craft::t('formie', 'Latitude'),
            'data-formie-custom-map-lat' => true,
        ]) : Html::hiddenInput($latName, $value->lat, ['data-formie-custom-map-lat' => true]);
        $lngInput = $showLatLng ? $this->renderTextInput($field, $form, [
            'name' => $lngName,
            'value' => $value->lng,
            'placeholder' => Craft::t('formie', 'Longitude'),
            'data-formie-custom-map-lng' => true,
        ]) : Html::hiddenInput($lngName, $value->lng, ['data-formie-custom-map-lng' => true]);
        $map = $hideMap ? '' : Html::tag('div', '', [
            'class' => ['formie-custom-maps-canvas'],
            'style' => 'height: 360px; width: 100%; margin-top: 0.75rem;',
            'data-formie-custom-maps-canvas' => true,
        ]);
        $currentLocationButton = $this->getBooleanSetting($field, 'showCurrentLocation') ? Html::button(Craft::t('formie', 'Use my location'), [
            'type' => 'button',
            'class' => ['formie-button', 'formie-custom-maps-current-location'],
            'data-formie-custom-maps-current-location' => true,
        ]) : '';
        $parts = is_array($value->parts) ? Json::encode($value->parts) : (string)$value->parts;

        // Keep this markup transport-first: the JS module upgrades it to an
        // interactive picker, while no-JS submissions still send useful values.
        return Html::tag('div',
            $searchInput .
            $addressInput .
            $currentLocationButton .
            $map .
            $latInput .
            $lngInput .
            Html::hiddenInput($zoomName, $value->zoom, ['data-formie-custom-map-zoom' => true]) .
            Html::hiddenInput($partsName, $parts, ['data-formie-custom-maps-parts' => true]) .
            Html::hiddenInput($what3wordsName, $value->what3words, ['data-formie-custom-maps-what3words' => true]),
            [
                'data-formie-custom-map' => true,
                'data-formie-custom-maps' => true,
                'data-formie-custom-map-provider' => $provider,
                'data-formie-custom-map-name' => $name,
                'data-formie-custom-map-hide-search' => $hideSearch ? '1' : '0',
                'data-formie-custom-map-hide-map' => $hideMap ? '1' : '0',
                'data-formie-custom-map-show-current-location' => $this->getBooleanSetting($field, 'showCurrentLocation') ? '1' : '0',
                'data-formie-custom-map-field-size' => $this->getStringSetting($field, 'fieldSize', 'normal'),
                'data-formie-custom-map-preferred-country' => $this->getStringSetting($field, 'preferredCountry'),
                'data-formie-custom-map-min-zoom' => $this->getStringSetting($field, 'minZoom'),
                'data-formie-custom-map-max-zoom' => $this->getStringSetting($field, 'maxZoom'),
                'data-formie-custom-maps-settings' => Json::encode($settings),
                'class' => ['formie-custom-maps'],
            ]
        );
    }

    protected function renderTextInput(CustomField $field, ?Form $form, array $attributes): string
    {
        $attributes['type'] = $attributes['type'] ?? 'text';

        $tag = SlotTag::make('input')
            ->core($attributes)
            ->theme([
                'class' => [
                    'formie-input',
                ],
            ]);

        if (!$form) {
            $attributes = $tag->attributes;
            $name = (string)($attributes['name'] ?? '');
            $value = $attributes['value'] ?? null;
            $type = (string)($attributes['type'] ?? 'text');
            unset($attributes['name'], $attributes['value'], $attributes['type']);

            return Html::input($type, $name ?: null, $value, $attributes);
        }

        $context = new RenderContext($form, [
            'field' => $field,
        ]);

        $tag = Formie::$plugin->getThemeConfigService()->applyFieldTagConfig($field, $form, 'fieldInput', $tag, $context);

        return FormieTwigExtension::formatSlotTagHtml('fieldInput', $tag, $context->toArray());
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

    protected function getMapsPluginClientSettings(CustomField $field): array
    {
        $settings = SimpleMap::getInstance()->getSettings();
        $mapTiles = $settings->mapTiles === MapTiles::Wikimedia ? MapTiles::OpenStreetMap : $settings->mapTiles;
        $mapToken = $settings->getMapToken();
        $geoToken = $settings->getGeoToken();

        return [
            'mapTiles' => $mapTiles,
            'mapToken' => is_array($mapToken) ? $mapToken : (string)$mapToken,
            'geoService' => $settings->geoService,
            'geoToken' => is_array($geoToken) ? $geoToken : (string)$geoToken,
            'w3wEnabled' => $settings->isW3WEnabled(),
            'w3wToken' => $settings->getW3WToken(),
            'showW3WGrid' => $this->getBooleanSetting($field, 'showW3WGrid'),
            'showW3WField' => $this->getBooleanSetting($field, 'showW3WField'),
            'defaultLat' => $this->getNullableFloatSetting($field, 'defaultLat'),
            'defaultLng' => $this->getNullableFloatSetting($field, 'defaultLng'),
            'defaultZoom' => $this->getNullableIntSetting($field, 'defaultZoom') ?? 15,
            'minZoom' => $this->getNullableIntSetting($field, 'minZoom') ?? 3,
            'maxZoom' => $this->getNullableIntSetting($field, 'maxZoom') ?? 18,
            'country' => $this->getStringSetting($field, 'preferredCountry'),
        ];
    }

    protected function createCraftMapsField(CustomField $field): CraftMapsField
    {
        $defaultLat = $this->getNullableFloatSetting($field, 'defaultLat');
        $defaultLng = $this->getNullableFloatSetting($field, 'defaultLng');
        $preferredCountry = $this->getStringSetting($field, 'preferredCountry');

        $mapsField = new CraftMapsField();
        $mapsField->id = $field->id;
        $mapsField->handle = $field->handle;
        $mapsField->name = $field->label;
        $mapsField->lat = $defaultLat ?? $mapsField->lat;
        $mapsField->lng = $defaultLng ?? $mapsField->lng;
        $mapsField->zoom = $this->getNullableIntSetting($field, 'defaultZoom') ?? $mapsField->zoom;
        $mapsField->minZoom = $this->getNullableIntSetting($field, 'minZoom') ?? $mapsField->minZoom;
        $mapsField->maxZoom = $this->getNullableIntSetting($field, 'maxZoom') ?? $mapsField->maxZoom;
        $mapsField->country = $preferredCountry !== '' ? $preferredCountry : null;
        $mapsField->hideSearch = $this->getBooleanSetting($field, 'hideSearch');
        $mapsField->hideMap = $this->getBooleanSetting($field, 'hideMap');
        $mapsField->hideAddress = $this->getBooleanSetting($field, 'hideAddress');
        $mapsField->showLatLng = $this->getBooleanSetting($field, 'showLatLng');
        $mapsField->showCurrentLocation = $this->getBooleanSetting($field, 'showCurrentLocation');
        $mapsField->size = $this->getStringSetting($field, 'fieldSize', 'normal') === 'mini' ? 'mini' : 'normal';
        $mapsField->showW3WGrid = $this->getBooleanSetting($field, 'showW3WGrid');
        $mapsField->showW3WField = $this->getBooleanSetting($field, 'showW3WField');

        return $mapsField;
    }

    protected function createCraftMapsValue(CustomMapFieldValue $value, CustomField $field): CraftMapsValue
    {
        return new CraftMapsValue([
            'address' => $value->address ?? $this->getDefaultAddress($field),
            'lat' => $value->lat,
            'lng' => $value->lng,
            'zoom' => $value->zoom ?? $this->getNullableIntSetting($field, 'defaultZoom'),
            'parts' => $value->parts,
            'what3words' => $value->what3words,
        ]);
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
        return Craft::t('formie', 'Maps plugin');
    }

    protected function getSvgIcon(): ?string
    {
        return '<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="map-marker-alt" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M192 0C86 0 0 86 0 192c0 77.4 27 99 172.3 309.7 9.5 13.8 29.9 13.8 39.5 0C357 291 384 269.4 384 192 384 86 298 0 192 0zm0 464C52.7 262.6 48 256.9 48 192c0-79.5 64.5-144 144-144s144 64.5 144 144c0 64.6-4.4 70.1-144 272zm0-352c-44.2 0-80 35.8-80 80s35.8 80 80 80 80-35.8 80-80-35.8-80-80-80zm0 112c-17.6 0-32-14.4-32-32s14.4-32 32-32 32 14.4 32 32-14.4 32-32 32z"></path></svg>';
    }
}
