<?php
namespace verbb\formie\fields\values;

use verbb\formie\content\FieldStorageCodec;

use craft\helpers\Json;

class CustomMapFieldValue extends BaseFieldValue
{
    // Properties
    // =========================================================================

    protected ?string $address = null;
    protected ?float $lat = null;
    protected ?float $lng = null;
    protected ?int $zoom = null;
    protected array|string|null $parts = null;
    protected ?string $what3words = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        if (isset($config['parts']) && is_string($config['parts'])) {
            $decoded = Json::decodeIfJson($config['parts']);
            $config['parts'] = is_array($decoded) ? $decoded : null;
        }

        foreach (['lat', 'lng'] as $key) {
            if (array_key_exists($key, $config) && $config[$key] !== '' && $config[$key] !== null) {
                $config[$key] = (float)$config[$key];
            } elseif (array_key_exists($key, $config)) {
                $config[$key] = null;
            }
        }

        if (array_key_exists('zoom', $config) && $config['zoom'] !== '' && $config['zoom'] !== null) {
            $config['zoom'] = (int)$config['zoom'];
        } elseif (array_key_exists('zoom', $config)) {
            $config['zoom'] = null;
        }

        FieldStorageCodec::assertSafe($config);
        $this->address = isset($config['address']) ? (string)$config['address'] : null;
        $this->lat = $config['lat'] ?? null;
        $this->lng = $config['lng'] ?? null;
        $this->zoom = $config['zoom'] ?? null;
        $this->parts = $config['parts'] ?? null;
        $this->what3words = isset($config['what3words']) ? (string)$config['what3words'] : null;
    }

    public function __toString(): string
    {
        if ($this->address) {
            return $this->address;
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
            'address' => $this->address,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'zoom' => $this->zoom,
            'parts' => $this->parts,
            'what3words' => $this->what3words,
        ];
    }
}
