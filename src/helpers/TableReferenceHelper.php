<?php
namespace verbb\formie\helpers;

use verbb\formie\base\FieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Table;
use verbb\formie\references\ReferenceContext;

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
        return References::resolveValue(References::field((string)$tableField->reference, $selector, $params), ReferenceContext::forSubmission($submission))->requireValue();
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
