<?php
namespace verbb\formie\options;

use verbb\formie\base\PredefinedOption;

use Craft;

class Gender extends PredefinedOption
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Gender');
    }

    public static function getDataOptions(): array
    {
        return [
            Craft::t('formie', 'Male'),
            Craft::t('formie', 'Female'),
            Craft::t('formie', 'Neither'),
            Craft::t('formie', 'Prefer not to answer'),
        ];
    }
}
