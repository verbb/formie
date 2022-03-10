<?php
namespace verbb\formie\options;

use Craft;
use verbb\formie\base\PredefinedOption;

class Days extends PredefinedOption
{
    // Protected Properties
    // =========================================================================

    public static ?string $defaultLabelOption = 'day';
    public static ?string $defaultValueOption = 'day';


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Days of the Week');
    }

    public static function getLabelOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Day'), 'value' => 'day'],
            ['label' => Craft::t('formie', 'Short Day'), 'value' => 'short'],
            ['label' => Craft::t('formie', 'Number'), 'value' => 'number'],
        ];
    }

    public static function getValueOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Day'), 'value' => 'day'],
            ['label' => Craft::t('formie', 'Short Day'), 'value' => 'short'],
            ['label' => Craft::t('formie', 'Number'), 'value' => 'number'],
        ];
    }

    public static function getDataOptions(): array
    {
        return [
            [
                'day' => Craft::t('formie', 'Sunday'),
                'short' => Craft::t('formie', 'Sun'),
                'number' => '1',
            ],
            [
                'day' => Craft::t('formie', 'Monday'),
                'short' => Craft::t('formie', 'Mon'),
                'number' => '2',
            ],
            [
                'day' => Craft::t('formie', 'Tuesday'),
                'short' => Craft::t('formie', 'Tue'),
                'number' => '3',
            ],
            [
                'day' => Craft::t('formie', 'Wednesday'),
                'short' => Craft::t('formie', 'Wed'),
                'number' => '4',
            ],
            [
                'day' => Craft::t('formie', 'Thursday'),
                'short' => Craft::t('formie', 'Thu'),
                'number' => '5',
            ],
            [
                'day' => Craft::t('formie', 'Friday'),
                'short' => Craft::t('formie', 'Fri'),
                'number' => '6',
            ],
            [
                'day' => Craft::t('formie', 'Saturday'),
                'short' => Craft::t('formie', 'Sat'),
                'number' => '7',
            ],
        ];
    }
}
