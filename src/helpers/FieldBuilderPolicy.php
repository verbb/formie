<?php
namespace verbb\formie\helpers;

use verbb\formie\base\ElementField;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\Phone;
use verbb\formie\Formie;

use Craft;

class FieldBuilderPolicy
{
    // Static Methods
    // =========================================================================

    public static function settingsKeys(): array
    {
        return [
            'allowPublicVolumes',
            'allowMultiSelectDropdowns',
            'allowPhoneCountrySelector',
        ];
    }

    public static function allowPublicVolumes(): bool
    {
        return (bool)(Formie::$plugin?->getSettings()->allowPublicVolumes ?? true);
    }

    public static function allowMultiSelectDropdowns(): bool
    {
        return (bool)(Formie::$plugin?->getSettings()->allowMultiSelectDropdowns ?? true);
    }

    public static function allowPhoneCountrySelector(): bool
    {
        return (bool)(Formie::$plugin?->getSettings()->allowPhoneCountrySelector ?? true);
    }

    public static function applyToFieldConfig(array &$config, string $fieldClass): void
    {
        if ($fieldClass === Phone::class && !self::allowPhoneCountrySelector()) {
            $config['countryEnabled'] = false;
        }

        if (self::allowMultiSelectDropdowns()) {
            return;
        }

        if ($fieldClass === Dropdown::class || is_subclass_of($fieldClass, ElementField::class)) {
            $config['multi'] = false;
        }
    }

    public static function multiSelectDropdownSchema(array $config = []): array
    {
        if (!self::allowMultiSelectDropdowns()) {
            return [];
        }

        return [
            SchemaHelper::lightswitchField(array_merge([
                'label' => Craft::t('formie', 'Allow Multiple'),
                'instructions' => Craft::t('formie', 'Whether this field should allow multiple options to be selected.'),
                'name' => 'multi',
            ], $config)),
        ];
    }

    public static function phoneCountrySelectorSchema(array $config = []): array
    {
        if (!self::allowPhoneCountrySelector()) {
            return [];
        }

        return [
            SchemaHelper::lightswitchField(array_merge([
                'label' => Craft::t('formie', 'Country Enabled'),
                'instructions' => Craft::t('formie', 'Whether to show the dial code on the country dropdown.'),
                'name' => 'countryEnabled',
            ], $config)),
        ];
    }
}
