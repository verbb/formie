<?php
namespace verbb\formie\fields\values;

use craft\elements\db\ElementQueryInterface;

class ElementFieldValue extends BaseFieldValue
{
    // Static Methods
    // =========================================================================

    public static function capabilityTypes(): array
    {
        return ['element'];
    }

    public static function toClientValueFrom(mixed $value): mixed
    {
        if ($value instanceof static) {
            return $value->toClientValue();
        }

        if ($value instanceof SingleOptionFieldValue || $value instanceof OptionValue) {
            return $value->value !== null && $value->value !== '' ? [$value->value] : [];
        }

        if ($value instanceof MultiOptionFieldValue) {
            return $value->values();
        }

        if ($value instanceof ElementQueryInterface) {
            return array_values(array_filter($value->ids(), static fn(mixed $id): bool => $id !== null && $id !== ''));
        }

        if (!is_array($value)) {
            return $value;
        }

        if (isset($value[0]) && is_array($value[0]) && array_key_exists('id', $value[0])) {
            $value = array_column($value, 'id');
        }

        return array_values(array_filter(array_map(static function(mixed $item): mixed {
            if ($item instanceof OptionValue) {
                return $item->value;
            }

            if (is_array($item) && array_key_exists('id', $item)) {
                return $item['id'];
            }

            return $item;
        }, $value), static fn(mixed $item): bool => $item !== null && $item !== ''));
    }


    // Properties
    // =========================================================================

    public array $elementIds = [];


    // Public Methods
    // =========================================================================

    public function isEmpty(): bool
    {
        return $this->elementIds === [];
    }

    public function toClientValue(): mixed
    {
        return $this->elementIds;
    }
}
