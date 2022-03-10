<?php
namespace verbb\formie\options;

use Craft;
use verbb\formie\base\PredefinedOption;

class Size extends PredefinedOption
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
        return Craft::t('formie', 'Size');
    }

    public static function getLabelOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Name'), 'value' => 'name'],
            ['label' => Craft::t('formie', 'Code'), 'value' => 'code'],
        ];
    }

    public static function getValueOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Name'), 'value' => 'name'],
            ['label' => Craft::t('formie', 'Code'), 'value' => 'code'],
        ];
    }

    public static function getDataOptions(): array
    {
        return [
            [
                'name' => Craft::t('formie', 'Extra Extra Small'),
                'code' => 'XXS',
            ],
            [
                'name' => Craft::t('formie', 'Extra Small'),
                'code' => 'XS',
            ],
            [
                'name' => Craft::t('formie', 'Small'),
                'code' => 'S',
            ],
            [
                'name' => Craft::t('formie', 'Medium'),
                'code' => 'M',
            ],
            [
                'name' => Craft::t('formie', 'Large'),
                'code' => 'L',
            ],
            [
                'name' => Craft::t('formie', 'Extra Large'),
                'code' => 'XL',
            ],
            [
                'name' => Craft::t('formie', 'Extra Extra Large'),
                'code' => 'XXL',
            ],
        ];
    }
}
