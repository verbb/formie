<?php
namespace verbb\formie\options;

use Craft;
use verbb\formie\base\PredefinedOption;

class HowOften extends PredefinedOption
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'How Often');
    }

    public static function getDataOptions(): array
    {
        return [
            Craft::t('formie', 'Every day'),
            Craft::t('formie', 'Once a week'),
            Craft::t('formie', '2 to 3 times a week'),
            Craft::t('formie', 'Once a month'),
            Craft::t('formie', '2 to 3 times a month'),
            Craft::t('formie', 'Less than once a month'),
        ];
    }
}
