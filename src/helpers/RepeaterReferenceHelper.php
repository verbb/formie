<?php
namespace verbb\formie\helpers;

class RepeaterReferenceHelper
{
    // Static Methods
    // =========================================================================

    public static function parseSelectorAndScope(string $selector, array $params = []): array
    {
        $scope = self::_normalizeScope($params['scope'] ?? null);
        $index = isset($params['index']) && is_numeric($params['index']) ? (int)$params['index'] : null;
        $rowsExpression = isset($params['rows']) ? trim((string)$params['rows']) : '';

        if ($scope === self::SCOPE_ROWS && $rowsExpression === '') {
            $scope = null;
        }

        $parts = array_values(array_filter(explode(':', $selector), static fn(string $part): bool => $part !== ''));

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

                for ($row = max(1, $start); $row <= min($rowCount, $end); $row++) {
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


    // Constants
    // =========================================================================

    public const SCOPE_FIRST = 'first';
    public const SCOPE_LAST = 'last';
    public const SCOPE_INDEX = 'index';
    public const SCOPE_ALL = 'all';
    public const SCOPE_COUNT = 'count';
    public const SCOPE_ROWS = 'rows';
}
