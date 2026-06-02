<?php
namespace verbb\formie\helpers;

use InvalidArgumentException;

class TypeHelper
{
    // Constants
    // =========================================================================

    public const TYPE_STRING = 'string';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_INT = 'int';
    public const TYPE_ID = 'id';


    // Static Methods
    // =========================================================================

    public static function parseTypedParam(mixed $value, string $type, mixed $default = null): mixed
    {
        if ($value === null) {
            return $default;
        }

        // Go case-by-case, so it's easier to handle, and more predictable
        if ($type === self::TYPE_STRING && is_string($value)) {
            return $value;
        }

        if ($type === self::TYPE_BOOLEAN) {
            if (is_bool($value)) {
                return $value;
            }

            if (is_string($value)) {
                return StringHelper::toBoolean($value);
            }
        }

        if ($type === self::TYPE_INT && (is_numeric($value) || $value === '')) {
            return (int)$value;
        }

        if ($type === self::TYPE_ID && is_numeric($value) && (int)$value > 0) {
            return (int)$value;
        }

        throw new InvalidArgumentException('Invalid typed param value.');
    }

    public static function getEnumParam(mixed $value, array $allowedValues, mixed $default = null): mixed
    {
        if ($value === null) {
            return $default;
        }

        if (!in_array($value, $allowedValues, true)) {
            return $default;
        }

        return $value;
    }
}
