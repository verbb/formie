<?php
namespace verbb\formie\options;

use Craft;
use verbb\formie\base\PredefinedOption;

class StatesAustralia extends PredefinedOption
{
    // Protected Properties
    // =========================================================================

    public static $defaultLabelOption = 'name';
    public static $defaultValueOption = 'name';


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'States (Australia)');
    }

    /**
     * @inheritDoc
     */
    public static function getLabelOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Name'), 'value' => 'name'],
            ['label' => Craft::t('formie', 'Short Name'), 'value' => 'short'],
        ];
    }

    /**
     * @inheritDoc
     */
    public static function getValueOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Name'), 'value' => 'name'],
            ['label' => Craft::t('formie', 'Short Name'), 'value' => 'short'],
        ];
    }

    /**
     * @inheritDoc
     */
    public static function getDataOptions(): array
    {
        return [
            [
                'name' => Craft::t('formie', 'Australian Capital Territory'),
                'short' => 'ACT',
            ],
            [
                'name' => Craft::t('formie', 'New South Wales'),
                'short' => 'NSW',
            ],
            [
                'name' => Craft::t('formie', 'Northern Territory'),
                'short' => 'NT',
            ],
            [
                'name' => Craft::t('formie', 'Queensland'),
                'short' => 'QLD',
            ],
            [
                'name' => Craft::t('formie', 'South Australia'),
                'short' => 'SA',
            ],
            [
                'name' => Craft::t('formie', 'Tasmania'),
                'short' => 'TAS',
            ],
            [
                'name' => Craft::t('formie', 'Victoria'),
                'short' => 'VIC',
            ],
            [
                'name' => Craft::t('formie', 'Western Australia'),
                'short' => 'WA',
            ],
        ];
    }
}
