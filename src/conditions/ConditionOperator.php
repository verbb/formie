<?php
namespace verbb\formie\conditions;

use DateTimeImmutable;
use DateTimeZone;

/** Closed GA operators. The schema is shared directly with the browser package. */
final class ConditionOperator
{
    // Static Methods
    // =========================================================================

    public static function schema(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/schema.json'), true, flags: JSON_THROW_ON_ERROR);
    }

    public static function all(): array
    {
        return array_keys(self::schema()['operators']);
    }

    public static function isSupported(string $operator): bool
    {
        return in_array($operator, self::all(), true);
    }

    public static function forType(string $type): array
    {
        return array_keys(array_filter(self::schema()['operators'], static fn(array $types): bool => in_array($type, $types, true)));
    }

    public static function evaluate(string $operator, mixed $actual, mixed $expected, string $type = 'text'): ConditionEvaluation
    {
        if (!in_array($operator, self::forType($type), true)) {
            return ConditionEvaluation::invalid('unsupportedOperator');
        }
        if ($operator === self::EMPTY || $operator === self::NOT_EMPTY) {
            if (is_object($actual) || (is_array($actual) && !array_is_list($actual) && !in_array($type, ['collection', 'date', 'time', 'datetime'], true))) {
                return ConditionEvaluation::invalid('invalidValue');
            }
            $empty = $actual === null || $actual === [] || (is_string($actual) && trim($actual, " \t\r\n\v\f") === '');
            return new ConditionEvaluation($operator === self::EMPTY ? $empty : !$empty);
        }
        if ($type === 'collection') {
            if ((!is_array($actual) && $actual !== null) || (is_array($expected) && !array_is_list($expected))) {
                return ConditionEvaluation::invalid('invalidValue');
            }
            $items = [];
            $actual ??= [];
            array_walk_recursive($actual, static function($item) use (&$items) { $items[] = $item; });
            $expectedItems = is_array($expected) ? $expected : [$expected];
            foreach ([...$items, ...$expectedItems] as $operand) {
                if ((!is_scalar($operand) && $operand !== null) || (is_float($operand) && !is_finite($operand))) {
                    return ConditionEvaluation::invalid('invalidValue');
                }
            }
            $matched = false;
            foreach ($items as $item) {
                foreach ($expectedItems as $wanted) {
                    $matched = $matched || self::_text($item) === self::_text($wanted);
                }
            }
            return new ConditionEvaluation(in_array($operator, [self::NEQ, self::NOT_CONTAINS], true) ? !$matched : $matched);
        }
        $a = self::_operand($actual, $type);
        $b = self::_operand($expected, $type);
        if ($a === null || $b === null) {
            return ConditionEvaluation::invalid('invalidValue');
        }
        $order = $type === 'text' ? strcmp($a, $b) : ($a <=> $b);
        return new ConditionEvaluation(match ($operator) {
            self::EQ => $a === $b,
            self::NEQ => $a !== $b,
            self::GT => $order > 0,
            self::LT => $order < 0,
            self::CONTAINS => str_contains($a, $b),
            self::NOT_CONTAINS => !str_contains($a, $b),
            self::STARTS_WITH => str_starts_with($a, $b),
            self::ENDS_WITH => str_ends_with($a, $b),
        });
    }

    private static function _text(mixed $value): ?string
    {
        if (is_int($value) || is_float($value)) {
            if (!is_finite((float)$value)) {
                return null;
            }
            if ($value == 0) {
                return '0';
            }
            // JSON retains round-trip precision; PHP's string cast uses the lower display precision.
            $precision = ini_get('serialize_precision');
            try {
                ini_set('serialize_precision', '-1');
                $text = json_encode((float)$value, JSON_THROW_ON_ERROR);
            } finally {
                ini_set('serialize_precision', $precision);
            }
            if (preg_match('/^(-?)([0-9]+)(?:\.([0-9]+))?e([+-]?[0-9]+)$/i', $text, $parts)) {
                $exponent = (int)$parts[4];
                $digits = rtrim($parts[2] . ($parts[3] ?? ''), '0');
                $position = strlen($parts[2]) + $exponent;
                // Match the browser's fixed/scientific notation boundaries.
                if ($exponent >= -6 && $exponent < 21) {
                    if ($position <= 0) {
                        return $parts[1] . '0.' . str_repeat('0', -$position) . $digits;
                    }
                    return $parts[1] . ($position >= strlen($digits)
                        ? $digits . str_repeat('0', $position - strlen($digits))
                        : substr($digits, 0, $position) . '.' . substr($digits, $position));
                }
                return $parts[1] . $digits[0] . (strlen($digits) > 1 ? '.' . substr($digits, 1) : '') . 'e' . ($exponent >= 0 ? '+' : '') . $exponent;
            }
            return $text;
        }
        return $value === null ? '' : (is_bool($value) ? ($value ? 'true' : 'false') : (is_string($value) ? $value : null));
    }

