<?php
namespace verbb\formie\fields\coercion;

use DateTimeInterface;
use Stringable;

final class ScalarValueCoercer
{
    // Static Methods
    // =========================================================================

    public static function normalizeScalarLike(mixed $value): mixed
    {
        if ($value === null || is_scalar($value) || $value instanceof DateTimeInterface) {
            return $value;
        }

        if ($value instanceof Stringable) {
            return (string)$value;
        }

        return null;
    }

    public static function toScalarString(mixed $value): string
    {
        $value = self::normalizeScalarLike($value);

        return is_scalar($value) ? (string)$value : '';
    }
}
