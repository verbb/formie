<?php
namespace verbb\formie\options;

use Craft;
use verbb\formie\base\PredefinedOption;

class Comparison extends PredefinedOption
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Comparison');
    }

    public static function getDataOptions(): array
    {
        return [
            Craft::t('formie', 'Much Better'),
            Craft::t('formie', 'Somewhat Better'),
            Craft::t('formie', 'About the Same'),
            Craft::t('formie', 'Somewhat Worse'),
            Craft::t('formie', 'Much Worse'),
            Craft::t('formie', 'Not applicable'),
        ];
    }
}
