<?php
namespace verbb\formie\helpers;

use verbb\formie\base\FieldInterface;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\base\RepeatableParentFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Calculations;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

use Throwable;

/**
 * Works out Calculations answers on the server the way the browser does (`client-calculations.ts` and
 * `calculations.ts`), so an answer that decides what a visitor pays isn't taken from the request.
 */
final class ServerCalculations
{
    // Static Methods
    // =========================================================================

    public static function calculate(Submission $submission, Calculations $field): mixed
    {
        $form = $submission->getForm();

        if (!$form) {
            return null;
        }

        $values = [];

        foreach ($form->getFields() as $formField) {
            $values[$formField->handle] = $formField->serializeValueForClientInput($submission->getFieldValue($formField->handle), $submission);
        }

        $values = self::_deriveValues($form->getFields(), $values, $submission);

        return $values[$field->handle] ?? null;
    }

    /** Matches JavaScript's `String()` for the values a calculation reads and returns. */
    public static function toJsString(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value)) {
            return (string)$value;
        }

        if (!is_float($value)) {
            return (string)$value;
        }

        if (is_nan($value)) {
            return 'NaN';
        }

        if (is_infinite($value)) {
            return $value > 0 ? 'Infinity' : '-Infinity';
        }

        if ($value == 0) {
            return '0';
        }

        // The shortest digits that read back as the same number, as JavaScript prints them.
        for ($precision = 1; $precision <= 17; $precision++) {
            $exponential = sprintf('%.' . ($precision - 1) . 'e', $value);

            if ((float)$exponential === $value) {
                break;
            }
        }

        [$mantissa, $exponent] = explode('e', $exponential);
        $sign = str_starts_with($mantissa, '-') ? '-' : '';
        $digits = rtrim(str_replace(['-', '.'], '', $mantissa), '0') ?: '0';
        $point = (int)$exponent + 1;

        if ($point > 21 || $point <= -6) {
            $fraction = strlen($digits) > 1 ? '.' . substr($digits, 1) : '';
            $power = $point - 1;

            return $sign . $digits[0] . $fraction . 'e' . ($power < 0 ? '-' : '+') . abs($power);
        }

        if ($point <= 0) {
            return $sign . '0.' . str_repeat('0', -$point) . $digits;
        }

        if ($point >= strlen($digits)) {
            return $sign . $digits . str_repeat('0', $point - strlen($digits));
        }

        return $sign . substr($digits, 0, $point) . '.' . substr($digits, $point);
    }

    /** Matches JavaScript's `Number.prototype.toFixed()`, which rounds the exact value half away from zero. */
    public static function toFixed(float $value, int $decimals): string
    {
        if (is_nan($value) || is_infinite($value)) {
            return self::toJsString($value);
        }

        $sign = $value < 0 ? '-' : '';
        $exact = sprintf('%.40f', abs($value));
        [$whole, $fraction] = explode('.', $exact);
        $kept = $whole . substr($fraction, 0, $decimals);

        if ((int)($fraction[$decimals] ?? '0') >= 5) {
            $kept = self::_incrementDigits($kept);
        }

        $whole = substr($kept, 0, strlen($kept) - $decimals) ?: '0';
        $result = $decimals > 0 ? $whole . '.' . substr($kept, -$decimals) : $whole;

        return $sign . $result;
    }


    // Private Methods
    // =========================================================================

    private static function _deriveValues(array $fields, array $values, Submission $submission): array
    {
        $occurrences = self::_occurrences($fields, $values);
        $calculationCount = count(array_unique(array_map(static fn(array $occurrence) => $occurrence['field']->uid ?: $occurrence['field']->handle, $occurrences)));

        // A calculation can read another one later in the form, so repeat until the answers settle. A formula that
        // reads itself never settles, so the passes are capped by the number of Calculations fields, not rows.
        for ($pass = 0; $pass < $calculationCount + 1; $pass++) {
            $changed = false;

            foreach ($occurrences as $occurrence) {
                // Conditions clear a hidden field's answer, so it isn't worked out again.
                if (!isset($occurrence['row']) && $occurrence['field']->isConditionallyHidden($submission)) {
                    continue;
                }

                $parent = count($occurrence['path']) > 1 ? self::_readValue($values, array_slice($occurrence['path'], 0, -1)) : $values;

                if (!is_array($parent)) {
                    continue;
                }

                $result = self::_calculateOccurrence($values, $occurrence);

                if ($result !== null && !self::_strictEquals(self::_readValue($values, $occurrence['path']), $result)) {
                    $values = self::_writeValue($values, $occurrence['path'], $result);
                    $changed = true;
                }
            }

            if (!$changed) {
                break;
            }

            $occurrences = self::_occurrences($fields, $values);
        }

        return $values;
    }

    private static function _occurrences(array $fields, array $values): array
    {
        $occurrences = [];

        foreach ($fields as $field) {
            if ($field instanceof Calculations) {
                $occurrences[] = ['field' => $field, 'path' => [$field->handle]];
            } elseif ($field instanceof RepeatableParentFieldInterface) {
                $rows = is_array($values[$field->handle] ?? null) ? array_values($values[$field->handle]) : [];

                foreach (array_keys($rows) as $index) {
                    foreach (self::_calculationChildren($field) as $child) {
                        $occurrences[] = ['field' => $child, 'path' => [$field->handle, $index, $child->handle], 'row' => ['handle' => $field->handle, 'index' => $index]];
                    }
                }
            } elseif ($field instanceof ParentFieldInterface) {
                foreach (self::_calculationChildren($field) as $child) {
                    $occurrences[] = ['field' => $child, 'path' => [$field->handle, $child->handle]];
                }
            }
        }

        return $occurrences;
    }

    private static function _calculationChildren(ParentFieldInterface $field): array
    {
        return array_values(array_filter($field->getFields(), static fn(FieldInterface $child) => $child instanceof Calculations));
    }

    private static function _calculateOccurrence(array $values, array $occurrence): mixed
    {
        /** @var Calculations $field */
        $field = $occurrence['field'];
        $formula = $field->getFormula();
        $expression = trim((string)($formula['expression'] ?? $formula['formula'] ?? ''));

        if ($expression === '') {
            return null;
        }

        $variables = [];

        foreach ($formula['variables'] ?? [] as $key => $variable) {
            if (!is_array($variable) || empty($variable['sourceKey'])) {
                continue;
            }

            $variables[$key] = self::_readVariableValue($variable, self::_sourceValue($values, $variable, $occurrence['row'] ?? null));
        }

        try {
            $result = (new ExpressionLanguage())->evaluate($expression, self::_coerceVariables($variables));
            $result = self::_formatValue($result, $field);
        } catch (Throwable) {
            // An incomplete formula, such as one waiting on an empty source, has no answer yet.
            return '';
        }

        return is_float($result) && !is_finite($result) ? '' : $result;
    }

    private static function _sourceValue(array $values, array $variable, ?array $row): string|array
    {
        $parts = array_values(array_filter(explode('.', (string)$variable['sourceKey']), static fn(string $part) => $part !== ''));
        $handle = array_shift($parts);
        $value = $values[$handle] ?? null;
        $scope = strtolower(trim((string)($variable['scope'] ?? '')));

        // A calculation in a Repeater row reads the other fields of its own row.
        if ($row && $scope === '' && $handle === $row['handle'] && $parts) {
            $rows = self::_isList($value) ? $value : [];

            return self::_toStrings(self::_readPath($rows[$row['index']] ?? null, ctype_digit($parts[0]) ? array_slice($parts, 1) : $parts));
        }

        // Repeater and Table rows can be picked by position.
        if (in_array($scope, ['first', 'last', 'index', 'all', 'count', 'rows'], true) && $parts) {
            $columnPath = count($parts) > 1 && ctype_digit($parts[0]) ? array_slice($parts, 1) : $parts;
            $rows = self::_isList($value) ? $value : [];
            $cells = array_map(static fn($row) => self::_toStrings(self::_readPath($row, $columnPath)), $rows);

            return match ($scope) {
                'count' => (string)count($rows),
                'first' => $cells[0] ?? '',
                'last' => $cells ? $cells[count($cells) - 1] : '',
                'index' => $cells[(int)($variable['index'] ?? 0)] ?? '',
                'all' => self::_flattenCells($cells),
                default => self::_rowsValue($cells, (string)($variable['rows'] ?? '')),
            };
        }

        return self::_readSourcePath($value, $parts);
    }

    private static function _rowsValue(array $cells, string $rows): string|array
    {
        $indices = RepeaterReferenceHelper::parseRowsExpression($rows, count($cells));

        if (count($indices) === 1) {
            return $cells[$indices[0]] ?? '';
        }

        return $indices ? self::_flattenCells(array_map(static fn(int $index) => $cells[$index], $indices)) : '';
    }

    private static function _readSourcePath(mixed $value, array $path): string|array
    {
        $values = [$value];

        foreach ($path as $key) {
            $next = [];

            foreach ($values as $item) {
                if (self::_isList($item)) {
                    foreach ($item as $row) {
                        $next[] = self::_readPath($row, [$key]);
                    }
                } else {
                    $next[] = self::_readPath($item, [$key]);
                }
            }

            $values = $next;
        }

        $strings = [];

        foreach ($values as $item) {
            foreach (self::_isList($item) ? $item : [$item] as $scalar) {
                if (is_scalar($scalar)) {
                    $strings[] = self::toJsString($scalar);
                }
            }
        }

        return $path || self::_isList($values[0] ?? null) ? $strings : ($strings[0] ?? '');
    }

    private static function _readVariableValue(array $variable, string|array $value): mixed
    {
        if (isset($variable['numberFormat']) && is_array($variable['numberFormat'])) {
            $prefix = (string)($variable['numberFormat']['prefix'] ?? '');
            $suffix = (string)($variable['numberFormat']['suffix'] ?? '');
            $readNumber = static function(string $formatted) use ($prefix, $suffix): float|int {
                // Remove only the source field's configured affixes, which may themselves contain digits.
                if ($prefix !== '' && str_starts_with($formatted, $prefix)) {
                    $formatted = substr($formatted, strlen($prefix));
                }

                if ($suffix !== '' && str_ends_with($formatted, $suffix)) {
                    $formatted = substr($formatted, 0, -strlen($suffix));
                }

                return self::_toNumber($formatted);
            };

            return is_array($value) ? array_map($readNumber, $value) : $readNumber($value);
        }

        if (is_array($value)) {
            return $value ?: '';
        }

        return $value;
    }

    private static function _coerceVariables(array $variables): array
    {
        // Numeric-looking answers are numbers even without number formatting, otherwise `1 + 2` becomes `12`.
        foreach ($variables as $key => $value) {
            if (is_array($value)) {
                $coerced = array_map(static fn($item) => is_string($item) && self::_isNumericString($item) ? self::_toNumber($item) : $item, $value);
                $numbers = array_filter($coerced, static fn($item) => is_int($item) || is_float($item));
                $variables[$key] = $coerced && count($numbers) === count($coerced) ? array_sum($numbers) : $coerced;
            } elseif (is_string($value) && self::_isNumericString($value)) {
                $variables[$key] = self::_toNumber($value);
            }
        }

        return $variables;
    }

    private static function _formatValue(mixed $value, Calculations $field): mixed
    {
        if ($field->formatting !== 'number') {
            return is_int($value) || is_float($value) || is_string($value) ? $value : '';
        }

        if (is_array($value)) {
            $value = array_reduce($value, static fn($total, $item) => $total + self::_toNumber($item ?: 0), 0);
        }

        $number = self::_isFalsy($value) ? 0.0 : (float)self::_toNumber($value);

        return $field->prefix . self::toFixed($number, $field->decimals) . $field->suffix;
    }

    private static function _toStrings(mixed $value): string|array
    {
        if (self::_isList($value)) {
            return array_values(array_map(self::toJsString(...), array_filter($value, static fn($item) => is_scalar($item))));
        }

        return $value === null || is_array($value) || is_object($value) ? '' : self::toJsString($value);
    }

    private static function _flattenCells(array $cells): array
    {
        $flattened = [];

        foreach ($cells as $cell) {
            if (is_array($cell)) {
                array_push($flattened, ...$cell);
            } elseif ($cell !== '') {
                $flattened[] = $cell;
            }
        }

        return $flattened;
    }

    private static function _readPath(mixed $value, array $path): mixed
    {
        foreach ($path as $key) {
            $value = is_array($value) && !self::_isList($value) ? ($value[$key] ?? null) : null;
        }

        return $value;
    }

    private static function _readValue(array $values, array $path): mixed
    {
        $current = $values;

        foreach ($path as $key) {
            $current = is_array($current) ? ($current[$key] ?? null) : null;
        }

        return $current;
    }

    private static function _writeValue(array $values, array $path, mixed $result): array
    {
        $key = array_shift($path);
        $values[$key] = $path ? self::_writeValue(is_array($values[$key] ?? null) ? $values[$key] : [], $path, $result) : $result;

        return $values;
    }

    private static function _strictEquals(mixed $left, mixed $right): bool
    {
        // JavaScript has a single number type, so 2 and 2.0 are the same answer.
        if ((is_int($left) || is_float($left)) && (is_int($right) || is_float($right))) {
            return $left == $right;
        }

        return $left === $right;
    }

    private static function _isList(mixed $value): bool
    {
        return is_array($value) && array_is_list($value);
    }

    private static function _isNumericString(string $value): bool
    {
        return trim($value) !== '' && is_numeric(trim($value));
    }

    private static function _toNumber(mixed $value): int|float
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return (int)$value;
        }

        $value = trim((string)$value);

        if ($value === '') {
            return 0;
        }

        return is_numeric($value) ? $value + 0 : NAN;
    }

    private static function _isFalsy(mixed $value): bool
    {
        return $value === null || $value === false || $value === '' || $value === 0 || $value === 0.0 || (is_float($value) && is_nan($value));
    }

    private static function _incrementDigits(string $digits): string
    {
        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            if ($digits[$i] !== '9') {
                return substr($digits, 0, $i) . ((int)$digits[$i] + 1) . str_repeat('0', strlen($digits) - $i - 1);
            }
        }

        return '1' . str_repeat('0', strlen($digits));
    }
}
