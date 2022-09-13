<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Throwable;

class Phone extends Model
{
    // Properties
    // =========================================================================

    public ?string $number = null;
    public ?string $country = null;
    public ?bool $hasCountryCode = null;


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
                    $countryString = $this->country ? '(' . $this->country . ') ' : '';

                    return $countryString . $this->number;
                }

                if ($this->country) {
                    return Craft::t('formie', '({country}) Not provided.', [
                        'country' => $this->country,
                    ]);
                }

                return '';
            }
        } else {
            return (string)$this->number;
        }
    }

    public function getCountryCode(): string
    {
        if ($this->hasCountryCode) {
            try {
                $phoneUtil = PhoneNumberUtil::getInstance();
                $numberProto = $phoneUtil->parse($this->number, $this->country);
                $countryCode = $numberProto->getCountryCode();

                if ($countryCode) {
                    return '+' . $countryCode;
                }
            } catch (Throwable $e) {

            }
        }

        return '';
    }

    public function getCountryName(): string
    {
        if ($this->country) {
            return Craft::$app->getAddresses()->getCountryRepository()->get($this->country)->getName();
        }

        return '';
    }

}
