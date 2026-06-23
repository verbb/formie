<?php
namespace verbb\formie\options\predefined;

use verbb\formie\base\PredefinedOption;

use Craft;

class Continents extends PredefinedOption
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Continents');
    }

    public static function getDataOptions(): array
    {
        // Deliberately not using Craft::t().
        return [
            'Africa',
            'Antarctica',
            'Asia',
            'Australia',
            'Europe',
            'North America',
            'South America',
        ];
    }
}
