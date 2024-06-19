<?php
namespace verbb\formie\models;

use verbb\formie\fields\data\OptionData;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;

use craft\base\Model;

class Address extends Model
{
    // Properties
    // =========================================================================

    public ?string $autoComplete = null;
    public ?string $address1 = null;
    public ?string $address2 = null;
    public ?string $address3 = null;
    public ?string $city = null;
    public ?string $state = null;
    public ?string $zip = null;
    public ?string $country = null;
    public ?string $countryOption = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Country should use the label, not value given it's a dropdown
        if (isset($config['country']) && $config['country'] instanceof OptionData) {
            $countryValue = $config['country']->value ?? '';

            if ($countryValue) {
                $countryOptions = $config['country']->getOptions();

                if ($countryOption = ArrayHelper::firstWhere($countryOptions, 'value', $countryValue)) {
                    $config['countryOption'] = $countryOption->label ?? '';
                }
            }
        }

        parent::__construct($config);
    }

    public function __toString()
    {
        if ($this->autoComplete) {
            return (string)$this->autoComplete;
        }

        $address = ArrayHelper::filterEmptyStringsFromArray([
            StringHelper::trim($this->address1 ?? ''),
            StringHelper::trim($this->address2 ?? ''),
            StringHelper::trim($this->address3 ?? ''),
            StringHelper::trim($this->city ?? ''),
            StringHelper::trim($this->state ?? ''),
            StringHelper::trim($this->zip ?? ''),
            StringHelper::trim($this->countryOption ?? ''),
        ]);

        return implode(', ', $address);
    }

}
