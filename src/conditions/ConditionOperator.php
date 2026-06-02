<?php
namespace verbb\formie\conditions;

class ConditionOperator
{
    // Constants
    // =========================================================================

    public const EQ = '=';
    public const NEQ = '!=';
    public const GT = '>';
    public const LT = '<';
    public const CONTAINS = 'contains';
    public const NOT_CONTAINS = 'notContains';
    public const STARTS_WITH = 'startsWith';
    public const ENDS_WITH = 'endsWith';
    public const EMPTY = 'empty';
    public const NOT_EMPTY = 'notEmpty';
    

    // Static Methods
    // =========================================================================

    public static function all(): array
    {
        return [
            self::EQ,
            self::NEQ,
            self::GT,
            self::LT,
            self::CONTAINS,
            self::NOT_CONTAINS,
            self::STARTS_WITH,
            self::ENDS_WITH,
            self::EMPTY,
            self::NOT_EMPTY,
        ];
    }

    public static function isSupported(string $operator): bool
    {
        return in_array($operator, self::all(), true);
    }
}
