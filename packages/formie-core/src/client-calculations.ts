import {
    coerceCalculationVariables,
    evaluateCalculationExpression,
    getCalculationFormula,
    getCalculationVariableEntries,
    readCalculationVariableValue,
    type CalculationOptions,
    type CalculationVariable,
} from './calculations.js';
import { allFields, isCalculationField } from './schema.js';
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

// Reads a source like `quantity`, `group.part` or `repeater.rowField` from form values keyed by field ID.
function sourceValue(state: ClientFormState, variable: CalculationVariable): string | string[] {
    const [handle, ...path] = (variable.sourceKey || '').split('.').filter(Boolean);
    const source = allFields(state.definition).find((field) => field.handle === handle);
    const value = source ? state.values[source.id] : undefined;
    const scope = String(variable.scope || '').trim().toLowerCase();

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

/**
 * Calculations fields are read-only answers worked out from other fields. Server-rendered forms work them out
 * from the page's inputs, so client-rendered forms work them out from the form values instead. Only top-level
 * Calculations fields are worked out here.
 */
export function deriveCalculationValues(state: ClientFormState): ClientFormState {
    const calculations = allFields(state.definition).filter(isCalculationField);

    if (calculations.length === 0) {
        return state;
    }

    let values = state.values;

    // A calculation can read another one later in the form, so repeat until the answers settle.
    for (let pass = 0; pass < calculations.length; pass++) {
        const previous = values;

        for (const field of calculations) {
            // Conditions clear a hidden field's answer, so it isn't worked out again.
            if (state.fieldStates?.[field.id]?.hidden === true) {
                continue;
            }

            const options = calculationOptions(state, field);
            const formula = options ? getCalculationFormula(options) : '';

            if (!options || !formula) {
                continue;
            }

            const variables: Record<string, unknown> = {};

            getCalculationVariableEntries(options).forEach(([key, variable]) => {
                variables[key] = readCalculationVariableValue(variable, sourceValue({ ...state, values }, variable));
            });

            let result: string | number;

            try {
                result = evaluateCalculationExpression(formula, coerceCalculationVariables(variables, options.formatting), options);
            } catch {
                // An incomplete formula, such as one waiting on an empty source, has no answer yet.
                result = '';
            }

            if (typeof result === 'number' && !Number.isFinite(result)) {
                result = '';
            }

            if (values[field.id] !== result) {
                values = { ...values, [field.id]: result };
            }
        }

        if (values === previous) {
            break;
        }
    }

    return values === state.values ? state : { ...state, values };
}
