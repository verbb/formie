<?php
namespace verbb\formie\helpers;

use craft\helpers\ArrayHelper as CraftArrayHelper;

use InvalidArgumentException;

class FieldAttributesHelper
{
    public const SETTING_CONTAINER = 'containerAttributes';
    public const SETTING_INPUT = 'inputAttributes';
    public const MERGE_SETTING_CONTAINER = 'mergeContainerAttributes';
    public const MERGE_SETTING_INPUT = 'mergeInputAttributes';

    // Static Methods
    // =========================================================================

    public static function applyToFieldSettings(
        array $settings,
        ?array $existingContainerAttributes = null,
        ?array $existingInputAttributes = null,
        bool $throwOnInvalid = true,
    ): array {
        if (array_key_exists(self::MERGE_SETTING_CONTAINER, $settings)) {
            $settings[self::SETTING_CONTAINER] = self::merge(
                $existingContainerAttributes,
                $settings[self::MERGE_SETTING_CONTAINER],
                self::SETTING_CONTAINER,
                $throwOnInvalid,
            );
            unset($settings[self::MERGE_SETTING_CONTAINER]);
        }

        if (array_key_exists(self::MERGE_SETTING_INPUT, $settings)) {
            $settings[self::SETTING_INPUT] = self::merge(
                $existingInputAttributes,
                $settings[self::MERGE_SETTING_INPUT],
                self::SETTING_INPUT,
                $throwOnInvalid,
            );
            unset($settings[self::MERGE_SETTING_INPUT]);
        }

        foreach ([self::SETTING_CONTAINER, self::SETTING_INPUT] as $setting) {
            if (!array_key_exists($setting, $settings)) {
                continue;
            }

            $settings[$setting] = self::normalize($settings[$setting], $setting, $throwOnInvalid);
        }

        return $settings;
    }

    public static function normalize(mixed $attributes, string $settingName, bool $throwOnInvalid = true): array
    {
        if ($attributes === null) {
            return [];
        }

        if (!is_array($attributes)) {
            if ($throwOnInvalid) {
                throw new InvalidArgumentException(self::_invalidFormatMessage($settingName));
            }

            return [];
        }

        if ($attributes === []) {
            return [];
        }

        if (self::isCraftFormat($attributes)) {
            return self::mapToTable(self::flattenNormalizedAttributes(Html::normalizeTagAttributes($attributes)));
        }

        if (self::isTableFormat($attributes)) {
            return self::normalizeTableFormat($attributes, $settingName, $throwOnInvalid);
        }

        if ($throwOnInvalid) {
            throw new InvalidArgumentException(self::_invalidFormatMessage($settingName));
        }

        return [];
    }

    public static function merge(
        ?array $existingTable,
        array $incoming,
        string $settingName,
        bool $throwOnInvalid = true,
    ): array {
        $existingMap = self::flattenNormalizedAttributes(
            Html::normalizeTagAttributes(self::tableToMap(self::normalize($existingTable, $settingName, false))),
        );

        if (self::isCraftFormat($incoming)) {
            $incomingMap = self::flattenNormalizedAttributes(Html::normalizeTagAttributes($incoming));
        } else {
            $incomingMap = self::flattenNormalizedAttributes(
                Html::normalizeTagAttributes(self::tableToMap(self::normalize($incoming, $settingName, $throwOnInvalid))),
            );
        }

        return self::mapToTable(self::flattenNormalizedAttributes(Html::mergeAttributes($existingMap, $incomingMap)));
    }

    public static function isCraftFormat(array $attributes): bool
    {
        return CraftArrayHelper::isAssociative($attributes);
    }

    public static function isTableFormat(array $attributes): bool
    {
        if (CraftArrayHelper::isAssociative($attributes)) {
            return false;
        }

        foreach ($attributes as $item) {
            if (!is_array($item) || !array_key_exists('label', $item)) {
                return false;
            }
        }

        return true;
    }

    public static function tableToMap(?array $table): array
    {
        if (!$table) {
            return [];
        }

        return ArrayHelper::map($table, 'label', 'value');
    }

    public static function mapToTable(array $map): array
    {
        $rows = [];

        foreach ($map as $label => $value) {
            $rows[] = [
                'label' => (string)$label,
                'value' => $value,
            ];
        }

        return $rows;
    }

    public static function flattenNormalizedAttributes(array $attributes): array
    {
        $flat = [];

        foreach ($attributes as $name => $value) {
            if ($value === false || $value === null) {
                continue;
            }

            if (is_array($value) && in_array($name, ['data', 'aria', 'data-hx', 'data-ng'], true)) {
                foreach ($value as $nestedName => $nestedValue) {
                    if ($nestedValue === false || $nestedValue === null) {
                        continue;
                    }

                    $flat["$name-$nestedName"] = $nestedValue;
                }

                continue;
            }

            if (is_array($value) && $value === []) {
                continue;
            }

            $flat[$name] = $value;
        }

        return $flat;
    }


    // Private Methods
    // =========================================================================

    private static function normalizeTableFormat(array $attributes, string $settingName, bool $throwOnInvalid): array
    {
        $rows = [];

        foreach ($attributes as $item) {
            if (!is_array($item) || !array_key_exists('label', $item)) {
                if ($throwOnInvalid) {
                    throw new InvalidArgumentException(self::_invalidFormatMessage($settingName));
                }

                continue;
            }

            $label = trim((string)$item['label']);

            if ($label === '') {
                if ($throwOnInvalid) {
                    throw new InvalidArgumentException(self::_invalidFormatMessage($settingName));
                }

                continue;
            }

            $rows[] = [
                'label' => $label,
                'value' => $item['value'] ?? null,
            ];
        }

        return $rows;
    }

    private static function _invalidFormatMessage(string $settingName): string
    {
        return sprintf(
            'Invalid %s format. Use a Craft-style attribute map such as `{ readonly: true, data: { foo: "bar" } }`, or the editable-table format `[{ label: "readonly", value: true }]`.',
            $settingName,
        );
    }
}
