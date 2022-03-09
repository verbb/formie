<?php
namespace verbb\formie\options;

use Craft;
use verbb\formie\base\PredefinedOption;

class Importance extends PredefinedOption
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Importance');
    }

    public static function getDataOptions(): array
    {
        return [
            Craft::t('formie', 'Very important'),
            Craft::t('formie', 'Important'),
            Craft::t('formie', 'Neutral'),
            Craft::t('formie', 'Somewhat important'),
            Craft::t('formie', 'Not at all important'),
            Craft::t('formie', 'Not applicable'),
        ];
    }
}
