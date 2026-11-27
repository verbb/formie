<?php

declare(strict_types=1);

use verbb\formie\fields\CustomField;
use verbb\formie\fields\custom\adapters\MapsCustomFieldAdapter;

// The Maps plugin isn't installed in the test runtime. Its tile names are plain strings, so a stand-in
// for its MapTiles constants lets the real browser settings run against fake plugin settings.
if (!class_exists('ether\simplemap\enums\MapTiles')) {
    eval('namespace ether\simplemap\enums; class MapTiles { public const OpenStreetMap = "openstreetmap"; public const Wikimedia = "wikimedia"; public const MapKitStandard = "mapkit.standard"; }');
}

class MapsTokenSecurityAdapter extends MapsCustomFieldAdapter
{
    public static function craftFieldClasses(): array
    {
        return [];
    }

    public function browserSettings(CustomField $field): array
    {
        return $this->getMapsPluginBrowserSettings($field);
    }

    protected function getMapsPluginSettings(): object
    {
        return new class {
            public string $mapTiles = 'mapkit.standard';
            public string $geoService = 'apple';

            public function getMapToken(): array
            {
                return ['privateKey' => 'MAPKIT-PRIVATE-KEY', 'keyId' => 'KEY-ID', 'teamId' => 'TEAM-ID'];
            }

            public function getGeoToken(): array
            {
                return ['privateKey' => 'MAPKIT-PRIVATE-KEY', 'keyId' => 'KEY-ID', 'teamId' => 'TEAM-ID'];
            }

            public function isW3WEnabled(): bool
            {
                return true;
            }

            public function getW3WToken(): string
            {
                return 'W3W-SECRET';
            }
        };
    }
}

it('keeps the Maps plugin tokens out of public form settings', function (): void {
    $settings = (new MapsTokenSecurityAdapter())->browserSettings(new CustomField(['handle' => 'location']));
    $json = json_encode($settings);

    expect($settings)->not->toHaveKeys(['mapToken', 'geoToken', 'w3wToken'])
        ->and($json)->not->toContain('MAPKIT-PRIVATE-KEY')
        ->and($json)->not->toContain('W3W-SECRET')
        ->and($settings)->toHaveKeys(['mapTiles', 'geoService', 'w3wEnabled', 'defaultZoom', 'minZoom']);
});
