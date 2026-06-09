<?php
namespace verbb\formie\helpers;

final class OptionsMode
{
    // Constants
    // =========================================================================

    public const STATIC = 'static';
    public const DYNAMIC = 'dynamic';
    public const TEMPLATE = 'template';


    // Static Methods
    // =========================================================================

    public static function all(): array
    {
        return [
            self::STATIC,
            self::DYNAMIC,
            self::TEMPLATE,
        ];
    }

    public static function normalize(mixed $mode): string
    {
        if (!is_string($mode)) {
            return self::STATIC;
        }

        return in_array($mode, self::all(), true) ? $mode : self::STATIC;
    }

    public static function usesStrictInValidation(string $mode): bool
    {
        return in_array($mode, [self::STATIC, self::DYNAMIC], true);
    }
}
