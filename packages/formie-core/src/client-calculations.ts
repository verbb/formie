import {
    coerceCalculationVariables,
    evaluateCalculationExpression,
    getCalculationFormula,
    getCalculationVariableEntries,
    readCalculationVariableValue,
    type CalculationOptions,
    type CalculationVariable,
} from './calculations.js';
import { allFields, compositePartDefinitions, isCalculationField, isCompositeField, isRepeatableField, repeaterFieldDefinitions } from './schema.js';
import type { ClientFieldDefinition, ClientFormState } from './types.js';

function calculationOptions(state: ClientFormState, field: ClientFieldDefinition): CalculationOptions | null {
    const refs = new Set(field.moduleRefs || []);
    const entry = state.definition.modules.entries.find((module) => refs.has(module.key) && module.moduleId === 'formie:calculations');

    return entry ? entry.config as CalculationOptions : null;
}

const ROW_SCOPES = new Set(['first', 'last', 'index', 'all', 'count', 'rows']);

function toStrings(value: unknown): string | string[] {
    if (Array.isArray(value)) {
        return value.filter((item) => item != null && typeof item !== 'object').map((item) => String(item));
    }

    return value == null || typeof value === 'object' ? '' : String(value);
}

function readPath(value: unknown, path: string[]): unknown {
    return path.reduce<unknown>((current, key) => current && typeof current === 'object' && !Array.isArray(current) ? (current as Record<string, unknown>)[key] : undefined, value);
}

function flattenCells(cells: Array<string | string[]>): string[] {
    return cells.flatMap((cell) => Array.isArray(cell) ? cell : (cell === '' ? [] : [cell]));
}

/** Row numbers like `1-3,5`, `even`, `odd` or `every:2`, as the server-rendered module reads them. */
export function parseRowsExpression(expression: string, rowCount: number): number[] {
    const normalized = String(expression || '').trim().toLowerCase();
    const indices: number[] = [];

    if (!normalized || rowCount <= 0) {
        return [];
    }

    if (normalized === 'even' || normalized === 'odd') {
        for (let row = 1; row <= rowCount; row++) {
            if ((row % 2 === 0) === (normalized === 'even')) {
                indices.push(row - 1);
            }
        }

        return indices;
    }

    const every = normalized.match(/^every:(\d+)$/);

    if (every) {
        for (let row = 1; row <= rowCount; row += Math.max(1, Number.parseInt(every[1] || '1', 10))) {
            indices.push(row - 1);
        }

        return indices;
    }

    normalized.split(/\s*,\s*/).forEach((segment) => {
        const range = segment.match(/^(\d+)\s*-\s*(\d+)$/);

        if (range) {
            const [start, end] = [Number(range[1]), Number(range[2])].sort((left, right) => left - right);

            for (let row = Math.max(1, start); row <= Math.min(rowCount, end); row++) {
                indices.push(row - 1);
            }

            return;
        }

        const row = Number.parseInt(segment, 10);

        if (Number.isFinite(row) && row >= 1 && row <= rowCount) {
            indices.push(row - 1);
        }
    });

    return [...new Set(indices)].sort((left, right) => left - right);
}

type RowContext = { handle: string; index: number };

// Reads a source like `quantity`, `group.part` or `repeater.rowField` from form values keyed by field ID.
function sourceValue(state: ClientFormState, variable: CalculationVariable, row?: RowContext): string | string[] {
    const [handle, ...path] = (variable.sourceKey || '').split('.').filter(Boolean);
    const source = allFields(state.definition).find((field) => field.handle === handle);
    const value = source ? state.values[source.id] : undefined;
    const scope = String(variable.scope || '').trim().toLowerCase();

    // A calculation in a Repeater row reads the other fields of its own row.
    if (row && !scope && handle === row.handle && path.length > 0) {
        const rows = Array.isArray(value) ? value : [];

        return toStrings(readPath(rows[row.index], /^\d+$/.test(path[0]) ? path.slice(1) : path));
    }

    // Repeater and Table rows can be picked by position, as in server-rendered forms.
    if (ROW_SCOPES.has(scope) && path.length > 0) {
        const columnPath = path.length > 1 && /^\d+$/.test(path[0]) ? path.slice(1) : path;
        const rows = Array.isArray(value) ? value : [];
        const cells = rows.map((row) => toStrings(readPath(row, columnPath)));

        if (scope === 'count') {
            return String(rows.length);
        }

        if (scope === 'first') {
            return cells[0] ?? '';
        }

        if (scope === 'last') {
            return cells[cells.length - 1] ?? '';
        }

        if (scope === 'index') {
            return cells[Number.parseInt(String(variable.index ?? '0'), 10)] ?? '';
        }

        if (scope === 'all') {
            return flattenCells(cells);
        }

        const indices = parseRowsExpression(String(variable.rows || ''), cells.length);

        return indices.length === 1 ? (cells[indices[0]] ?? '') : (indices.length === 0 ? '' : flattenCells(indices.map((index) => cells[index])));
    }

    let values: unknown[] = [value];

    for (const key of path) {
        values = values.flatMap((item) => Array.isArray(item) ? item.map((row) => readPath(row, [key])) : [readPath(item, [key])]);
    }

    const strings = values.flatMap((item) => Array.isArray(item) ? item : [item])
        .filter((item) => item != null && typeof item !== 'object')
        .map((item) => String(item));

    return path.length > 0 || Array.isArray(values[0]) ? strings : (strings[0] ?? '');
}

