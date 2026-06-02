<?php
namespace verbb\formie\fields\values;

class EmailFieldValue extends StringFieldValue
{
    // Static Methods
    // =========================================================================

    public static function capabilityTypes(): array
    {
        return ['string', 'email'];
    }
}
