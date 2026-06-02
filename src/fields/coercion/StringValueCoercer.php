<?php
namespace verbb\formie\fields\coercion;

use verbb\formie\fields\values\FieldValueInterface;

final class StringValueCoercer
{
    // Static Methods
    // =========================================================================

    public static function asString(mixed $value): string
    {
        if ($value instanceof FieldValueInterface) {
            return $value->toValueString();
        }

        if (is_array($value)) {
            return implode(', ', array_map(static function($item) {
                if ($item instanceof FieldValueInterface) {
                    return $item->toValueString();
                }

                if (is_scalar($item) || (is_object($item) && method_exists($item, '__toString'))) {
                    return (string)$item;
                }

                return '';
            }, $value));
        }

        return ScalarValueCoercer::toScalarString($value);
    }

    public static function asArray(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        $string = self::asString($value);

        return $string !== '' ? [$string] : [];
    }

    public static function forCondition(mixed $value): mixed
    {
        return $value;
    }
}
