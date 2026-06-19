<?php
namespace verbb\formie\options\predefined;

use verbb\formie\base\PredefinedOption;

use Craft;

class LikertScale extends PredefinedOption
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Likert Scale');
    }

    public static function getDataOptions(): array
    {
        return [
            Craft::t('formie', 'Strongly disagree'),
            Craft::t('formie', 'Disagree'),
            Craft::t('formie', 'Neutral'),
            Craft::t('formie', 'Agree'),
            Craft::t('formie', 'Strongly agree'),
        ];
    }
}
