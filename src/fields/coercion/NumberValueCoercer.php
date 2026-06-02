<?php
namespace verbb\formie\fields\coercion;

final class NumberValueCoercer
{
    // Static Methods
    // =========================================================================

    public static function toInt(mixed $value): ?int
    {
        $value = ScalarValueCoercer::normalizeScalarLike($value);

        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) || is_float($value) || is_bool($value)) {
            $intValue = filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

            return is_int($intValue) ? $intValue : null;
        }

        return null;
    }

    public static function toFloat(mixed $value): ?float
    {
        $value = ScalarValueCoercer::normalizeScalarLike($value);

        if ($value === null || $value === '') {
            return null;
        }

        if (is_float($value)) {
            return $value;
        }

        if (is_int($value)) {
            return (float)$value;
        }

        if (is_string($value) || is_bool($value)) {
            $floatValue = filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);

            return is_float($floatValue) ? $floatValue : null;
        }

        return null;
    }
}
