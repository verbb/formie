<?php
namespace verbb\formie\options;

use Craft;
use verbb\formie\base\PredefinedOption;

class Gender extends PredefinedOption
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
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
