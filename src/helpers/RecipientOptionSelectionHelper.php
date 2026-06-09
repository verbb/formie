<?php
namespace verbb\formie\helpers;

use verbb\formie\fields\values\MultiOptionFieldValue;
use verbb\formie\fields\values\OptionValue;
use verbb\formie\fields\values\SingleOptionFieldValue;

class RecipientOptionSelectionHelper
{
    // Static Methods
    // =========================================================================

    public static function optionRows(array $options): array
    {
        $rows = [];

        foreach ($options as $key => $option) {
            if (!is_array($option) || isset($option['optgroup'])) {
                continue;
            }

            $option = [
                ...$option,
                'label' => (string)($option['label'] ?? ''),
                'value' => (string)($option['value'] ?? ''),
            ];
            $option['id'] = RecipientTokenHelper::optionId($option, $key);

            $rows[] = $option;
        }

        return $rows;
    }

    public static function normalizeSelections(mixed $value): array
    {
        $selections = [];

        if ($value instanceof MultiOptionFieldValue) {
            foreach ($value as $option) {
                if ($option instanceof OptionValue) {
                    $selections[] = self::selectionFromValue($option->value, $option->label);
                }
            }
        } else if ($value instanceof SingleOptionFieldValue) {
            $selections[] = self::selectionFromValue($value->value ?? '', $value->label ?? null);
        } else if ($value instanceof OptionValue) {
            $selections[] = self::selectionFromValue($value->value ?? '', $value->label ?? null);
        } else if (is_array($value)) {
            if (array_key_exists('value', $value) && !array_is_list($value)) {
                $selections[] = self::selectionFromValue(
                    $value['value'] ?? '',
                    array_key_exists('label', $value) ? $value['label'] : null,
                    $value['id'] ?? null,
                );
            } else {
                foreach ($value as $val) {
                    array_push($selections, ...self::normalizeSelections($val));
                }
            }
        } else if (is_scalar($value) || $value === null) {
            $selections[] = self::selectionFromValue($value);
        }

        return array_values(array_filter(
            $selections,
            static fn(array $selection): bool => $selection['value'] !== '',
        ));
    }

    public static function selectionFromValue(mixed $value, mixed $label = null, mixed $id = null): array
    {
        if (is_string($value) && str_starts_with($value, 'base64:')) {
            $payload = RecipientTokenHelper::decodePayload($value);

            if (($payload['type'] ?? null) === RecipientTokenHelper::TYPE_OPTION) {
                return [
                    'id' => isset($payload['id']) ? (string)$payload['id'] : null,
                    'label' => isset($payload['label']) ? (string)$payload['label'] : null,
                    'value' => (string)($payload['value'] ?? ''),
                    'token' => true,
                ];
            }

            $value = RecipientTokenHelper::decode($value);
        }

        if (is_array($value)) {
            $value = implode(',', array_filter($value));
        }

        return [
            'id' => $id !== null && $id !== '' ? (string)$id : null,
            'label' => $label !== null && $label !== '' ? (string)$label : null,
            'value' => (string)$value,
            'token' => false,
        ];
    }

    public static function isOptionSelected(array $option, array $selections): bool
    {
        foreach ($selections as $selection) {
            if (($selection['id'] ?? null) && $selection['id'] === $option['id']) {
                return true;
            }

            if (($selection['id'] ?? null) === null && $selection['value'] === $option['value']) {
                return true;
            }
        }

        return false;
    }

    public static function resolveSelectionOption(?array $selection, array $optionsById, array $optionsByValue): ?array
    {
        if (!$selection) {
            return null;
        }

        $id = $selection['id'] ?? null;

        if ($id && isset($optionsById[$id])) {
            return $optionsById[$id];
        }

        return $optionsByValue[$selection['value']] ?? null;
    }
}
