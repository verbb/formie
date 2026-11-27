<?php
namespace verbb\formie\helpers;

use verbb\formie\base\FieldInterface;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\base\RepeatableParentFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Calculations;
use verbb\formie\fields\Table;

use InvalidArgumentException;
use Throwable;

/**
 * Works out Calculations answers on the server the way the browser does (the server-rendered `calculations.ts`
 * module and formie-core's `client-calculations.ts`), so an answer that decides what a visitor pays isn't taken
 * from the request.
 */
final class ServerCalculations
{
    // Static Methods
    // =========================================================================

    public static function calculate(Submission $submission, Calculations $field): mixed
    {
        return self::_derive($submission)['values'][$field->handle] ?? null;
    }

    /**
     * Returns the paths of Calculations answers in the submission that differ from the server's answer.
     * Conditions on the server read the posted answers, so every one has to agree, not just the amount.
     */
    public static function mismatches(Submission $submission): array
    {
        $derived = self::_derive($submission);
        $mismatches = [];

        foreach ($derived['calculated'] as $key => $path) {
            $posted = self::_readValue($derived['posted'], $path);
            $calculated = self::_readValue($derived['values'], $path);

            if (!self::answersMatch($posted, $calculated)) {
                $mismatches[] = $key;
            }
        }

        return $mismatches;
    }

    public static function answersMatch(mixed $posted, mixed $calculated): bool
    {
        $posted = trim(is_float($posted) || is_int($posted) ? CalculationExpression::toJsString($posted) : (string)$posted);
        $calculated = trim(is_float($calculated) || is_int($calculated) ? CalculationExpression::toJsString($calculated) : (string)$calculated);

        // Server-rendered forms show a formula with no number as `NaN` or `Infinity`, while client-rendered forms leave it empty.
        $noAnswer = ['', 'NaN', 'Infinity', '-Infinity'];

        if ($posted === $calculated || (in_array($posted, $noAnswer, true) && in_array($calculated, $noAnswer, true))) {
            return true;
        }

        // Site code can format a number for display, such as `$6.00` for 6, which is still the same answer.
        return preg_match('/\d/', $posted) && preg_match('/\d/', $calculated) && self::_amount($posted) !== null && self::_amount($posted) === self::_amount($calculated);
    }


    // Private Methods
    // =========================================================================

    private static function _derive(Submission $submission): array
    {
        $form = $submission->getForm();

        if (!$form) {
            return ['values' => [], 'posted' => [], 'calculated' => []];
        }

        $fields = $form->getFields();
        $values = [];

        foreach ($fields as $field) {
            $values[$field->handle] = self::_clientValue($field, $submission->getFieldValue($field->handle), $submission);
        }

        $posted = $values;
        $occurrences = self::_occurrences($fields, $values);
        $calculated = [];
        $calculationCount = count(array_unique(array_map(static fn(array $occurrence) => $occurrence['field']->uid ?: $occurrence['field']->handle, $occurrences)));

        // A calculation can read another one later in the form, so repeat until the answers settle. A formula that
        // reads itself never settles, so the passes are capped by the number of Calculations fields, not rows.
        for ($pass = 0; $pass < $calculationCount + 1; $pass++) {
            $changed = false;

            foreach ($occurrences as $occurrence) {
                $key = implode('.', $occurrence['path']);

                // Conditions clear a hidden field's answer, so it isn't worked out, or read by other calculations.
                if (!isset($occurrence['row']) && $occurrence['field']->isConditionallyHidden($submission)) {
                    unset($calculated[$key]);

                    continue;
                }

                $parent = count($occurrence['path']) > 1 ? self::_readValue($values, array_slice($occurrence['path'], 0, -1)) : $values;

                if (!is_array($parent)) {
                    continue;
                }

                $result = self::_calculateOccurrence($values, $occurrence);

                if ($result === null) {
                    continue;
                }

                $calculated[$key] = $occurrence['path'];

                if (!self::_sameValue(self::_readValue($values, $occurrence['path']), $result)) {
                    $values = self::_writeValue($values, $occurrence['path'], $result);
                    $changed = true;
                }
            }

            if (!$changed) {
                break;
            }

            $occurrences = self::_occurrences($fields, $values);
        }

        return ['values' => $values, 'posted' => $posted, 'calculated' => $calculated];
    }

    /** A field's answer as the browser reads it from the form's inputs. */
    private static function _clientValue(FieldInterface $field, mixed $value, Submission $submission): mixed
    {
        // Table inputs are named by column ID, which is also how formulas select a column.
        if ($field instanceof Table) {
            $rows = [];

            foreach (is_array($value) ? $value : [] as $row) {
                $cells = [];

                foreach ($field->columns ?? [] as $id => $column) {
                    $cell = is_array($row) ? ($row[$id] ?? $row[$column['handle'] ?? $id] ?? null) : null;
                    $cells[(string)$id] = is_bool($cell) ? ($cell ? '1' : '') : $cell;
                }

                $rows[] = $cells;
            }

            return $rows;
        }

        return self::_booleansAsInputs($field->serializeValueForClientInput($value, $submission));
    }

    /** A ticked checkbox posts `1` and an unticked one posts nothing. */
    private static function _booleansAsInputs(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value ? '1' : '';
        }

        return is_array($value) ? array_map(self::_booleansAsInputs(...), $value) : $value;
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
        $expression = trim((string)(($formula['expression'] ?? '') ?: ($formula['formula'] ?? '')));

        if ($expression === '') {
            return null;
        }

        $variables = array_filter($formula['variables'] ?? [], static fn($variable) => is_array($variable) && !empty($variable['sourceKey']));
        $sources = array_map(fn(array $variable) => self::_sourceValue($values, $variable, $occurrence['row'] ?? null), $variables);

