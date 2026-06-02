<?php
namespace verbb\formie\models;

use craft\helpers\Json;

final class PaymentFieldPayload
{
    // Public Properties
    // =========================================================================

    public string $provider = '';
    public string $fieldKey = '';
    public array $parts = [];


    // Public Methods
    // =========================================================================

    public function __construct(string $provider = '', string $fieldKey = '', array $parts = [])
    {
        $this->provider = $provider;
        $this->fieldKey = $fieldKey;
        $this->parts = $parts;
    }

    public function __get(string $name): mixed
    {
        return $this->parts[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->parts);
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function fieldKey(): string
    {
        return $this->fieldKey;
    }

    public function all(): array
    {
        return $this->parts;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->parts);
    }

    public function string(string $key): ?string
    {
        $value = $this->__get($key);

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    public function array(string $key): array
    {
        $value = $this->__get($key);

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && Json::isJsonObject($value)) {
            $decoded = Json::decode($value);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }
}
