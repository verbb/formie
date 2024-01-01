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
    // Static Methods
    // =========================================================================

    public static function toPhoneString(mixed $value): string
    {
        $number = $value;

        try {
            // Try and parse the number. Will fail if not provided in international format.
            $phoneUtil = PhoneNumberUtil::getInstance();
            $numberProto = $phoneUtil->parse($value);
            $number = $phoneUtil->format($numberProto, PhoneNumberFormat::INTERNATIONAL);
        } catch (Throwable $e) {
            // Do nothing, an invalid number
        }

        return str_replace(' ', '', $number);
    }


    // Properties
    // =========================================================================

    public ?string $number = null;
    public ?string $country = null;
    public ?bool $hasCountryCode = null;


    // Public Methods
    // =========================================================================

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
