<?php
namespace verbb\formie\options;

use Craft;
use verbb\formie\base\PredefinedOption;

class Employment extends PredefinedOption
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Employment');
    }

    public static function getDataOptions(): array
    {
        return [
            Craft::t('formie', 'Full-Time'),
            Craft::t('formie', 'Part-Time'),
            Craft::t('formie', 'Self-Employed'),
            Craft::t('formie', 'Homemaker'),
            Craft::t('formie', 'Retired'),
            Craft::t('formie', 'Student'),
            Craft::t('formie', 'Prefer not to answer'),
        ];
    }
}
