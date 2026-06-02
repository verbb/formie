<?php
namespace verbb\formie\fields\values;

class StringFieldValue extends BaseFieldValue
{
    // Static Methods
    // =========================================================================

    public static function capabilityTypes(): array
    {
        return ['string'];
    }
    

    // Properties
    // =========================================================================

    public ?string $value = null;


    // Public Methods
    // =========================================================================

    public function isEmpty(): bool
    {
        return $this->value === null || $this->value === '';
    }
}
