<?php
namespace verbb\formie\models;

use craft\base\Model;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;

class Address extends Model
{
    // Properties
    // =========================================================================

    public $autocomplete;
    public $address1;
    public $address2;
    public $address3;
    public $city;
    public $state;
    public $zip;
    public $country;


    // Public Methods
    // =========================================================================

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->autocomplete) {
            return $this->autocomplete;
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
