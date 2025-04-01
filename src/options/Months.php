<?php
namespace verbb\formie\options;

use verbb\formie\base\PredefinedOption;

use Craft;
use craft\i18n\Locale;

class Months extends PredefinedOption
{
    // Protected Properties
    // =========================================================================

    public static ?string $defaultLabelOption = 'month';
    public static ?string $defaultValueOption = 'month';


    // Static Methods
    // =========================================================================

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
        $locale = Craft::$app->getLocale();

        $monthNames = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthNames[] = [
                'month' => $locale->getMonthName($month, Locale::LENGTH_FULL),
                'short' => str_replace('.', '', $locale->getMonthName($month, Locale::LENGTH_MEDIUM)),
                'number' => $month,
            ];
        }

        return $monthNames;
    }
}
