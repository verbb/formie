<?php
namespace verbb\formie\fields\values;

class BooleanFieldValue extends BaseFieldValue
{
    // Static Methods
    // =========================================================================

    public static function capabilityTypes(): array
    {
        return ['boolean'];
    }

    public static function toClientValueFrom(mixed $value): mixed
    {
        if ($value instanceof static) {
            return $value->toClientValue();
        }

        return $value;
    }


    // Properties
    // =========================================================================

    public bool $value = false;
    

    // Public Methods
    // =========================================================================

    public function isEmpty(): bool
    {
        return !$this->value;
    }

    public function toClientValue(): mixed
    {
        return $this->value;
    }
}
