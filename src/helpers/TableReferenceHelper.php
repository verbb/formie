<?php
namespace verbb\formie\helpers;

use verbb\formie\base\FieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Table;

class TableReferenceHelper
{
    // Static Methods
    // =========================================================================

    public static function resolve(
        Submission $submission,
        FieldInterface $tableField,
        string $selector,
        array $params = [],
    ): mixed {
        if (!$tableField instanceof Table) {
            return null;
        }

        [$columnPath, $scope, $index] = RepeaterReferenceHelper::parseSelectorAndScope($selector, $params);

        if ($columnPath === '' || $scope === null) {
            return null;
        }

        if ($scope === RepeaterReferenceHelper::SCOPE_INDEX && $index === null) {
            return null;
        }

        $rows = $submission->getFieldValue($tableField->handle);
        if (!is_array($rows)) {
            $rows = [];
        }

        if ($scope === RepeaterReferenceHelper::SCOPE_COUNT) {
            return count($rows);
        }

        $columnKey = self::_resolveColumnKey($tableField, $columnPath);

        if ($columnKey === null) {
            return null;
        }

        $rowValues = [];

        foreach ($rows as $row) {
            $rowValues[] = self::_extractColumnValue($row, $columnKey);
        }

        return match ($scope) {
            RepeaterReferenceHelper::SCOPE_FIRST => $rowValues[0] ?? null,
            RepeaterReferenceHelper::SCOPE_LAST => $rowValues !== [] ? $rowValues[array_key_last($rowValues)] : null,
            RepeaterReferenceHelper::SCOPE_INDEX => $rowValues[$index] ?? null,
            RepeaterReferenceHelper::SCOPE_ALL => array_values(array_filter(
                $rowValues,
                static fn(mixed $value): bool => $value !== null && $value !== '',
            )),
            RepeaterReferenceHelper::SCOPE_ROWS => self::_resolveRowsScope($rowValues, $params),
            default => null,
        };
    }

    public static function requiresScope(Submission $submission, string $fieldReference, string $selector, array $params = []): bool
    {
        $field = self::_findFieldByReference($submission, $fieldReference);

        if (!$field instanceof Table) {
            return false;
        }

        [, $scope] = RepeaterReferenceHelper::parseSelectorAndScope($selector, $params);

        return $scope === null && trim($selector) !== '';
    }

    public static function getColumnReferenceSelector(Table $field, string $columnId): string
    {
        $columnId = trim($columnId);

        if ($columnId === '') {
            return '';
        }

        foreach ($field->columns as $id => $column) {
            if ((string)$id === $columnId) {
                return (string)$id;
            }

            $handle = trim((string)($column['handle'] ?? ''));

            if ($handle !== '' && $handle === $columnId) {
                return (string)$id;
            }
        }

        return $columnId;
    }

    private static function _resolveRowsScope(array $rowValues, array $params): mixed
    {
        $rowsExpression = trim((string)($params['rows'] ?? ''));

        if ($rowsExpression === '') {
            return [];
        }

        $indices = RepeaterReferenceHelper::parseRowsExpression($rowsExpression, count($rowValues));

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

    private static function _resolveColumnKey(Table $field, string $columnPath): ?string
    {
        $columnPath = trim(str_replace(':', '.', $columnPath));

        if ($columnPath === '') {
            return null;
        }

        $parts = array_values(array_filter(explode('.', $columnPath), static fn(string $part): bool => $part !== ''));

        if ($parts === []) {
            return null;
        }

        if (count($parts) > 1 && is_numeric($parts[0])) {
            array_shift($parts);
        }

        $requested = (string)($parts[0] ?? '');

        if ($requested === '') {
            return null;
        }

        foreach ($field->columns as $id => $column) {
            if ((string)$id === $requested) {
                return (string)$id;
            }

            $handle = trim((string)($column['handle'] ?? ''));

            if ($handle !== '' && $handle === $requested) {
                return (string)$id;
            }
        }

        return array_key_exists($requested, $field->columns) ? $requested : null;
    }

    private static function _extractColumnValue(mixed $row, string $columnKey): mixed
    {
        if (!is_array($row)) {
            return null;
        }

        if (array_key_exists($columnKey, $row)) {
            return $row[$columnKey];
        }

        foreach ($row as $key => $value) {
            if ((string)$key === $columnKey) {
                return $value;
            }
        }

        return ArrayHelper::getValue($row, $columnKey);
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
