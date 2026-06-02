<?php
namespace verbb\formie\fields\coercion;

use verbb\formie\fields\values\FieldValueInterface;

final class EmptyValueCoercer
{
    // Static Methods
    // =========================================================================

    public static function isEmpty(mixed $value): bool
    {
        if ($value instanceof FieldValueInterface) {
            return $value->isEmpty();
        }

        if (is_array($value)) {
            $hasNonEmptyValue = false;

            array_walk_recursive($value, function($item) use (&$hasNonEmptyValue) {
                if ($item !== null && $item !== '' && $item !== []) {
                    $hasNonEmptyValue = true;
                }
            });

            return !$hasNonEmptyValue;
        }

        return $value === null || $value === [] || $value === '';
    }
}
