<?php
namespace verbb\formie\helpers;

use InvalidArgumentException;

class PaymentAmountHelper
{
    // Static Methods
    // =========================================================================

    public static function parseAmount(mixed $value): string
    {
        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }

        $string = trim((string)$value);

        if ($string === '') {
            return '0';
        }

        $symbols = ['$', '€', '£', '¥', '₣', '₹', '₻', '₽', '₾', '₺', '₼', '₸', '฿', '원', '₫', '₱', '₳', '₵'];
        $string = str_replace($symbols, '', $string);
        $string = trim($string);

        $sanitized = preg_replace('/[^\d.,-]/', '', $string) ?? '';

        if ($sanitized === '' || $sanitized === '-') {
            return '0';
        }

        $hasComma = str_contains($sanitized, ',');
        $hasDot = str_contains($sanitized, '.');

        if ($hasComma && $hasDot) {
            if (strrpos($sanitized, ',') > strrpos($sanitized, '.')) {
                // EU style: 1.234,56
                $sanitized = str_replace('.', '', $sanitized);
                $sanitized = preg_replace('/,/', '.', $sanitized, 1) ?? $sanitized;
            } else {
                // US style: 1,234.56
                $sanitized = str_replace(',', '', $sanitized);
            }
        } elseif ($hasComma && !$hasDot) {
            if (preg_match('/^\d{1,3}(,\d{3})+$/', $sanitized)) {
                $sanitized = str_replace(',', '', $sanitized);
            } else {
                $sanitized = preg_replace('/,/', '.', $sanitized, 1) ?? $sanitized;
            }
        } else {
            $sanitized = str_replace(',', '', $sanitized);
        }

        if (!preg_match('/^-?[0-9]+(?:\.[0-9]+)?$/D', $sanitized)) {
            throw new InvalidArgumentException('Invalid payment amount.');
        }

        return $sanitized;
    }
}
