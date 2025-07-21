<?php
namespace verbb\formie\options;

use verbb\formie\base\PredefinedOption;

use Craft;

class Difficulty extends PredefinedOption
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Difficulty');
    }

    public static function getDataOptions(): array
    {
        return [
            Craft::t('formie', 'Very easy'),
            Craft::t('formie', 'Easy'),
            Craft::t('formie', 'Neutral'),
            Craft::t('formie', 'Difficult'),
            Craft::t('formie', 'Very difficult'),
            Craft::t('formie', 'Not applicable'),
        ];
    }
}
