<?php
namespace verbb\formie\fields\values;

class CalculationFieldValue extends BaseFieldValue
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

    public mixed $value = null;


    // Public Methods
    // =========================================================================

    public function isEmpty(): bool
    {
        return $this->value === null || $this->value === '';
    }
}
