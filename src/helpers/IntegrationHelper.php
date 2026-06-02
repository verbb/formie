<?php
namespace verbb\formie\helpers;

use verbb\formie\fields\coercion\ArrayValueCoercer;
use verbb\formie\fields\coercion\BooleanValueCoercer;
use verbb\formie\fields\coercion\NumberValueCoercer;
use verbb\formie\fields\coercion\ScalarValueCoercer;
use verbb\formie\fields\values\DateFieldValue;
use verbb\formie\fields\values\PhoneFieldValue;
use verbb\formie\models\IntegrationField;

final class IntegrationHelper
{
    public static function convertValueForIntegration(mixed $value, IntegrationField $integrationField): mixed
    {
        return match ($integrationField->getType()) {
            IntegrationField::TYPE_ARRAY => ArrayValueCoercer::forIntegration($value),
            IntegrationField::TYPE_DATE => DateFieldValue::toDateString($value),
            IntegrationField::TYPE_DATETIME => DateFieldValue::toDateTimeString($value),
            IntegrationField::TYPE_DATECLASS => DateFieldValue::toDateTime($value),
            IntegrationField::TYPE_NUMBER => NumberValueCoercer::toInt($value),
            IntegrationField::TYPE_FLOAT => NumberValueCoercer::toFloat($value),
            IntegrationField::TYPE_BOOLEAN => BooleanValueCoercer::toBoolean($value),
            IntegrationField::TYPE_PHONE => PhoneFieldValue::toNormalizedPhone($value),
            default => ScalarValueCoercer::toScalarString($value),
        };
    }
}
