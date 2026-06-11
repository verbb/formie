import { fieldKeyToInputName, normalizeFieldKey } from '#utils/field-references.keys';
import { resolveFieldReferenceLive } from '#utils/field-references.resolver';
import type { FieldValueRegistry, ResolveFieldValueResult } from '#utils/field-references.types';

export type RowScopeParams = {
    scope?: string;
    index?: string | number;
    rows?: string;
    fieldKind?: string;
};

const ROW_SCOPE_VALUES = new Set(['first', 'last', 'index', 'all', 'count', 'rows']);

function escapeRegExp(value: string): string {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

export function parseRowsExpression(expression: string, rowCount: number): number[] {
    const normalized = String(expression || '').trim().toLowerCase();

    if (!normalized || rowCount <= 0) {
        return [];
    }

    if (normalized === 'even') {
        const indices: number[] = [];

        for (let row = 1; row <= rowCount; row++) {
            if (row % 2 === 0) {
                indices.push(row - 1);
            }
        }

        return indices;
    }

    if (normalized === 'odd') {
        const indices: number[] = [];

        for (let row = 1; row <= rowCount; row++) {
            if (row % 2 === 1) {
                indices.push(row - 1);
            }
        }

        return indices;
    }

    const everyMatch = normalized.match(/^every:(\d+)$/);

    if (everyMatch) {
        const step = Math.max(1, Number.parseInt(everyMatch[1] || '1', 10));
        const indices: number[] = [];

        for (let row = 1; row <= rowCount; row += step) {
            indices.push(row - 1);
        }

        return indices;
    }

    const indices: number[] = [];

    normalized.split(/\s*,\s*/).forEach((segment) => {
        const trimmed = segment.trim();

        if (!trimmed) {
            return;
        }

        const rangeMatch = trimmed.match(/^(\d+)\s*-\s*(\d+)$/);

        if (rangeMatch) {
            let start = Number.parseInt(rangeMatch[1] || '0', 10);
            let end = Number.parseInt(rangeMatch[2] || '0', 10);

            if (start > end) {
                [start, end] = [end, start];
            }

            for (let row = start; row <= end; row++) {
                if (row >= 1 && row <= rowCount) {
                    indices.push(row - 1);
                }
            }

            return;
        }

        const row = Number.parseInt(trimmed, 10);

        if (Number.isFinite(row) && row >= 1 && row <= rowCount) {
            indices.push(row - 1);
        }
    });

    return [...new Set(indices)].sort((left, right) => left - right);
}

function resolveColumnSelector(sourceKey: string): { fieldKey: string; columnKey: string } {
    const normalized = normalizeFieldKey(sourceKey);
    const parts = normalized.split('.').filter(Boolean);

    if (parts.length < 2) {
        return {
            fieldKey: normalized,
            columnKey: parts[parts.length - 1] || '',
        };
    }

    if (parts.length >= 3 && /^\d+$/.test(parts[1] || '')) {
        return {
            fieldKey: parts[0] || '',
            columnKey: parts.slice(2).join('.'),
        };
    }

    return {
        fieldKey: parts[0] || '',
        columnKey: parts.slice(1).join('.'),
    };
}

function listColumnCellKeys(fieldKey: string, columnKey: string, registry: FieldValueRegistry): string[] {
    const pattern = new RegExp(`^${escapeRegExp(fieldKey)}\\.(\\d+)\\.${escapeRegExp(columnKey)}$`);

    return [...registry.keys()]
        .filter((key) => pattern.test(key))
        .sort((left, right) => {
            const leftRow = Number.parseInt(left.split('.')[1] || '0', 10);
            const rightRow = Number.parseInt(right.split('.')[1] || '0', 10);

            return leftRow - rightRow;
        });
}

function readCellValue(key: string, registry: FieldValueRegistry): string | string[] {
    return resolveFieldReferenceLive(key, registry).value;
}

export function getRowScopedWatchNames(
    sourceKey: string,
    params: RowScopeParams,
    registry: FieldValueRegistry,
): Set<string> {
    const watchNames = new Set<string>();
    const { fieldKey, columnKey } = resolveColumnSelector(sourceKey);
    const scope = String(params.scope || '').trim().toLowerCase();

    if (!fieldKey || !columnKey || !ROW_SCOPE_VALUES.has(scope)) {
        const fallback = fieldKeyToInputName(sourceKey);

        if (fallback) {
            watchNames.add(fallback);
            watchNames.add(`${fallback}[]`);
        }

        return watchNames;
    }

    const cellKeys = listColumnCellKeys(fieldKey, columnKey, registry);

    cellKeys.forEach((cellKey) => {
        const entry = registry.get(cellKey);

        if (entry?.names?.length) {
            entry.names.forEach((name) => {
                watchNames.add(name);
            });

            return;
        }

        const fallback = fieldKeyToInputName(cellKey);

        if (fallback) {
            watchNames.add(fallback);
            watchNames.add(`${fallback}[]`);
        }
    });

    return watchNames;
}

export function resolveRowScopedFieldReference(
    sourceKey: string,
    params: RowScopeParams,
    registry: FieldValueRegistry,
): ResolveFieldValueResult {
    const scope = String(params.scope || '').trim().toLowerCase();

    if (!scope || !ROW_SCOPE_VALUES.has(scope)) {
        return resolveFieldReferenceLive(sourceKey, registry);
    }

    const { fieldKey, columnKey } = resolveColumnSelector(sourceKey);

    if (!fieldKey || !columnKey) {
        return resolveFieldReferenceLive(sourceKey, registry);
    }

    const cellKeys = listColumnCellKeys(fieldKey, columnKey, registry);
    const rowValues = cellKeys.map((cellKey) => readCellValue(cellKey, registry));

    if (scope === 'count') {
        return {
            key: `${fieldKey}.${columnKey}`,
            value: String(cellKeys.length),
            found: true,
        };
    }

    if (scope === 'first') {
        const key = cellKeys[0] || `${fieldKey}.0.${columnKey}`;

        return {
            key,
            value: rowValues[0] ?? '',
            found: cellKeys.length > 0,
        };
    }

    if (scope === 'last') {
        const key = cellKeys[cellKeys.length - 1] || `${fieldKey}.0.${columnKey}`;

        return {
            key,
            value: rowValues[rowValues.length - 1] ?? '',
            found: cellKeys.length > 0,
        };
    }

    if (scope === 'index') {
        const index = Number.parseInt(String(params.index ?? '0'), 10);
        const key = `${fieldKey}.${index}.${columnKey}`;

        return resolveFieldReferenceLive(key, registry);
    }

    if (scope === 'all') {
        const values = rowValues.flatMap((value) => {
            if (Array.isArray(value)) {
                return value;
            }

            if (value === '') {
                return [];
            }

            return [value];
        });

        return {
            key: `${fieldKey}.${columnKey}`,
            value: values,
            found: values.length > 0,
        };
    }

    if (scope === 'rows') {
        const indices = parseRowsExpression(String(params.rows || ''), cellKeys.length);

        if (indices.length === 0) {
            return {
                key: `${fieldKey}.${columnKey}`,
                value: '',
                found: false,
            };
        }

        if (indices.length === 1) {
            const key = cellKeys[indices[0]] || `${fieldKey}.${indices[0]}.${columnKey}`;

            return {
                key,
                value: rowValues[indices[0]] ?? '',
                found: true,
            };
        }

        const values = indices.flatMap((index) => {
            const value = rowValues[index];

            if (Array.isArray(value)) {
                return value;
            }

            if (value === '') {
                return [];
            }

            return [value];
        });

        return {
            key: `${fieldKey}.${columnKey}`,
            value: values,
            found: values.length > 0,
        };
    }

    return resolveFieldReferenceLive(sourceKey, registry);
}
