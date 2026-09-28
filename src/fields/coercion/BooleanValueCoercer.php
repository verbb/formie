<?php
namespace verbb\formie\fields\coercion;

use verbb\formie\fields\values\DateFieldValue;
use verbb\formie\fields\values\FieldValueInterface;
use verbb\formie\helpers\StringHelper;

use Countable;

final class BooleanValueCoercer
{
    // Static Methods
    // =========================================================================

    public static function toBoolean(mixed $value): bool
    {
        // The date client's parts array describes the editor, not an opt-in.
        if ($value instanceof DateFieldValue) {
            return self::toBoolean((string)$value);
        }

        if ($value instanceof FieldValueInterface) {
            return !$value->isEmpty();
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
