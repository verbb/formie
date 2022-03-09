<?php
namespace verbb\formie\options;

use Craft;
use verbb\formie\base\PredefinedOption;

class Acceptability extends PredefinedOption
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Acceptability');
    }

    public static function getDataOptions(): array
    {
        return [
            Craft::t('formie', 'Acceptable'),
            Craft::t('formie', 'Somewhat acceptable'),
            Craft::t('formie', 'Neutral'),
            Craft::t('formie', 'Unacceptable'),
            Craft::t('formie', 'Totally unacceptable'),
            Craft::t('formie', 'Not applicable'),
        ];
    }
}
