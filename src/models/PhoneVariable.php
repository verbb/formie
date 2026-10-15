<?php
namespace verbb\formie\models;

use verbb\formie\base\FieldValueInterface;

use craft\base\Model;

class PhoneVariable extends Model implements FieldValueInterface
{
    // Properties
    // =========================================================================

    public ?string $country = null;
    public ?string $countryCode = null;
    public ?string $countryName = null;
    public ?string $number = null;

    private string $_formattedValue;


    // Public Methods
    // =========================================================================

    public function __construct(string $formattedValue, ?string $country = null, ?string $countryCode = null, ?string $countryName = null, ?string $number = null, array $config = [])
    {
        $this->_formattedValue = $formattedValue;
        $this->country = $country;
        $this->countryCode = $countryCode;
        $this->countryName = $countryName;
        $this->number = $number;

        parent::__construct($config);
    }

    public function __toString(): string
    {
        return $this->_formattedValue;
    }
}
