import { selectConditionRows } from './condition-projections';
import { combineConditions, evaluateCondition } from './conditions';
import type { ConditionEvaluation, ConditionValueType } from './conditions';
import { allFields, compositePartDefinitions, isRepeatableField, repeaterFieldDefinitions } from './schema';
import type { ClientFieldDefinition, ClientFormState } from './types';

const conditionEffect = (effect?: string): string => ['show', 'hide', 'enable', 'disable'].includes(effect ?? '') ? effect! : 'show';

type Occurrence = { field: ClientFieldDefinition; key: string; path: string[]; parent?: string };
function occurrences(state: ClientFormState): Occurrence[] {
    const result: Occurrence[] = [];
    const walk = (field: ClientFieldDefinition, value: unknown, key: string, path: string[], parent?: string) => {
        result.push({ field, key, path, parent });
        if (isRepeatableField(field)) {
            (Array.isArray(value) ? value : []).forEach((row, index) => repeaterFieldDefinitions(field).forEach((child) => walk(child, row?.[child.handle], `${key}.${index}.${child.handle}`, [...path, String(index), child.handle], key)));
        } else {
            compositePartDefinitions(field).forEach((child) => walk(child, (value as Record<string, unknown>)?.[child.handle], `${key}.${child.handle}`, [...path, child.handle], key));
        }
    };
    allFields(state.definition).forEach((field) => walk(field, state.values[field.id], field.id, [field.handle]));
    return result;
}
function getValue(state: ClientFormState, entry: Occurrence): unknown {
    const [root, ...parts] = entry.key.split('.');
    return parts.reduce<unknown>((value, key) => value && typeof value === 'object' ? (value as Record<string, unknown>)[key] : null, state.values[root]);
}
function sourceFor(rule: NonNullable<ClientFieldDefinition['condition']>['rules'][number], entries: Occurrence[], target?: Occurrence): Occurrence | undefined {
    const found = entries.filter((entry) => entry.field.id === rule.fieldId || entry.field.handle === rule.fieldId || entry.path.filter((part) => !/^\d+$/.test(part)).join('.') === rule.source?.handle);
    return found.find((entry) => entry.parent && entry.parent === target?.parent) ?? found.find((entry) => !entry.parent) ?? (found.length === 1 ? found[0] : undefined);
}
export function evaluateClientCondition(condition: ClientFieldDefinition['condition'], state: ClientFormState, target?: Occurrence): ConditionEvaluation {
    if (!condition) return { value: true, diagnostics: [] };
    if ((condition.version != null && condition.version !== 1) || !['show', 'hide', 'enable', 'disable'].includes(condition.effect)) return { value: null, diagnostics: [{ code: 'invalidSchema' }] };
    const entries = occurrences(state);
    return combineConditions(condition.mode, condition.rules.map((rule) => {
        const source = sourceFor(rule, entries, target);
        if (rule.browserSafe === false || !source) return { value: null, diagnostics: [{ code: rule.browserSafe === false ? 'serverOnlyReference' : 'unresolvedReference' }] };
        if (rule.source?.transformerId) return { value: null, diagnostics: [{ code: 'serverOnlyReference' }] };
        let value = getValue(state, source);
        let selector = (rule.source?.selector ?? '').split(/[.:]/).filter(Boolean);
        const params = { ...(rule.source?.transformerParams ?? {}) };
        const read = (input: unknown, keys: string[]): unknown => keys.reduce<unknown>((item, key) => item && typeof item === 'object' && Object.prototype.hasOwnProperty.call(item, key) ? (item as Record<string, unknown>)[key] : undefined, input);
        if (isRepeatableField(source.field) && (selector.length || params.scope)) {
            if (/^[0-9]+$/.test(selector[0] ?? '') && !params.scope) { params.scope = 'index'; params.index = selector.shift()!; }
            const rows = (Array.isArray(value) ? value : []).map((row) => read(row, selector));
            if (rows.some((row) => row === undefined)) return { value: null, diagnostics: [{ code: 'invalidSelector' }] };
            const current = target?.path[source.path.length];
            const result = selectConditionRows(rows, params, current != null && /^[0-9]+$/.test(current) ? Number(current) : undefined);
            if (result.diagnostic) return { value: null, diagnostics: [{ code: result.diagnostic }] };
            value = params.scope === 'rows' && !Array.isArray(result.value) ? [result.value] : result.value;
        } else if (selector.length) {
            if (source.field.input.fieldKind === 'options' && selector.length === 1 && ['label', 'value'].includes(selector[0])) {
                if (selector[0] === 'label') {
                    const options = (source.field.input.options ?? []) as Array<{ value: string; label: string }>;
                    const label = (item: unknown) => options.find((option) => String(option.value) === String(item))?.label;
                    value = Array.isArray(value) ? value.map(label) : label(value);
                }
            } else if (['date', 'time'].includes(selector[0]) && source.field.type === 'date') {
                // The date operator consumes the same parts directly.
            } else value = read(value, selector);
            if (value === undefined) return { value: null, diagnostics: [{ code: 'invalidSelector' }] };
        }
        if ((value == null || value === '' || (Array.isArray(value) && !value.length)) && rule.source?.defaultValue) value = rule.source.defaultValue;
        const kind = source.field.client?.valueType?.kind ?? source.field.runtime?.valueType?.kind;
        const type: ConditionValueType = rule.valueType ?? (kind === 'number' ? 'number' : kind === 'boolean' ? 'boolean' : Array.isArray(value) ? 'collection' : 'text');
        return evaluateCondition(rule.operator, value, rule.value, type);
    }));
}
export function clientActionAllowed(state: ClientFormState): boolean {
    const condition = state.definition.pages.find((page) => page.id === state.currentPageId)?.actions.primary.condition;
    if (!condition) return true;
    const evaluation = evaluateClientCondition(condition, state);
    return evaluation.value !== null && evaluation.value === (condition.effect === 'show' || condition.effect === 'enable');
}
export function deriveConditionState(state: ClientFormState): ClientFormState {
    // Clone only value containers. Field uploads/Blob objects keep their identity.
    const clone = (value: unknown): unknown => Array.isArray(value) ? value.map(clone) : value && typeof value === 'object' && Object.getPrototypeOf(value) === Object.prototype ? Object.fromEntries(Object.entries(value).map(([key, item]) => [key, clone(item)])) : value;
    const next: ClientFormState = { ...state, values: clone(state.values) as ClientFormState['values'], fieldStates: {}, pageStates: {} };
    const entries = occurrences(next);
    const visiting = new Set<string>();
    const visited = new Set<string>();
    const visit = (entry: Occurrence): void => {
        if (visited.has(entry.key)) return;
        if (visiting.has(entry.key)) {
            next.fieldStates[entry.key] = { hidden: true, disabled: true };
            return;
        }
        visiting.add(entry.key);
        if (entry.parent) { const parent = entries.find((item) => item.key === entry.parent); if (parent) visit(parent); }
        const page = next.definition.pages.find((item) => item.rows.some((row) => row.fields.some((field) => field.id === entry.key.split('.')[0])));
        for (const rule of [...(entry.field.condition?.rules ?? []), ...(page?.condition?.rules ?? [])]) {
            const source = sourceFor(rule, entries, entry);
            if (source) {
                visit(source);
                if (!rule.source?.selector) entries.filter((item) => item.key.startsWith(`${source.key}.`)).forEach(visit);
            }
        }
        const evaluation = evaluateClientCondition(entry.field.condition, next, entry);
        const effect = conditionEffect(entry.field.condition?.effect);
        const hiddenByRule = entry.field.condition ? ['show', 'enable'].includes(effect) ? evaluation.value !== true : evaluation.value === true : false;
        const pageEvaluation = evaluateClientCondition(page?.condition, next);
        const pageHidden = page?.condition ? ['show', 'enable'].includes(conditionEffect(page.condition.effect)) ? pageEvaluation.value !== true : pageEvaluation.value === true : false;
        const parentHidden = entry.parent ? next.fieldStates[entry.parent]?.hidden === true : false;
        const hidden = next.fieldStates[entry.key]?.hidden === true || entry.field.meta?.hidden === true || parentHidden || pageHidden || (['show', 'hide'].includes(effect) && hiddenByRule);
        const disabled = entry.field.meta?.disabled === true || (entry.parent ? next.fieldStates[entry.parent]?.disabled === true : false) || (['enable', 'disable'].includes(effect) && hiddenByRule);
        next.fieldStates[entry.key] = { hidden, disabled };
        if ((hidden || disabled) && (entry.field.condition || parentHidden || pageHidden || disabled)) {
            const [root, ...parts] = entry.key.split('.');
            let container = next.values;
            if (parts.length) {
                let value = container[root];
                for (const part of parts.slice(0, -1)) value = value && typeof value === 'object' ? (value as Record<string, unknown>)[part] : null;
                if (value && typeof value === 'object') (value as Record<string, unknown>)[parts[parts.length - 1]] = null;
            } else container[root] = isRepeatableField(entry.field) ? [] : null;
        }
        visiting.delete(entry.key);
        visited.add(entry.key);
    };
    entries.forEach(visit);
    for (const page of next.definition.pages) {
        const evaluation = evaluateClientCondition(page.condition, next);
        next.pageStates[page.id] = { hidden: page.condition ? ['show', 'enable'].includes(conditionEffect(page.condition.effect)) ? evaluation.value !== true : evaluation.value === true : false };
    }
    if (next.pageStates[next.currentPageId]?.hidden) next.currentPageId = next.definition.pages.find((page) => !next.pageStates[page.id]?.hidden)?.id ?? next.currentPageId;
    return next;
}
