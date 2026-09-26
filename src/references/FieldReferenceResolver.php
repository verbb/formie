<?php
namespace verbb\formie\references;

use verbb\formie\base\ElementField;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\base\RepeatableParentFieldInterface;
use verbb\formie\fields\Table;
use verbb\formie\helpers\ElementReferenceHelper;
use verbb\formie\helpers\RepeaterReferenceHelper;
use verbb\formie\models\ReferenceExpression;

/** Resolves form-local identity and selectors through the owning fields. */
final class FieldReferenceResolver
{
    // Public Methods
    // =========================================================================

    public function resolve(ReferenceExpression $expression, ReferenceContext $context): ResolvedReference
    {
        if (!$context->form || !$context->submission) {
            throw new ReferenceException(ReferenceDiagnostic::MissingField);
        }
        $entry = $this->findField($expression->identifier, $context);
        if (!$entry) {
            throw new ReferenceException(ReferenceDiagnostic::MissingField);
        }
        $field = $entry['field'];
        $path = $this->_scopedPath($entry['path'], $context);
        $value = $context->submission->getFieldValue($path);
        $selector = $expression->selector;
        $params = $expression->transformerParams;
        $projection = $selector === '' && !isset($params['scope']) ? 'value' : 'none';
        $selectors = array_map(static fn($item): string => $item->handle, $field->references()->selectors);
        $definition = new ReferenceDefinition('field:' . $field->reference, $field->label, 'fields', $field->valueType(), $selectors, browser: true);

        if (!$field instanceof RepeatableParentFieldInterface && !$field instanceof Table && (isset($params['scope']) || isset($params['rows']) || (isset($params['index']) && !$field instanceof ElementField))) {
            throw new ReferenceException(ReferenceDiagnostic::InvalidRowScope);
        }
        if ($field instanceof RepeatableParentFieldInterface || $field instanceof Table) {
            if ($selector !== '' || isset($params['scope'])) {
                $rowCount = is_array($value) ? count($value) : 0;
                $value = $this->_collection($field, $value, $selector, $params, $context);
                if ($field instanceof RepeatableParentFieldInterface) {
                    [$childPath] = RepeaterReferenceHelper::parseSelectorAndScope($selector, $params);
                    if ($childPath !== '') {
                        $field = $this->findField($entry['path'] . '.' . $childPath, $context)['field'] ?? $field;
                        $scope = $params['scope'] ?? '';
                        $projection = $scope === 'all' || ($scope === 'rows' && count(RepeaterReferenceHelper::parseRowsExpression($params['rows'] ?? '', $rowCount)) !== 1) ? 'collection' : ($scope === 'count' ? 'none' : 'value');
                    }
                }
            }
        } elseif ($selector !== '') {
            if ($field instanceof ElementField) {
                [$property] = ElementReferenceHelper::parseSelector($selector, $params);
                if (!in_array($property, $selectors, true)) {
                    throw new ReferenceException(ReferenceDiagnostic::InvalidSelector);
                }
                $value = ElementReferenceHelper::resolveFromValue($field, $value, $selector, $params);
            } elseif ($field instanceof ParentFieldInterface) {
                $childPath = $path . '.' . str_replace(':', '.', $selector);
                $child = $this->findField($childPath, $context);
                if (!$child && !in_array(str_replace(':', '.', $selector), $selectors, true)) {
                    throw new ReferenceException(ReferenceDiagnostic::InvalidSelector);
                }
                $value = $context->submission->getFieldValue($childPath);
                $field = $child['field'] ?? $field;
                $projection = $child ? 'value' : 'none';
            } else {
                if (!in_array(str_replace(':', '.', $selector), $selectors, true)) {
                    throw new ReferenceException(ReferenceDiagnostic::InvalidSelector);
                }
                $value = $context->submission->getFieldValue($path . '.' . str_replace(':', '.', $selector));
            }
        }
        return new ResolvedReference($expression, $value, $definition, field: $field, fieldProjection: $projection);
    }

    public function findField(string $identifier, ReferenceContext $context): ?array
    {
        $entries = [];
        $this->_index($context->form?->getFields() ?? [], [], $entries);
        // Exact persisted identity always wins; readable selectors are form-local and unambiguous.
        foreach (['reference', 'uid', 'path', 'handle'] as $key) {
            $matches = array_values(array_filter($entries, static fn(array $entry): bool => $entry[$key] === $identifier));
            if (count($matches) > 1) {
                throw new ReferenceException(ReferenceDiagnostic::AmbiguousField);
            }
            if ($matches) {
                return $matches[0];
            }
        }
        return null;
    }

    // Private Methods
    // =========================================================================

