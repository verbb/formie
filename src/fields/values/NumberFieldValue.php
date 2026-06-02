<?php
namespace verbb\formie\fields\values;

class NumberFieldValue extends BaseFieldValue
{
    // Static Methods
    // =========================================================================

    public static function capabilityTypes(): array
    {
        return ['string', 'number'];
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

    public string|int|float|null $value = null;


    // Public Methods
    // =========================================================================

    public function isEmpty(): bool
    {
        return $this->value === null || $this->value === '';
    }
}
