<?php
namespace verbb\formie\fields\coercion;

use verbb\formie\fields\values\FieldValueInterface;
use verbb\formie\helpers\StringHelper;

use Countable;

final class BooleanValueCoercer
{
    // Static Methods
    // =========================================================================

    public static function toBoolean(mixed $value): bool
    {
        if ($value instanceof FieldValueInterface) {
            return self::toBoolean($value->toClientValue());
        }

        if (is_array($value)) {
            return count($value) > 0;
        }

        if ($value instanceof Countable) {
            return count($value) > 0;
        }

        if (is_iterable($value)) {
            foreach ($value as $_) {
                return true;
            }

            return false;
        }

        $value = ScalarValueCoercer::normalizeScalarLike($value);

        return StringHelper::toBoolean(is_scalar($value) ? (string)$value : '');
    }
}
