<?php
namespace verbb\formie\options;

use verbb\formie\base\PredefinedOption;

use Craft;

class Satisfaction extends PredefinedOption
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Satisfaction');
    }

    public static function getDataOptions(): array
    {
        return [
            Craft::t('formie', 'Very satisfied'),
            Craft::t('formie', 'Satisfied'),
            Craft::t('formie', 'Neutral'),
            Craft::t('formie', 'Unsatisfied'),
            Craft::t('formie', 'Very unsatisfied'),
            Craft::t('formie', 'Not applicable'),
        ];
    }
}