    private static function _operand(mixed $value, string $type): string|float|bool|null
    {
        if ($type === 'text') {
            return self::_text($value);
        }
        if ($type === 'number') {
            if ((!is_string($value) && !is_int($value) && !is_float($value)) || !preg_match('/^[+-]?(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)(?:[eE][+-]?[0-9]+)?$/D', trim((string)$value, " \t\r\n\v\f"))) {
                return null;
            }
            $number = (float)$value;
            return is_finite($number) ? $number : null;
        }
        if ($type === 'boolean') {
            if (is_bool($value)) {
                return $value;
            }
            return match (strtolower(trim(self::_text($value) ?? '', " \t\r\n\v\f"))) {
                'true', '1', 'yes', 'on' => true,
                'false', '0', 'no', 'off' => false,
                default => null,
            };
        }
        // Consume the field's normalized parts without formatting through Twig or a locale.
        if (is_array($value)) {
            $required = $type === 'date' ? ['year', 'month', 'day'] : ($type === 'time' ? ['hour', 'minute'] : ['year', 'month', 'day', 'hour', 'minute']);
            foreach ($required as $part) {
                if (!isset($value[$part]) || !ctype_digit((string)$value[$part])) {
                    return null;
                }
            }
            if (isset($value['_input']) || (isset($value['second']) && !ctype_digit((string)$value['second']))) {
                return null;
            }
            $hour = (int)($value['hour'] ?? 0);
            if (isset($value['ampm'])) {
                if (!in_array($value['ampm'], ['AM', 'PM'], true) || $hour < 1 || $hour > 12) {
                    return null;
                }
                $hour = $hour % 12 + ($value['ampm'] === 'PM' ? 12 : 0);
            }
            $date = sprintf('%04d-%02d-%02d', $value['year'] ?? 1970, $value['month'] ?? 1, $value['day'] ?? 1);
            $time = sprintf('%02d:%02d:%02d', $hour, $value['minute'] ?? 0, $value['second'] ?? 0);
            if ($type === 'datetime') {
                if (self::_operand($date, 'date') === null || self::_operand($time, 'time') === null) {
                    return null;
                }
                try {
                    $zone = new DateTimeZone($value['timezone'] ?? 'UTC');
                    $wall = (new DateTimeImmutable($date . 'T' . $time, new DateTimeZone('UTC')))->getTimestamp();
                    $candidates = [];
                    foreach ([-86400, 0, 86400] as $delta) {
                        $offset = $zone->getOffset(new DateTimeImmutable('@' . ($wall + $delta)));
                        $candidate = $wall - $offset;
                        $instant = (new DateTimeImmutable('@' . $candidate))->setTimezone($zone);
                        if ($instant->format('Y-m-d\TH:i:s') === $date . 'T' . $time) {
                            $candidates[$candidate] = true;
                        }
                    }
                    return count($candidates) === 1 ? (float)array_key_first($candidates) : null;
                } catch (\Throwable) {
                    return null;
                }
            }
            $value = $type === 'date' ? $date : $time;
        }
        if (!is_string($value)) {
            return null;
        }
        $pattern = match ($type) {
            'date' => '/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/D',
            'time' => '/^([0-9]{2}):([0-9]{2})(?::([0-9]{2}))?$/D',
            'datetime' => '/^([0-9]{4})-([0-9]{2})-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})(Z|[+-][0-9]{2}:[0-9]{2})$/D',
            default => '//',
        };
        if (!preg_match($pattern, $value, $parts)) {
            return null;
        }
        if ($type !== 'time' && (!checkdate((int)$parts[2], (int)$parts[3], (int)$parts[1]) || (int)$parts[1] < 1)) {
            return null;
        }
        $offset = $type === 'time' ? 1 : 4;
        if ($type !== 'date' && ((int)$parts[$offset] > 23 || (int)$parts[$offset + 1] > 59 || (int)($parts[$offset + 2] ?? 0) > 59)) {
            return null;
        }
        if ($type === 'time') {
            return (float)((int)$parts[1] * 3600 + (int)$parts[2] * 60 + (int)($parts[3] ?? 0));
        }
        if ($type === 'datetime' && $parts[7] !== 'Z' && ((int)substr($parts[7], 1, 2) > 23 || (int)substr($parts[7], 4, 2) > 59)) {
            return null;
        }
        return (float)(new DateTimeImmutable($value, new DateTimeZone('UTC')))->getTimestamp();
    }


    // Constants
    // =========================================================================

    public const EQ = '=';
    public const NEQ = '!=';
    public const GT = '>';
    public const LT = '<';
    public const CONTAINS = 'contains';
    public const NOT_CONTAINS = 'notContains';
    public const STARTS_WITH = 'startsWith';
    public const ENDS_WITH = 'endsWith';
    public const EMPTY = 'empty';
    public const NOT_EMPTY = 'notEmpty';
}
