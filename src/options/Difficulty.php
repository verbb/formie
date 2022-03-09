<?php
namespace verbb\formie\options;

use Craft;
use verbb\formie\base\PredefinedOption;

class Difficulty extends PredefinedOption
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
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
