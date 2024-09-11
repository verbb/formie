<?php
namespace verbb\formie\options;

use verbb\formie\base\PredefinedOption;

use Craft;
use craft\i18n\Locale;

class Days extends PredefinedOption
{
    // Protected Properties
    // =========================================================================

    public static ?string $defaultLabelOption = 'day';
    public static ?string $defaultValueOption = 'day';


    // Static Methods
    // =========================================================================

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
        $locale = Craft::$app->getLocale();

        $weekDayNames = [];

        for ($day = 0; $day <= 6; $day++) {
            $weekDayNames[] = [
                'day' => $locale->getWeekDayName($day, Locale::LENGTH_FULL),
                'short' => str_replace('.', '', $locale->getWeekDayName($day, Locale::LENGTH_MEDIUM)),
                'number' => $day + 1,
            ];
        }

        return $weekDayNames;
    }
}
