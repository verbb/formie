<?php
namespace verbb\formie\models;

use InvalidArgumentException;

/** Versioned, non-executable builder metadata. Expired data is display-only until refreshed. */
final class IntegrationConfig
{
    // Static Methods
    // =========================================================================

    public static function encode(mixed $value, int $depth = 0): mixed
    {
        if ($depth > 16) {
            throw new InvalidArgumentException('Integration metadata is too deeply nested.');
        }
        if ($value instanceof IntegrationField || $value instanceof IntegrationCollection) {
            $kind = $value instanceof IntegrationField ? 'field' : 'collection';
            return ['_kind' => $kind, 'attributes' => self::encode(get_object_vars($value), $depth + 1)];
        }
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                if (preg_match('/^(?:class|__class|as .+|on .+|password|clientSecret|apiKey|accessToken|refreshToken|authorization|cookie)$/i', (string)$key)) {
                    continue;
                }
                $result[$key] = self::encode($item, $depth + 1);
            }
            return $result;
        }
        if (is_object($value) || is_resource($value)) {
            throw new InvalidArgumentException('Integration metadata must contain only data.');
        }
        return $value;
    }

    public static function decode(mixed $value, int $depth = 0): mixed
    {
        if ($depth > 16 || !is_array($value)) {
            return $depth > 16 ? null : $value;
        }

        // Stable cached metadata used class names. Decode only these exact inert
        // models; never invoke a constructor selected by persisted data.
        $kind = $value['_kind'] ?? match ($value['class'] ?? null) {
            IntegrationField::class => 'field',
            IntegrationCollection::class => 'collection',
            default => null,
        };
        if (array_key_exists('class', $value) && $kind === null) {
            throw new InvalidArgumentException('Unsupported legacy integration metadata. Refresh this connection.');
        }
        if ($kind !== null) {
            $model = match ($kind) {
                'field' => new IntegrationField(),
                'collection' => new IntegrationCollection(),
                default => throw new InvalidArgumentException('Unsupported integration metadata kind.'),
            };
            $attributes = $value['attributes'] ?? $value;
            foreach (array_keys(get_object_vars($model)) as $property) {
                if (array_key_exists($property, $attributes)) {
                    $model->$property = self::decode($attributes[$property], $depth + 1);
                }
            }
            return $model;
        }

        return array_map(fn($item) => self::decode($item, $depth + 1), $value);
    }

    public static function fromStorage(array $value, string $invalidationKey): self
    {
        if (($value['version'] ?? null) !== self::VERSION || ($value['invalidationKey'] ?? '') !== $invalidationKey) {
            return new self([], $invalidationKey, 0);
        }
        return new self((array)($value['data'] ?? []), $invalidationKey, (int)($value['fetchedAt'] ?? 0));
    }


    // Constants
    // =========================================================================

    public const VERSION = 1;
    public const FRESH_SECONDS = 86400;
    public const MAX_BYTES = 2097152;


    // Properties
    // =========================================================================

    public readonly array $data;
    public readonly string $invalidationKey;
    public readonly int $fetchedAt;


    // Public Methods
    // =========================================================================

    public function __construct(array $data, string $invalidationKey, int $fetchedAt)
    {
        $data = self::encode($data);
        if (strlen(json_encode($data, JSON_THROW_ON_ERROR)) > self::MAX_BYTES) {
            throw new InvalidArgumentException('Integration metadata exceeds the storage limit.');
        }
        $this->data = $data;
        $this->invalidationKey = $invalidationKey;
        $this->fetchedAt = $fetchedAt;
    }

    public function isStale(?int $now = null): bool
    {
        return $this->fetchedAt <= 0 || ($now ?? time()) - $this->fetchedAt >= self::FRESH_SECONDS;
    }

    public function toStorage(): array
    {
        return ['version' => self::VERSION, 'fetchedAt' => $this->fetchedAt, 'invalidationKey' => $this->invalidationKey, 'data' => $this->data];
    }
}
