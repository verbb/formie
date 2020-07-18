<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class Phone extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public $number;
    /**
     * @var string
     */
    public $country;

    /**
     * @var bool
     */
    public $hasCountryCode;


    // Public Methods
    // =========================================================================

    /**
     * Returns the formatted phone number.
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->hasCountryCode) {
            try {
                $phoneUtil = PhoneNumberUtil::getInstance();
                $numberProto = $phoneUtil->parse($this->number, $this->country);

                return $phoneUtil->format($numberProto, PhoneNumberFormat::INTERNATIONAL);
            } catch (NumberParseException $e) {
                if ($this->number) {
                    return '(' . $this->country . ') ' . (string)$this->number;
                } else {
                    return Craft::t('formie', '({country}) Not provided.', [
                        'country' => $this->country,
                    ]);
                }
            }
        } else {
            return (string)$this->number;
        }
    }

}
