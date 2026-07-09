<?php
namespace verbb\formie\helpers;

class FieldOptionHelper
{
    public static function isOptionDefault(array $option): bool
    {
        return !empty($option['default']) || !empty($option['isDefault']);
    }

    public static function normalizeOptionRow(array $option): array
    {
        if (isset($option['optgroup'])) {
            return $option;
        }

        if (array_key_exists('isDefault', $option)) {
            if (!array_key_exists('default', $option)) {
                $option['default'] = (bool)$option['isDefault'];
            }

            unset($option['isDefault']);
        }

        return $option;
    }

    /**
     * @param array<int, mixed> $options
     * @return array<int, mixed>
     */
    public static function normalizeOptionRows(array $options): array
    {
        $normalized = [];

        foreach ($options as $option) {
            $normalized[] = is_array($option) ? self::normalizeOptionRow($option) : $option;
        }

        return $normalized;
    }

    /**
     * Repair legacy recipient dropdown placeholders and conflicting default flags.
     *
     * @param array<int, mixed> $options
     * @return array<int, mixed>
     */
    public static function sanitizeRecipientPlaceholderOptions(array $options): array
    {
        $options = self::normalizeOptionRows($options);
        $hadLegacyPlaceholder = false;

        foreach ($options as $index => &$option) {
            if (!is_array($option) || isset($option['optgroup'])) {
                continue;
            }

            $label = trim((string)($option['label'] ?? ''));
            $value = trim((string)($option['value'] ?? ''));

            // Legacy exports often used the placeholder label as the option value.
            if ($index === 0 && $label !== '' && !str_contains($label, '@') && ($value === '' || $label === $value)) {
                $hadLegacyPlaceholder = true;
                $option['value'] = '';
                $option['default'] = false;
            }
        }
        unset($option);

        if ($hadLegacyPlaceholder) {
            foreach ($options as &$option) {
                if (is_array($option) && !isset($option['optgroup'])) {
                    $option['default'] = false;
                }
            }
            unset($option);

            return $options;
        }

        $defaultIndexes = [];

        foreach ($options as $index => $option) {
            if (!is_array($option) || isset($option['optgroup'])) {
                continue;
            }

            if (!empty($option['default'])) {
                $defaultIndexes[] = $index;
            }
        }

        if (count($defaultIndexes) <= 1) {
            return $options;
        }

        foreach ($defaultIndexes as $index) {
            $options[$index]['default'] = false;
        }

        return $options;
    }
}
