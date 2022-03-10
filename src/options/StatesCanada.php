<?php
namespace verbb\formie\options;

use Craft;
use verbb\formie\base\PredefinedOption;

class StatesCanada extends PredefinedOption
{
    // Protected Properties
    // =========================================================================

    public static ?string $defaultLabelOption = 'name';
    public static ?string $defaultValueOption = 'name';


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'States (Canada)');
    }

    public static function getLabelOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Name'), 'value' => 'name'],
            ['label' => Craft::t('formie', 'Short Name'), 'value' => 'short'],
        ];
    }

    public static function getValueOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Name'), 'value' => 'name'],
            ['label' => Craft::t('formie', 'Short Name'), 'value' => 'short'],
        ];
    }

    public static function getDataOptions(): array
    {
        return [
            [
                'name' => Craft::t('formie', 'Alberta'),
                'short' => 'AB',
            ],
            [
                'name' => Craft::t('formie', 'British Columbia'),
                'short' => 'BC',
            ],
            [
                'name' => Craft::t('formie', 'Manitoba'),
                'short' => 'MB',
            ],
            [
                'name' => Craft::t('formie', 'New Brunswick'),
                'short' => 'NB',
            ],
            [
                'name' => Craft::t('formie', 'Newfoundland and Labrador'),
                'short' => 'NL',
            ],
            [
                'name' => Craft::t('formie', 'Northwest Territories'),
                'short' => 'NT',
            ],
            [
                'name' => Craft::t('formie', 'Nova Scotia'),
                'short' => 'NS',
            ],
            [
                'name' => Craft::t('formie', 'Nunavut'),
                'short' => 'NU',
            ],
            [
                'name' => Craft::t('formie', 'Ontario'),
                'short' => 'ON',
            ],
            [
                'name' => Craft::t('formie', 'Prince Edward Island'),
                'short' => 'PE',
            ],
            [
                'name' => Craft::t('formie', 'Quebec'),
                'short' => 'QC',
            ],
            [
                'name' => Craft::t('formie', 'Saskatchewan'),
                'short' => 'SK',
            ],
            [
                'name' => Craft::t('formie', 'Yukon'),
                'short' => 'YT',
            ],
        ];
    }
}
