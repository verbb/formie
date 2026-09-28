<?php
namespace verbb\formie\fields\coercion;

use verbb\formie\fields\values\FieldValueInterface;
use verbb\formie\fields\values\MultiOptionFieldValue;
use verbb\formie\fields\values\OptionValue;
use verbb\formie\fields\values\RecipientsFieldValue;
use verbb\formie\fields\values\SingleOptionFieldValue;
use verbb\formie\helpers\ArrayHelper;

use Traversable;

final class ArrayValueCoercer
{
    // Static Methods
    // =========================================================================

    public static function forIntegration(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        // Integration arrays contain selected values, not option labels,
        // validity flags or the available (including unselected) options.
        if ($value instanceof MultiOptionFieldValue || $value instanceof RecipientsFieldValue) {
            return $value->values();
        }

        if ($value instanceof SingleOptionFieldValue || $value instanceof OptionValue) {
            return $value->value === null || $value->value === '' ? [] : [$value->value];
        }

        if ($value instanceof FieldValueInterface) {
            return $value->isEmpty() ? [] : [(string)$value];
        }

        if ($value instanceof Traversable) {
            return iterator_to_array($value);
        }

        return [$value];
    }


}
