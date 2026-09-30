<?php
namespace verbb\formie\fields\values;

class PhoneFieldValue extends BaseFieldValue
{
    // Properties
    // =========================================================================

    protected readonly string $number;
    protected readonly ?string $country;
    protected readonly ?string $canonicalNumber;
    protected readonly ?string $countryCode;


    // Public Methods
    // =========================================================================

    public function __construct(string $number = '', ?string $country = null, ?string $canonicalNumber = null, ?string $countryCode = null)
    {
        $this->number = $number;
        $this->country = $country;
        $this->canonicalNumber = $canonicalNumber;
        $this->countryCode = $countryCode;
    }

    public function __toString(): string
    {
        return $this->canonicalNumber ?? $this->number;
    }

    public function isEmpty(): bool
    {
        return $this->number === '';
    }

    public function toArray(): array
    {
        return [
            'number' => $this->number,
            'country' => $this->country,
            'canonicalNumber' => $this->canonicalNumber,
            'countryCode' => $this->countryCode,
        ];
    }
}
