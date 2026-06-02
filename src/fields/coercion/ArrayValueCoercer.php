<?php
namespace verbb\formie\fields\coercion;

use verbb\formie\fields\values\FieldValueInterface;
use verbb\formie\helpers\ArrayHelper;

use Arrayable;
use Serializable;
use Traversable;

final class ArrayValueCoercer
{
    // Static Methods
    // =========================================================================

    public static function forIntegration(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        if ($value instanceof FieldValueInterface) {
            return $value->toValueArray();
        }

        if ($value instanceof Traversable) {
            return iterator_to_array($value);
        }

        return [$value];
    }

    public static function normalizeForField(mixed $value, bool $supportsArray): ?array
    {
        if ($value === null) {
            return [];
        }

        if ($value instanceof FieldValueInterface && $supportsArray) {
            return $value->toValueArray();
        }

        if (is_array($value)) {
            return $value;
        }

        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        if ($value instanceof Serializable) {
            $value = $value->serialize();
        }

        if (is_iterable($value)) {
            return ArrayHelper::toArray($value);
        }

        return null;
    }
}
