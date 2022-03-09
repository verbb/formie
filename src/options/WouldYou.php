<?php
namespace verbb\formie\options;

use Craft;
use verbb\formie\base\PredefinedOption;

class WouldYou extends PredefinedOption
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Would You');
    }

    public static function getDataOptions(): array
    {
        return [
            Craft::t('formie', 'Definitely'),
            Craft::t('formie', 'Probably'),
            Craft::t('formie', 'Neutral'),
            Craft::t('formie', 'Probably Not'),
            Craft::t('formie', 'Definitely Not'),
            Craft::t('formie', 'Not applicable'),
        ];
    }
}
