<?php
namespace verbb\formie\fields\definitions;

use craft\elements\db\ElementQueryInterface;

use LogicException;

final class FieldValueType
{
    // Static Methods
    // =========================================================================

    public static function string(): self
    {
        return new self('string');
    }

    public static function boolean(): self
    {
        return new self('boolean');
    }

    public static function object(string $class): self
    {
        return new self('object', $class);
    }

    public static function array(?self $items = null): self
    {
        return new self('array', items: $items);
    }

    public static function relationQuery(string $elementType): self
    {
        return new self('relationQuery', $elementType);
    }

    public static function none(): self
    {
        return new self('none');
    }

    // Used only for unavailable owners and bounded legacy adapters, not to infer object schemas.
    public static function storageSafe(): self
    {
        return new self('storageSafe');
    }


    private static function _isStorageSafe(mixed $value): bool
    {
        if ($value === null || is_scalar($value)) {
            return !is_float($value) || is_finite($value);
        }

        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (!self::_isStorageSafe($item)) {
                return false;
            }
        }

        return true;
    }

    // Properties
    // =========================================================================

    public readonly string $kind;
    public readonly ?string $class;
    public readonly ?self $items;


    // Public Methods
    // =========================================================================

    public function __construct(string $kind, ?string $class = null, ?self $items = null)
    {
        $this->kind = $kind;
        $this->class = $class;
        $this->items = $items;
    }

    public function accepts(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        return match ($this->kind) {
            'string' => is_string($value),
            'boolean' => is_bool($value),
            'object' => $value instanceof $this->class,
            'relationQuery' => $value instanceof ElementQueryInterface && is_a($value->elementType, $this->class, true),
            'array' => is_array($value) && ($this->items === null || count(array_filter($value, fn($item) => !$this->items->accepts($item))) === 0),
            'storageSafe' => self::_isStorageSafe($value),
            default => false,
        };
    }

    public function assert(mixed $value, string $owner): mixed
    {
        if (!$this->accepts($value)) {
            throw new LogicException($owner . ' normalized to ' . get_debug_type($value) . '; expected FieldValueType::' . $this->kind . ($this->class ? '(' . $this->class . ')' : '') . '. Declare defineValueType() and return that runtime type from normalizeValue().');
        }

        return $value;
    }

    public function toArray(): array
    {
        return array_filter([
            'kind' => $this->kind,
            'class' => $this->class,
            'items' => $this->items?->toArray(),
        ], static fn($value) => $value !== null);
    }
}
