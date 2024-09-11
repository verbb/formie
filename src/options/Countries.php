<?php
namespace verbb\formie\options;

use verbb\formie\base\PredefinedOption;

use Craft;

use CommerceGuys\Addressing\Country\CountryRepository;

class Countries extends PredefinedOption
{
    // Protected Properties
    // =========================================================================

    public static ?string $defaultLabelOption = 'name';
    public static ?string $defaultValueOption = 'name';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Countries');
    }

    public static function getLabelOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Name'), 'value' => 'name'],
            ['label' => Craft::t('formie', '2-Letter Code'), 'value' => '2-letter'],
            ['label' => Craft::t('formie', '3-Letter Code'), 'value' => '3-letter'],
        ];
    }

    public static function getValueOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Name'), 'value' => 'name'],
            ['label' => Craft::t('formie', '2-Letter Code'), 'value' => '2-letter'],
            ['label' => Craft::t('formie', '3-Letter Code'), 'value' => '3-letter'],
        ];
    }

    public static function getDataOptions(): array
    {
        $locale = Craft::$app->getLocale()->getLanguageID();

        $countryRepository = new CountryRepository($locale);

        $countries = [];

        foreach ($countryRepository->getAll() as $country) {
            $countries[] = [
                'name' => $country->getName(),
                '2-letter' => $country->getCountryCode(),
                '3-letter' => $country->getThreeLetterCode(),
            ];
        }

        return $countries;
    }
}
