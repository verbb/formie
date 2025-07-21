<?php
namespace verbb\formie\options;

use verbb\formie\base\PredefinedOption;

use Craft;

class HowLong extends PredefinedOption
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'How Long');
    }

    public static function getDataOptions(): array
    {
        return [
            Craft::t('formie', 'Less than a month'),
            Craft::t('formie', '1-6 months'),
            Craft::t('formie', '1-3 years'),
            Craft::t('formie', 'Over 3 years'),
            Craft::t('formie', 'Never used'),
        ];
    }
}
