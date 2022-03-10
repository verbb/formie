<?php
namespace verbb\formie\options;

use Craft;
use verbb\formie\base\PredefinedOption;

class Months extends PredefinedOption
{
    // Protected Properties
    // =========================================================================

    public static ?string $defaultLabelOption = 'month';
    public static ?string $defaultValueOption = 'month';


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Months of the Year');
    }

    public static function getLabelOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Month'), 'value' => 'month'],
            ['label' => Craft::t('formie', 'Short Month'), 'value' => 'short'],
            ['label' => Craft::t('formie', 'Number'), 'value' => 'number'],
        ];
    }

    public static function getValueOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Month'), 'value' => 'month'],
            ['label' => Craft::t('formie', 'Short Month'), 'value' => 'short'],
            ['label' => Craft::t('formie', 'Number'), 'value' => 'number'],
        ];
    }

    public static function getDataOptions(): array
    {
        return [
            [
                'month' => Craft::t('formie', 'January'),
                'short' => Craft::t('formie', 'Jan'),
                'number' => '1',
            ],
            [
                'month' => Craft::t('formie', 'February'),
                'short' => Craft::t('formie', 'Feb'),
                'number' => '2',
            ],
            [
                'month' => Craft::t('formie', 'March'),
                'short' => Craft::t('formie', 'Mar'),
                'number' => '3',
            ],
            [
                'month' => Craft::t('formie', 'April'),
                'short' => Craft::t('formie', 'Apr'),
                'number' => '4',
            ],
            [
                'month' => Craft::t('formie', 'May'),
                'short' => Craft::t('formie', 'May'),
                'number' => '5',
            ],
            [
                'month' => Craft::t('formie', 'June'),
                'short' => Craft::t('formie', 'Jun'),
                'number' => '6',
            ],
            [
                'month' => Craft::t('formie', 'July'),
                'short' => Craft::t('formie', 'Jul'),
                'number' => '7',
            ],
            [
                'month' => Craft::t('formie', 'August'),
                'short' => Craft::t('formie', 'Aug'),
                'number' => '8',
            ],
            [
                'month' => Craft::t('formie', 'September'),
                'short' => Craft::t('formie', 'Sep'),
                'number' => '9',
            ],
            [
                'month' => Craft::t('formie', 'October'),
                'short' => Craft::t('formie', 'Oct'),
                'number' => '10',
            ],
            [
                'month' => Craft::t('formie', 'November'),
                'short' => Craft::t('formie', 'Nov'),
                'number' => '11',
            ],
            [
                'month' => Craft::t('formie', 'December'),
                'short' => Craft::t('formie', 'Dec'),
                'number' => '12',
            ],
        ];
    }
}