type CalculationOccurrence = {
    field: ClientFieldDefinition;
    // The field's state key, such as `12`, `12.total` or `12.0.total`.
    key: string;
    path: Array<string | number>;
    row?: RowContext;
};

function calculationOccurrences(state: ClientFormState, values: ClientFormState['values']): CalculationOccurrence[] {
    return allFields(state.definition).flatMap((field): CalculationOccurrence[] => {
        if (isCalculationField(field)) {
            return [{ field, key: field.id, path: [field.id] }];
        }

        if (isCompositeField(field)) {
            return compositePartDefinitions(field).filter(isCalculationField).map((part) => ({
                field: part,
                key: `${field.id}.${part.handle}`,
                path: [field.id, part.handle],
            }));
        }

        if (isRepeatableField(field)) {
            const rows = Array.isArray(values[field.id]) ? values[field.id] as unknown[] : [];
            const calculations = repeaterFieldDefinitions(field).filter(isCalculationField);

            return rows.flatMap((_, index) => calculations.map((rowField) => ({
                field: rowField,
                key: `${field.id}.${index}.${rowField.handle}`,
                path: [field.id, index, rowField.handle],
                row: { handle: field.handle, index },
            })));
        }

        return [];
    });
}

function readValue(values: ClientFormState['values'], path: Array<string | number>): unknown {
    return path.slice(1).reduce<unknown>((current, key) => {
        return current && typeof current === 'object' ? (current as Record<string | number, unknown>)[key] : undefined;
    }, values[path[0] as string]);
}

function writeValue(values: ClientFormState['values'], path: Array<string | number>, result: unknown): ClientFormState['values'] {
    const write = (current: unknown, keys: Array<string | number>): unknown => {
        const [key, ...rest] = keys;
        const next = rest.length === 0 ? result : write(current && typeof current === 'object' ? (current as Record<string | number, unknown>)[key] : undefined, rest);

        if (Array.isArray(current)) {
            return current.map((item, index) => index === key ? next : item);
        }

        return { ...(current && typeof current === 'object' ? current as Record<string, unknown> : {}), [key]: next };
    };

    return write(values, path) as ClientFormState['values'];
}

function calculate(state: ClientFormState, occurrence: CalculationOccurrence): string | number | null {
    const options = calculationOptions(state, occurrence.field);
    const formula = options ? getCalculationFormula(options) : '';

    if (!options || !formula) {
        return null;
    }

    const variables: Record<string, unknown> = {};

    getCalculationVariableEntries(options).forEach(([key, variable]) => {
        variables[key] = readCalculationVariableValue(variable, sourceValue(state, variable, occurrence.row));
    });

    try {
        const result = evaluateCalculationExpression(formula, coerceCalculationVariables(variables, options.formatting), options);

        return typeof result === 'number' && !Number.isFinite(result) ? '' : result;
    } catch {
        // An incomplete formula, such as one waiting on an empty source, has no answer yet.
        return '';
    }
}

/**
 * Calculations fields are read-only answers worked out from other fields. Server-rendered forms work them out
 * from the page's inputs, so client-rendered forms work them out from the form values instead, including
 * Calculations fields in Groups and Repeater rows.
 */
export function deriveCalculationValues(state: ClientFormState): ClientFormState {
    let values = state.values;
    let occurrences = calculationOccurrences(state, values);

    // A calculation can read another one later in the form, so repeat until the answers settle. A formula that
    // reads itself never settles, so the passes are capped by the number of Calculations fields, not rows.
    const passes = new Set(occurrences.map((occurrence) => occurrence.field.id || occurrence.field.handle)).size + 1;

    for (let pass = 0; pass < passes; pass++) {
        const previous = values;

        for (const occurrence of occurrences) {
            // Conditions clear a hidden field's answer, so it isn't worked out again.
            if (state.fieldStates?.[occurrence.key]?.hidden === true) {
                continue;
            }

            // A Group or Repeater row that conditions cleared stays cleared, rather than coming back with only its answer.
            const parent = occurrence.path.length > 1 ? readValue(values, occurrence.path.slice(0, -1)) : values;

            if (!parent || typeof parent !== 'object') {
                continue;
            }

            const result = calculate({ ...state, values }, occurrence);

            if (result !== null && readValue(values, occurrence.path) !== result) {
                values = writeValue(values, occurrence.path, result);
            }
        }

        if (values === previous) {
            break;
        }

        occurrences = calculationOccurrences(state, values);
    }

    return values === state.values ? state : { ...state, values };
}
