<?php
namespace verbb\formie\options;

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
        return [
            Craft::t('formie', 'Africa'),
            Craft::t('formie', 'Antarctica'),
            Craft::t('formie', 'Asia'),
            Craft::t('formie', 'Australia'),
            Craft::t('formie', 'Europe'),
            Craft::t('formie', 'North America'),
            Craft::t('formie', 'South America'),
        ];
    }
}
