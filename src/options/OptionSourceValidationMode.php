<?php
namespace verbb\formie\options;

final class OptionSourceValidationMode
{
    // Static Methods
    // =========================================================================

    public static function normalize(mixed $mode): string
    {
        return $mode === self::ACCEPT_SUBMITTED ? self::ACCEPT_SUBMITTED : self::STRICT;
    }


    // Constants
    // =========================================================================

    public const STRICT = 'strict';
    public const ACCEPT_SUBMITTED = 'acceptSubmitted';
}
