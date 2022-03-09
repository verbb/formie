<?php
namespace verbb\formie\options;

use Craft;
use verbb\formie\base\PredefinedOption;

class Agreement extends PredefinedOption
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Agreement');
    }

    public static function getDataOptions(): array
    {
        return [
            Craft::t('formie', 'Strongly agree'),
            Craft::t('formie', 'Agree'),
            Craft::t('formie', 'Neutral'),
            Craft::t('formie', 'Disagree'),
            Craft::t('formie', 'Strongly disagree'),
            Craft::t('formie', 'Not applicable'),
        ];
    }
}
