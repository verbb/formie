<?php
namespace verbb\formie\options;

use Craft;
use verbb\formie\base\PredefinedOption;

class Age extends PredefinedOption
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Age');
    }

    public static function getDataOptions(): array
    {
        return [
            Craft::t('formie', 'Under 18'),
            Craft::t('formie', '18-24'),
            Craft::t('formie', '25-34'),
            Craft::t('formie', '35-44'),
            Craft::t('formie', '45-54'),
            Craft::t('formie', '55-64'),
            Craft::t('formie', '65 or above'),
            Craft::t('formie', 'Prefer not to answer'),
        ];
    }
}
