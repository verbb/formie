<?php
namespace verbb\formie\models;

use craft\base\Model;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;

class Address extends Model
{
    // Properties
    // =========================================================================

    public ?string $autocomplete = null;
    public ?string $address1 = null;
    public ?string $address2 = null;
    public ?string $address3 = null;
    public ?string $city = null;
    public ?string $state = null;
    public ?string $zip = null;
    public ?string $country = null;


    // Public Methods
    // =========================================================================

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->autocomplete) {
            return (string)$this->autocomplete;
        }

        $address = ArrayHelper::filterEmptyStringsFromArray([
            StringHelper::trim($this->address1 ?? ''),
            StringHelper::trim($this->address2 ?? ''),
            StringHelper::trim($this->address3 ?? ''),
            StringHelper::trim($this->city ?? ''),
            StringHelper::trim($this->state ?? ''),
            StringHelper::trim($this->zip ?? ''),
            StringHelper::trim($this->country ?? ''),
        ]);

        return implode(', ', $address);
    }

}