    private function _index(array $fields, array $path, array &$entries): void
    {
        foreach ($fields as $field) {
            $parts = [...$path, $field->handle];
            $entries[] = ['field' => $field, 'reference' => (string)$field->reference, 'uid' => (string)$field->uid, 'handle' => $field->handle, 'path' => implode('.', array_filter($parts, 'is_string')), 'parts' => $parts];
            if ($field instanceof ParentFieldInterface) {
                // Row markers carry the persisted parent reference, never an inferred row number.
                $this->_index($field->getFields(), $field instanceof RepeatableParentFieldInterface ? [...$parts, ['row' => (string)$field->reference]] : $parts, $entries);
            }
        }
    }

    private function _scopedPath(string $path, ReferenceContext $context): string
    {
        $entries = [];
        $this->_index($context->form->getFields(), [], $entries);
        foreach ($entries as $entry) {
            if ($entry['path'] !== $path) {
                continue;
            }
            $parts = [];
            foreach ($entry['parts'] as $part) {
                if (is_array($part)) {
                    $row = $context->rows[$part['row']] ?? null;
                    if (!is_int($row) || $row < 0) {
                        throw new ReferenceException(ReferenceDiagnostic::MissingRowScope);
                    }
                    $parentRows = $context->submission->getFieldValue(implode('.', $parts));
                    if (!is_array($parentRows) || !array_key_exists($row, $parentRows)) {
                        throw new ReferenceException(ReferenceDiagnostic::InvalidRowScope);
                    }
                    $parts[] = $row;
                } else {
                    $parts[] = $part;
                }
            }
            return implode('.', $parts);
        }
        return $path;
    }

    private function _collection(FieldInterface $field, mixed $rows, string $selector, array $params, ReferenceContext $context): mixed
    {
        if (isset($params['index']) && !preg_match('/^\d+$/D', (string)$params['index'])) {
            throw new ReferenceException(ReferenceDiagnostic::InvalidRowScope);
        }
        if (isset($params['scope']) && !in_array($params['scope'], ['first', 'last', 'index', 'all', 'count', 'rows', 'current'], true)) {
            throw new ReferenceException(ReferenceDiagnostic::InvalidRowScope);
        }
        if (($params['scope'] ?? '') === 'rows' && !preg_match('/^(?:even|odd|every:[1-9]\d*|[1-9]\d*(?:\s*-\s*[1-9]\d*)?(?:\s*,\s*[1-9]\d*(?:\s*-\s*[1-9]\d*)?)*)$/D', (string)($params['rows'] ?? ''))) {
            throw new ReferenceException(ReferenceDiagnostic::InvalidRowScope);
        }
        [$path, $scope, $index] = RepeaterReferenceHelper::parseSelectorAndScope($selector, $params);
        if (($params['scope'] ?? '') === 'current') {
            $scope = 'index';
            $index = $context->rows[(string)$field->reference] ?? null;
        }
        if ($scope === null) {
            throw new ReferenceException(ReferenceDiagnostic::MissingRowScope);
        }
        $rows = is_array($rows) ? array_values($rows) : [];
        if ($scope === 'count') {
            return count($rows);
        }
        if ($scope === 'index' && (!is_int($index) || $index < 0 || !array_key_exists($index, $rows))) {
            throw new ReferenceException(ReferenceDiagnostic::InvalidRowScope);
        }
        if ($path !== '') {
            if ($field instanceof Table) {
                $columns = array_filter($field->columns, static fn(array $column, $key): bool => (string)$key === $path || ($column['handle'] ?? '') === $path, ARRAY_FILTER_USE_BOTH);
                if (count($columns) !== 1) {
                    throw new ReferenceException(ReferenceDiagnostic::InvalidSelector);
                }
                $path = (string)array_key_first($columns);
            } else {
                $children = [];
                $this->_index($field->getFields(), [], $children);
                $matches = array_filter($children, static fn(array $entry): bool => $entry['path'] === $path);
                if (!$matches) {
                    throw new ReferenceException(ReferenceDiagnostic::InvalidSelector);
                }
            }
        }
        $values = array_map(function($row) use ($path, $context) {
            return $path === '' ? $row : $context->submission->getContentManager()->resolvePathValue($row, $path);
        }, $rows);
        return match ($scope) {
            'first' => $values[0] ?? null,
            'last' => $values ? $values[array_key_last($values)] : null,
            'index' => $values[$index],
            'all' => $values,
            'rows' => $this->_selectedRows($values, (string)($params['rows'] ?? '')),
            default => throw new ReferenceException(ReferenceDiagnostic::InvalidRowScope),
        };
    }

    private function _selectedRows(array $values, string $selection): mixed
    {
        $indices = RepeaterReferenceHelper::parseRowsExpression($selection, count($values));
        $selected = array_map(static fn(int $index) => $values[$index], $indices);
        return count($selected) === 1 ? $selected[0] : $selected;
    }

}
