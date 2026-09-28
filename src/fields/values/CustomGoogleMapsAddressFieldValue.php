<?php
namespace verbb\formie\fields\values;

use craft\helpers\Json;

use doublesecretagency\googlemaps\models\Address as GoogleMapsAddress;

class CustomGoogleMapsAddressFieldValue extends BaseFieldValue
{
    // Properties
    // =========================================================================

    protected ?string $formatted = null;
    protected array|string|null $raw = null;
    protected ?string $name = null;
    protected ?string $street1 = null;
    protected ?string $street2 = null;
    protected ?string $city = null;
    protected ?string $state = null;
    protected ?string $zip = null;
    protected ?string $neighborhood = null;
    protected ?string $county = null;
    protected ?string $country = null;
    protected ?string $countryCode = null;
    protected ?string $placeId = null;
    protected ?float $lat = null;
    protected ?float $lng = null;
    protected ?int $zoom = null;


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

        \verbb\formie\content\FieldStorageCodec::assertSafe($config);

        foreach (['formatted', 'name', 'street1', 'street2', 'city', 'state', 'zip', 'neighborhood', 'county', 'country', 'countryCode', 'placeId'] as $key) {
            $value = $config[$key] ?? null;
            $this->{$key} = $value === null ? null : (string)$value;
        }

        $this->raw = $config['raw'] ?? null;
        $this->lat = $config['lat'] ?? null;
        $this->lng = $config['lng'] ?? null;
        $this->zoom = $config['zoom'] ?? null;
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

    public function toArray(): array
    {
        return [
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
        ];
    }
}
