<?php
// The Maps and Google Maps plugins aren't installed in the test runtime. These adapters keep everything Formie owns,
// the markup, browser modules and answers, and stand in for the plugin's settings, so browser tests can show map
// fields in server-rendered and client-rendered forms. The SDKs and map services are faked at the network boundary.
use verbb\formie\events\RegisterCustomFieldAdaptersEvent;
use verbb\formie\fields\CustomField;
use verbb\formie\fields\custom\adapters\GoogleMapsCustomFieldAdapter;
use verbb\formie\fields\custom\adapters\MapsCustomFieldAdapter;
use verbb\formie\fields\values\CustomGoogleMapsAddressFieldValue;
use verbb\formie\services\CustomFields;
use craft\helpers\Html;
use yii\base\Event;

class BrowserMapsCustomFieldAdapter extends MapsCustomFieldAdapter
{
    public static function craftFieldClasses(): array
    {
        return [];
    }

    protected function getMapsPluginBrowserSettings(CustomField $field): array
    {
        return [
            'mapTiles' => 'openstreetmap',
            'mapToken' => '',
            'geoService' => 'nominatim',
            'geoToken' => '',
            'w3wEnabled' => false,
            'w3wToken' => '',
            'showW3WGrid' => false,
            'showW3WField' => false,
            'defaultLat' => $this->getNullableFloatSetting($field, 'defaultLat'),
            'defaultLng' => $this->getNullableFloatSetting($field, 'defaultLng'),
            'defaultZoom' => $this->getNullableIntSetting($field, 'defaultZoom') ?? 15,
            'minZoom' => 3,
            'maxZoom' => 18,
            'country' => $this->getStringSetting($field, 'preferredCountry'),
        ];
    }
}

class BrowserGoogleMapsCustomFieldAdapter extends GoogleMapsCustomFieldAdapter
{
    public static function craftFieldClasses(): array
    {
        return [];
    }

    protected function renderSubfieldInputs(CustomGoogleMapsAddressFieldValue $value, string $handlePrefix): string
    {
        // The plugin's default sub-fields that the adapter shows.
        $html = '';

        foreach (['name' => 'Name', 'street1' => 'Street Address', 'street2' => 'Apartment or Suite', 'city' => 'City', 'state' => 'State', 'zip' => 'Zip Code', 'country' => 'Country'] as $handle => $label) {
            $html .= Html::textInput($handlePrefix . '[' . $handle . ']', $value->{$handle} ?? null, [
                'placeholder' => $label,
                'class' => ['formie-input', 'formie-custom-google-maps-subfield'],
                'data-formie-custom-google-maps-field' => $handle,
            ]);
        }

        return $html;
    }

    protected function getGoogleMapsBrowserSettings(CustomField $field): array
    {
        return array_merge(parent::getGoogleMapsBrowserSettings($field), [
            'apiUrl' => 'https://maps.googleapis.com/maps/api/js?key=browser-test&libraries=places&loading=async',
        ]);
    }
}

Event::on(CustomFields::class, CustomFields::EVENT_REGISTER_CUSTOM_FIELD_ADAPTERS, static function(RegisterCustomFieldAdaptersEvent $event): void {
    $event->adapters[] = BrowserMapsCustomFieldAdapter::class;
    $event->adapters[] = BrowserGoogleMapsCustomFieldAdapter::class;
});
