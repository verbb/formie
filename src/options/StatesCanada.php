<?php
namespace verbb\formie\options;

use verbb\formie\base\PredefinedOption;

use Craft;

use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;

class StatesCanada extends PredefinedOption
{
    // Protected Properties
    // =========================================================================

    public static ?string $defaultLabelOption = 'name';
    public static ?string $defaultValueOption = 'name';


    // Static Methods
    // =========================================================================

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
        $locale = Craft::$app->getLocale()->getLanguageID();

        $subdivisionRepository = new SubdivisionRepository();

        $states = [];

        foreach ($subdivisionRepository->getAll(['CA']) as $state) {
            $states[] = [
                'name' => $state->getName(),
                'short' => $state->getCode(),
            ];
        }

        return $states;
    }
}
