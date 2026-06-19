<?php
namespace verbb\formie\options\predefined;

use verbb\formie\base\PredefinedOption;

use Craft;

class StarRating extends PredefinedOption
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Star Rating');
    }

    public static function getDataOptions(): array
    {
        return [
            Craft::t('formie', 'Terrible'),
            Craft::t('formie', 'Not so great'),
            Craft::t('formie', 'Neutral'),
            Craft::t('formie', 'Pretty good'),
            Craft::t('formie', 'Excellent'),
        ];
    }
}