        return self::evaluateFormula($expression, $variables, $sources, [
            'formatting' => $field->formatting,
            'prefix' => $field->prefix,
            'suffix' => $field->suffix,
            'decimals' => $field->decimals,
        ]);
    }

    /**
     * Works out a formula from its sources' answers as the browser reads them, the way `client-calculations.ts`
     * does, returning `''` when the formula has no answer.
     *
     * @param array<string, array> $variables Variable settings from `Calculations::getFormula()`.
     * @param array<string, string|string[]> $sources Each variable's source answer.
     * @param array{formatting?: string, prefix?: string, suffix?: string, decimals?: int} $format
     */
    public static function evaluateFormula(string $expression, array $variables, array $sources, array $format = []): mixed
    {
        $values = [];

        foreach ($variables as $key => $variable) {
            $values[$key] = self::_readVariableValue($variable, $sources[$key] ?? '');
        }

        try {
            $result = self::_formatValue(CalculationExpression::evaluate($expression, self::_coerceVariables($values)), $format);
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
                    $strings[] = CalculationExpression::toJsString($scalar);
                }
            }
        }

        return $path || self::_isList($values[0] ?? null) ? $strings : ($strings[0] ?? '');
    }

    /** `readCalculationVariableValue()` in `calculations.ts`. */
    private static function _readVariableValue(array $variable, string|array $value): mixed
    {
        if (isset($variable['numberFormat']) && is_array($variable['numberFormat'])) {
            $prefix = (string)($variable['numberFormat']['prefix'] ?? '');
            $suffix = (string)($variable['numberFormat']['suffix'] ?? '');
            $readNumber = static function(string $formatted) use ($prefix, $suffix): float {
                // Remove only the source field's configured affixes, which may themselves contain digits.
                if ($prefix !== '' && str_starts_with($formatted, $prefix)) {
                    $formatted = substr($formatted, strlen($prefix));
                }

                if ($suffix !== '' && str_ends_with($formatted, $suffix)) {
                    $formatted = substr($formatted, 0, -strlen($suffix));
                }

                return CalculationExpression::toNumber($formatted === '' ? 0.0 : $formatted);
            };

            return is_array($value) ? array_map($readNumber, $value) : $readNumber($value);
        }

        if (is_array($value)) {
            return $value ?: '';
        }

        return $value;
    }

    /** `coerceCalculationVariables()` in `calculations.ts`. */
    private static function _coerceVariables(array $variables): array
    {
        // Numeric-looking answers are numbers even without number formatting, otherwise `1 + 2` becomes `12`.
        foreach ($variables as $key => $value) {
            if (is_array($value)) {
                $coerced = array_map(static fn($item) => is_string($item) && self::_isNumericString($item) ? CalculationExpression::toNumber($item) : $item, $value);
                $numbers = array_filter($coerced, static fn($item) => is_float($item));

                if ($coerced && count($numbers) === count($coerced)) {
                    // `total + Number(item || 0)`, so an unreadable number counts as 0.
                    $variables[$key] = array_reduce($coerced, static fn(float $total, float $item) => $total + (CalculationExpression::isTruthy($item) ? $item : 0.0), 0.0);
                } else {
                    $variables[$key] = $coerced;
                }
            } elseif (is_string($value) && self::_isNumericString($value)) {
                $variables[$key] = CalculationExpression::toNumber($value);
            }
        }

        return $variables;
    }

    /** `formatCalculationValue()` in `calculations.ts`. */
    private static function _formatValue(mixed $value, array $format): mixed
    {
        if (($format['formatting'] ?? '') !== 'number') {
            return is_float($value) || is_string($value) ? $value : '';
        }

        if (is_array($value)) {
            $value = array_reduce($value, static fn(float $total, mixed $item) => $total + CalculationExpression::toNumber(CalculationExpression::isTruthy($item) ? $item : 0.0), 0.0);
        }

        $number = CalculationExpression::toNumber(CalculationExpression::isTruthy($value) ? $value : 0.0);

        return ($format['prefix'] ?? '') . CalculationExpression::toFixed($number, (int)($format['decimals'] ?? 0)) . ($format['suffix'] ?? '');
    }

    private static function _isNumericString(string $value): bool
    {
        return trim($value) !== '' && !is_nan(CalculationExpression::toNumber($value));
    }

    private static function _toStrings(mixed $value): string|array
    {
        if (self::_isList($value)) {
            return array_values(array_map(CalculationExpression::toJsString(...), array_filter($value, static fn($item) => is_scalar($item))));
        }

        return $value === null || is_array($value) || is_object($value) ? '' : CalculationExpression::toJsString($value);
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

    private static function _sameValue(mixed $left, mixed $right): bool
    {
        // JavaScript has a single number type, so 2 and 2.0 are the same answer.
        if ((is_int($left) || is_float($left)) && (is_int($right) || is_float($right))) {
            return $left == $right;
        }

        return $left === $right;
    }

    private static function _amount(string $value): ?string
    {
        try {
            $amount = PaymentAmountHelper::parseAmount($value);
        } catch (InvalidArgumentException) {
            return null;
        }

        $negative = str_starts_with($amount, '-');
        [$whole, $fraction] = array_pad(explode('.', ltrim($amount, '-'), 2), 2, '');
        $amount = (ltrim($whole, '0') ?: '0') . (rtrim($fraction, '0') !== '' ? '.' . rtrim($fraction, '0') : '');

        return $negative && $amount !== '0' ? '-' . $amount : $amount;
    }

    private static function _isList(mixed $value): bool
    {
        return is_array($value) && array_is_list($value);
    }
}
