<?php
namespace verbb\formie\helpers;

use craft\helpers\StringHelper as CraftStringHelper;

use LitEmoji\LitEmoji;

use voku\helper\AntiXSS;

class StringHelper extends CraftStringHelper
{
    // Static Methods
    // =========================================================================

    public static function toId(mixed $value, bool $allowNull = true): ?int
    {
        if ($allowNull && ($value === null || $value === '')) {
            return null;
        }

        if ($value === null || is_scalar($value)) {
            return (int)$value;
        }

        return null;
    }

    public static function entitiesToEmoji(string $str): string
    {
        return LitEmoji::entitiesToUnicode($str);
    }

    public static function encodeHtml(string $str): string
    {
        return LitEmoji::encodeHtml($str);
    }

    public static function cleanString(string $str): string
    {
        return (new AntiXSS())->xss_clean((string)$str);
    }
}