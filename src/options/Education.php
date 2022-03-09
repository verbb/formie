<?php
namespace verbb\formie\options;

use Craft;
use verbb\formie\base\PredefinedOption;

class Education extends PredefinedOption
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Education');
    }

    public static function getDataOptions(): array
    {
        return [
            Craft::t('formie', 'High School'),
            Craft::t('formie', 'Associate Degree'),
            Craft::t('formie', 'Bachelor‘s Degree'),
            Craft::t('formie', 'Graduate or Professional Degree'),
            Craft::t('formie', 'Some College'),
            Craft::t('formie', 'Other'),
            Craft::t('formie', 'Prefer not to answer'),
        ];
    }
}
