<?php
namespace verbb\formie\fields\values;

use verbb\formie\fields\coercion\ScalarValueCoercer;

use Craft;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

use Throwable;

class PhoneFieldValue extends BaseFieldValue
{
    // Static Methods
    // =========================================================================

    public static function capabilityTypes(): array
    {
        return ['string', 'array', 'phone'];
    }

    public static function toPhoneString(mixed $value): string
    {
        if (is_array($value)) {
            $value = implode(', ', $value);
        } else if ($value instanceof FieldValueInterface) {
            $value = $value->toValueString();
        } else if (!is_scalar($value) && is_object($value)) {
            if (method_exists($value, '__toString')) {
                $value = $value->__toString();
            } else {
                $value = json_encode($value);
            }
        } else if (!is_scalar($value)) {
            $value = (string)$value;
        }

        $number = $value;

        try {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $numberProto = $phoneUtil->parse((string)$value);
            $number = $phoneUtil->format($numberProto, PhoneNumberFormat::INTERNATIONAL);
        } catch (Throwable) {
        }

        return str_replace(' ', '', (string)$number);
    }

    public static function toNormalizedPhone(mixed $value): ?string
    {
        $value = ScalarValueCoercer::normalizeScalarLike($value);

        if (!is_scalar($value)) {
            return null;
        }

        $stringValue = trim((string)$value);

        if ($stringValue === '') {
            return null;
        }

        if (preg_match('/[A-Za-z]/', $stringValue)) {
            return null;
        }

        $phone = self::toPhoneString($stringValue);

        if ($phone === '' || $phone === $stringValue) {
            return null;
        }

        return $phone;
    }


    // Properties
    // =========================================================================

    public ?string $number = null;
    public ?string $country = null;
    public ?bool $hasCountryCode = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        unset($config['countryCode'], $config['countryName']);

        parent::__construct($config);
    }

    public function __toString()
    {
        if ($this->hasCountryCode) {
            try {
                $phoneUtil = PhoneNumberUtil::getInstance();
                $numberProto = $phoneUtil->parse($this->number, $this->country);

                return $phoneUtil->format($numberProto, PhoneNumberFormat::INTERNATIONAL);
            } catch (NumberParseException) {
                if ($this->number && is_numeric($this->number)) {
                    $countryString = $this->country && is_numeric($this->country) ? '(' . $this->country . ') ' : '';

                    return $countryString . $this->number;
                }

                return '';
            }
        }

        return (string)$this->number;
    }

    public function isEmpty(): bool
    {
        return $this->__toString() === '';
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
            } catch (Throwable) {
            }
        }

        return '';
    }

    public function getCountryName(): string
    {
        if ($this->country) {
            try {
                return Craft::$app->getAddresses()->getCountryRepository()->get($this->country)->getName();
            } catch (Throwable) {
            }
        }

        return '';
    }

    public function toArray(): array
    {
        $array = parent::toArray();

        $array['countryCode'] = $this->getCountryCode();
        $array['countryName'] = $this->getCountryName();

        return $array;
    }

    public function toValueArray(): array
    {
        return $this->toArray();
    }
}
