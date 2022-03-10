<?php
namespace verbb\formie\options;

use Craft;
use verbb\formie\base\PredefinedOption;

class StatesUsa extends PredefinedOption
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
        return Craft::t('formie', 'States (USA)');
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
                'name' => Craft::t('formie', 'Alabama'),
                'short' => 'AL',
            ],
            [
                'name' => Craft::t('formie', 'Alaska'),
                'short' => 'AK',
            ],
            [
                'name' => Craft::t('formie', 'Arizona'),
                'short' => 'AZ',
            ],
            [
                'name' => Craft::t('formie', 'Arkansas'),
                'short' => 'AR',
            ],
            [
                'name' => Craft::t('formie', 'California'),
                'short' => 'CA',
            ],
            [
                'name' => Craft::t('formie', 'Colorado'),
                'short' => 'CO',
            ],
            [
                'name' => Craft::t('formie', 'Connecticut'),
                'short' => 'CT',
            ],
            [
                'name' => Craft::t('formie', 'Delaware'),
                'short' => 'DE',
            ],
            [
                'name' => Craft::t('formie', 'Florida'),
                'short' => 'FL',
            ],
            [
                'name' => Craft::t('formie', 'Georgia'),
                'short' => 'GA',
            ],
            [
                'name' => Craft::t('formie', 'Hawaii'),
                'short' => 'HI',
            ],
            [
                'name' => Craft::t('formie', 'Idaho'),
                'short' => 'ID',
            ],
            [
                'name' => Craft::t('formie', 'Illinois'),
                'short' => 'IL',
            ],
            [
                'name' => Craft::t('formie', 'Indiana'),
                'short' => 'IN',
            ],
            [
                'name' => Craft::t('formie', 'Iowa'),
                'short' => 'IA',
            ],
            [
                'name' => Craft::t('formie', 'Kansas'),
                'short' => 'KS',
            ],
            [
                'name' => Craft::t('formie', 'Kentucky'),
                'short' => 'KY',
            ],
            [
                'name' => Craft::t('formie', 'Louisiana'),
                'short' => 'LA',
            ],
            [
                'name' => Craft::t('formie', 'Maine'),
                'short' => 'ME',
            ],
            [
                'name' => Craft::t('formie', 'Maryland'),
                'short' => 'MD',
            ],
            [
                'name' => Craft::t('formie', 'Massachusetts'),
                'short' => 'MA',
            ],
            [
                'name' => Craft::t('formie', 'Michigan'),
                'short' => 'MI',
            ],
            [
                'name' => Craft::t('formie', 'Minnesota'),
                'short' => 'MN',
            ],
            [
                'name' => Craft::t('formie', 'Mississippi'),
                'short' => 'MS',
            ],
            [
                'name' => Craft::t('formie', 'Missouri'),
                'short' => 'MO',
            ],
            [
                'name' => Craft::t('formie', 'Montana'),
                'short' => 'MT',
            ],
            [
                'name' => Craft::t('formie', 'Nebraska'),
                'short' => 'NE',
            ],
            [
                'name' => Craft::t('formie', 'Nevada'),
                'short' => 'NV',
            ],
            [
                'name' => Craft::t('formie', 'New Hampshire'),
                'short' => 'NH',
            ],
            [
                'name' => Craft::t('formie', 'New Jersey'),
                'short' => 'NJ',
            ],
            [
                'name' => Craft::t('formie', 'New Mexico'),
                'short' => 'NM',
            ],
            [
                'name' => Craft::t('formie', 'New York'),
                'short' => 'NY',
            ],
            [
                'name' => Craft::t('formie', 'North Carolina'),
                'short' => 'NC',
            ],
            [
                'name' => Craft::t('formie', 'North Dakota'),
                'short' => 'ND',
            ],
            [
                'name' => Craft::t('formie', 'Ohio'),
                'short' => 'OH',
            ],
            [
                'name' => Craft::t('formie', 'Oklahoma'),
                'short' => 'OK',
            ],
            [
                'name' => Craft::t('formie', 'Oregon'),
                'short' => 'OR',
            ],
            [
                'name' => Craft::t('formie', 'Pennsylvania'),
                'short' => 'PA',
            ],
            [
                'name' => Craft::t('formie', 'Rhode Island'),
                'short' => 'RI',
            ],
            [
                'name' => Craft::t('formie', 'South Carolina'),
                'short' => 'SC',
            ],
            [
                'name' => Craft::t('formie', 'South Dakota'),
                'short' => 'SD',
            ],
            [
                'name' => Craft::t('formie', 'Tennessee'),
                'short' => 'TN',
            ],
            [
                'name' => Craft::t('formie', 'Texas'),
                'short' => 'TX',
            ],
            [
                'name' => Craft::t('formie', 'Utah'),
                'short' => 'UT',
            ],
            [
                'name' => Craft::t('formie', 'Vermont'),
                'short' => 'VT',
            ],
            [
                'name' => Craft::t('formie', 'Virginia'),
                'short' => 'VA',
            ],
            [
                'name' => Craft::t('formie', 'Washington'),
                'short' => 'WA',
            ],
            [
                'name' => Craft::t('formie', 'West Virginia'),
                'short' => 'WV',
            ],
            [
                'name' => Craft::t('formie', 'Wisconsin'),
                'short' => 'WI',
            ],
            [
                'name' => Craft::t('formie', 'Wyoming'),
                'short' => 'WY',
            ],
        ];
    }
}
