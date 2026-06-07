<?php
namespace verbb\formie\helpers;

use verbb\formie\base\RepeatableParentFieldInterface;
use verbb\formie\base\FieldInterface;
use verbb\formie\elements\Submission;

class RepeaterReferenceHelper
{
    // Constants
    // =========================================================================

    public const SCOPE_FIRST = 'first';
    public const SCOPE_LAST = 'last';
    public const SCOPE_INDEX = 'index';
    public const SCOPE_ALL = 'all';
    public const SCOPE_COUNT = 'count';
    public const SCOPE_ROWS = 'rows';


    // Static Methods
    // =========================================================================

    public static function resolve(
        Submission $submission,
        FieldInterface $repeaterField,
        string $selector,
        array $params = [],
    ): mixed {
        if (!$repeaterField instanceof RepeatableParentFieldInterface) {
            return null;
        }

        [$subPath, $scope, $index] = self::parseSelectorAndScope($selector, $params);

        if ($scope === null) {
            return null;
        }

        if ($scope === self::SCOPE_INDEX && $index === null) {
            return null;
        }

        $rows = $submission->getFieldValue($repeaterField->handle);
        if (!is_array($rows)) {
            $rows = [];
        }

        if ($scope === self::SCOPE_COUNT) {
            return count($rows);
        }

        $rowValues = [];

        foreach ($rows as $rowIndex => $row) {
            $rowValues[] = self::_extractRowValue($row, $subPath);
        }

        return match ($scope) {
            self::SCOPE_FIRST => $rowValues[0] ?? null,
            self::SCOPE_LAST => $rowValues !== [] ? $rowValues[array_key_last($rowValues)] : null,
            self::SCOPE_INDEX => $rowValues[$index] ?? null,
            self::SCOPE_ALL => array_values(array_filter(
                $rowValues,
                static fn(mixed $value): bool => $value !== null && $value !== '',
            )),
            self::SCOPE_ROWS => self::_resolveRowsScope($rowValues, $params),
            default => null,
        };
    }

    public static function parseSelectorAndScope(string $selector, array $params = []): array
    {
        $scope = self::_normalizeScope($params['scope'] ?? null);
        $index = isset($params['index']) && is_numeric($params['index']) ? (int)$params['index'] : null;
        $rowsExpression = isset($params['rows']) ? trim((string)$params['rows']) : '';

        if ($scope === self::SCOPE_ROWS && $rowsExpression === '') {
            $scope = null;
        }

        $parts = array_values(array_filter(explode(':', $selector), static fn(string $part): bool => $part !== ''));

        if ($parts !== [] && is_numeric($parts[0]) && $scope === null) {
            $scope = self::SCOPE_INDEX;
            $index = (int)$parts[0];
            array_shift($parts);
        }

        $subPath = implode('.', $parts);

        return [$subPath, $scope, $index];
    }

    /**
     * Parse a 1-based row selection expression into 0-based row indices.
     *
     * Supported syntax:
     * - "1,3,5" comma-separated rows
     * - "1-3,5" inclusive ranges
     * - "even" / "odd"
     * - "every:N" every Nth row starting at row 1 (e.g. every:2 → 1,3,5…)
     */
    public static function parseRowsExpression(string $expression, int $rowCount): array
    {
        $expression = strtolower(trim($expression));

        if ($expression === '' || $rowCount <= 0) {
            return [];
        }

        if ($expression === 'even') {
            return self::_filterParityIndices($rowCount, false);
        }

        if ($expression === 'odd') {
            return self::_filterParityIndices($rowCount, true);
        }

        if (preg_match('/^every:(\d+)$/', $expression, $matches)) {
            $step = max(1, (int)$matches[1]);
            $indices = [];

            for ($row = 1; $row <= $rowCount; $row += $step) {
                $indices[] = $row - 1;
            }

            return $indices;
        }

        $indices = [];

        foreach (preg_split('/\s*,\s*/', $expression) ?: [] as $segment) {
            $segment = trim($segment);

            if ($segment === '') {
                continue;
            }

            if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $segment, $matches)) {
                $start = (int)$matches[1];
                $end = (int)$matches[2];

                if ($start > $end) {
                    [$start, $end] = [$end, $start];
                }

                for ($row = $start; $row <= $end; $row++) {
                    if ($row >= 1 && $row <= $rowCount) {
                        $indices[] = $row - 1;
                    }
                }

                continue;
            }

            if (is_numeric($segment)) {
                $row = (int)$segment;

                if ($row >= 1 && $row <= $rowCount) {
                    $indices[] = $row - 1;
                }
            }
        }

        $indices = array_values(array_unique($indices));
        sort($indices);

        return $indices;
    }

    public static function requiresScope(Submission $submission, string $fieldReference, string $selector, array $params = []): bool
    {
        $field = self::_findFieldByReference($submission, $fieldReference);

        if (!$field instanceof RepeatableParentFieldInterface) {
            return false;
        }

        [, $scope] = self::parseSelectorAndScope($selector, $params);

        return $scope === null && trim($selector) !== '';
    }

    private static function _resolveRowsScope(array $rowValues, array $params): mixed
    {
        $rowsExpression = trim((string)($params['rows'] ?? ''));

        if ($rowsExpression === '') {
            return [];
        }

        $indices = self::parseRowsExpression($rowsExpression, count($rowValues));

        if ($indices === []) {
            return [];
        }

        if (count($indices) === 1) {
            return $rowValues[$indices[0]] ?? null;
        }

        $values = [];

        foreach ($indices as $index) {
            $value = $rowValues[$index] ?? null;

            if ($value !== null && $value !== '') {
                $values[] = $value;
            }
        }

        return array_values($values);
    }

    private static function _filterParityIndices(int $rowCount, bool $odd): array
    {
        $indices = [];

        for ($row = 1; $row <= $rowCount; $row++) {
            $isOdd = ($row % 2) === 1;

            if ($odd ? $isOdd : !$isOdd) {
                $indices[] = $row - 1;
            }
        }

        return $indices;
    }

    private static function _normalizeScope(mixed $scope): ?string
    {
        if (!is_string($scope)) {
            return null;
        }

        $scope = strtolower(trim($scope));

        return in_array($scope, [
            self::SCOPE_FIRST,
            self::SCOPE_LAST,
            self::SCOPE_INDEX,
            self::SCOPE_ALL,
            self::SCOPE_COUNT,
            self::SCOPE_ROWS,
        ], true) ? $scope : null;
    }

    private static function _extractRowValue(mixed $row, string $subPath): mixed
    {
        if ($subPath === '') {
            return $row;
        }

        if (!is_array($row)) {
            return null;
        }

        return ArrayHelper::getValue($row, $subPath);
    }

    private static function _findFieldByReference(Submission $submission, string $reference): ?FieldInterface
    {
        $reference = trim($reference);

        if ($reference === '') {
            return null;
        }

        foreach ($submission->getFields() as $field) {
            if ((string)($field->reference ?? '') === $reference) {
                return $field;
            }
        }

        return null;
    }
}
