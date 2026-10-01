<?php
namespace verbb\formie\fields\coercion;

use verbb\formie\content\FieldStorageCodec;

use craft\helpers\Json;

use NumberFormatter;

final class DecimalValueCoercer
{
    // Static Methods
    // =========================================================================

    public static function normalize(mixed $value, ?string $locale = null): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_scalar($value)) {
            FieldStorageCodec::assertSafe($value);
            return Json::encode($value);
        }
        $value = trim((string)$value);

        if ($locale) {
            $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
            $value = str_replace($formatter->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL), '', $value);
            $value = str_replace($formatter->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL), '.', $value);
        }
        return $value === '' ? null : $value;
    }
}
