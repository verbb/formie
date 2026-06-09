<?php
namespace verbb\formie\options;

final class OptionSourceValidationMode
{
    // Constants
    // =========================================================================

    public const STRICT = 'strict';
    public const ACCEPT_SUBMITTED = 'acceptSubmitted';
    

    // Static Method
    // =========================================================================

    public static function normalize(mixed $mode): string
    {
        return $mode === self::ACCEPT_SUBMITTED ? self::ACCEPT_SUBMITTED : self::STRICT;
    }
}
