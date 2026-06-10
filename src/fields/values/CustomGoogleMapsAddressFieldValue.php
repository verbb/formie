<?php
namespace verbb\formie\fields\values;

use verbb\formie\helpers\ArrayHelper;

use craft\helpers\Json;

use doublesecretagency\googlemaps\models\Address as GoogleMapsAddress;

class CustomGoogleMapsAddressFieldValue extends BaseFieldValue
{
    // Static Methods
    // =========================================================================

    public static function capabilityTypes(): array
    {
        return ['string', 'array'];
    }


    // Properties
    // =========================================================================

    public ?string $formatted = null;
    public array|string|null $raw = null;
    public ?string $name = null;
    public ?string $street1 = null;
    public ?string $street2 = null;
    public ?string $city = null;
    public ?string $state = null;
    public ?string $zip = null;
    public ?string $neighborhood = null;
    public ?string $county = null;
    public ?string $country = null;
    public ?string $countryCode = null;
    public ?string $placeId = null;
    public ?float $lat = null;
    public ?float $lng = null;
    public ?int $zoom = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        if (isset($config['raw']) && is_string($config['raw'])) {
            $decoded = Json::decodeIfJson($config['raw']);
            $config['raw'] = is_array($decoded) ? $decoded : null;
        }

        foreach (['lat', 'lng'] as $key) {
            if (array_key_exists($key, $config) && $config[$key] !== '' && $config[$key] !== null) {
                $config[$key] = (float)$config[$key];
            } else if (array_key_exists($key, $config)) {
                $config[$key] = null;
            }
        }

        if (array_key_exists('zoom', $config) && $config['zoom'] !== '' && $config['zoom'] !== null) {
            $config['zoom'] = (int)$config['zoom'];
        } else if (array_key_exists('zoom', $config)) {
            $config['zoom'] = null;
        }

        parent::__construct($config);
    }

    public static function fromGoogleMapsAddress(GoogleMapsAddress $address): self
    {
        return new self([
            'formatted' => $address->formatted,
            'raw' => $address->raw,
            'name' => $address->name,
            'street1' => $address->street1,
            'street2' => $address->street2,
            'city' => $address->city,
            'state' => $address->state,
            'zip' => $address->zip,
            'neighborhood' => $address->neighborhood,
            'county' => $address->county,
            'country' => $address->country,
            'countryCode' => $address->countryCode,
            'placeId' => $address->placeId,
            'lat' => $address->lat,
            'lng' => $address->lng,
            'zoom' => $address->zoom,
        ]);
    }

    public function __toString(): string
    {
        if ($this->formatted) {
            return $this->formatted;
        }

        $parts = array_filter([
            $this->name,
            $this->street1,
            $this->street2,
            $this->city,
            $this->state,
            $this->zip,
            $this->country,
        ]);

        if ($parts !== []) {
            return implode(', ', $parts);
        }

        if ($this->lat !== null && $this->lng !== null) {
            return $this->lat . ', ' . $this->lng;
        }

        return '';
    }

    public function isEmpty(): bool
    {
        return (string)$this === '';
    }

    public function toValueArray(): array
    {
        return ArrayHelper::filterEmptyStringsFromArray([
            'formatted' => $this->formatted,
            'raw' => $this->raw,
            'name' => $this->name,
            'street1' => $this->street1,
            'street2' => $this->street2,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'neighborhood' => $this->neighborhood,
            'county' => $this->county,
            'country' => $this->country,
            'countryCode' => $this->countryCode,
            'placeId' => $this->placeId,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'zoom' => $this->zoom,
        ]);
    }
}
