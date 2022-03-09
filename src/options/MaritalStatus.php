<?php
namespace verbb\formie\options;

use Craft;
use verbb\formie\base\PredefinedOption;

class MaritalStatus extends PredefinedOption
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Marital Status');
    }

    public static function getDataOptions(): array
    {
        return [
            Craft::t('formie', 'Single'),
            Craft::t('formie', 'Married'),
            Craft::t('formie', 'Divorced'),
            Craft::t('formie', 'Widowed'),
            Craft::t('formie', 'Prefer not to answer'),
        ];
    }
}
