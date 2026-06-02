<?php
namespace verbb\formie\fields\coercion;

use verbb\formie\fields\values\FieldValueInterface;

use DateTimeInterface;

final class ScalarValueCoercer
{
    // Static Methods
    // =========================================================================

    public static function normalizeScalarLike(mixed $value): mixed
    {
        if ($value === null || is_scalar($value) || $value instanceof DateTimeInterface) {
            return $value;
        }

        if ($value instanceof FieldValueInterface) {
            return $value->toValueString();
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return $value->__toString();
        }

        return null;
    }

    public static function toScalarString(mixed $value): string
    {
        $value = self::normalizeScalarLike($value);

        return is_scalar($value) ? (string)$value : '';
    }
}
