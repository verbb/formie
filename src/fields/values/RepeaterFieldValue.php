<?php
namespace verbb\formie\fields\values;

class RepeaterFieldValue extends BaseFieldValue
{
    // Static Methods
    // =========================================================================

    public static function capabilityTypes(): array
    {
        return ['array'];
    }

    public static function toClientValueFrom(mixed $value): mixed
    {
        if ($value instanceof static) {
            return $value->toClientValue();
        }

        if (is_array($value) && array_key_exists('rows', $value) && is_array($value['rows'])) {
            return array_values($value['rows']);
        }

        return is_array($value) ? array_values($value) : $value;
    }


    // Properties
    // =========================================================================

    public array $rows = [];


    // Public Methods
    // =========================================================================

    public function isEmpty(): bool
    {
        return $this->rows === [];
    }

    public function toClientValue(): mixed
    {
        return $this->rows;
    }
}
