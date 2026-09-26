<?php
namespace verbb\formie\fields\values;

use verbb\formie\helpers\ArrayHelper;

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
        parent::__construct($config);
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

    public function toValueArray(): array
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
