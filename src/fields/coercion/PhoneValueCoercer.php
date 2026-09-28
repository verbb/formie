<?php
namespace verbb\formie\fields\coercion;

use Throwable;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

final class PhoneValueCoercer
{
    // Static Methods
    // =========================================================================

    public static function toPhoneString(mixed $value): string
    {
        if (is_array($value)) {
            $value = implode(', ', $value);
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
            $number = $phoneUtil->format($numberProto, PhoneNumberFormat::E164);
        } catch (Throwable) {
        }

        return (string)$number;
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

        if ($phone === '' || !preg_match('/^\+[0-9]+$/', $phone)) {
            return null;
        }

        return $phone;
    }
}
