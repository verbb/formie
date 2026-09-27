import schema from './condition-schema.json';

export type ConditionValueType = 'text' | 'number' | 'boolean' | 'date' | 'time' | 'datetime' | 'collection';
export type ConditionEvaluation = { value: boolean | null; diagnostics: Array<{ code: string; rule?: number }> };
export type ClientConditionRule = { condition: string; value?: unknown; valueType?: ConditionValueType; browserSafe?: boolean };
export type ClientConditionSettings = { showRule: 'show' | 'hide' | 'enable' | 'disable'; conditionRule: 'all' | 'any'; conditions: ClientConditionRule[] };
const invalid = (code: string): ConditionEvaluation => ({ value: null, diagnostics: [{ code }] });
const text = (value: unknown): string | null => typeof value === 'number' && !Number.isFinite(value) ? null : value == null ? '' : ['string', 'number', 'boolean'].includes(typeof value) ? String(value) : null;
const trim = (value: string): string => value.replace(/^[ \t\r\n\v\f]+|[ \t\r\n\v\f]+$/g, '');

export function conditionNumber(value: unknown): number | null {
    if (!['number', 'string'].includes(typeof value) || !/^[+-]?(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)(?:[eE][+-]?[0-9]+)?$/.test(trim(String(value)))) return null;
    const number = Number(value);
    return Number.isFinite(number) ? number : null;
}

function operand(value: unknown, type: ConditionValueType): string | number | boolean | null {
    if (type === 'text') return text(value);
    if (type === 'number') return conditionNumber(value);
    if (type === 'boolean') {
        if (typeof value === 'boolean') return value;
        const string = trim(text(value) ?? '').toLowerCase();
        return ['true', '1', 'yes', 'on'].includes(string) ? true : ['false', '0', 'no', 'off'].includes(string) ? false : null;
    }
    if (value && typeof value === 'object' && !Array.isArray(value)) {
        const parts = value as Record<string, unknown>;
        const required = type === 'date' ? ['year', 'month', 'day'] : type === 'time' ? ['hour', 'minute'] : ['year', 'month', 'day', 'hour', 'minute'];
        if (required.some((part) => !/^[0-9]+$/.test(String(parts[part]))) || '_input' in parts || (parts.second != null && !/^[0-9]+$/.test(String(parts.second)))) return null;
        let hour = Number(parts.hour ?? 0);
        if (parts.ampm != null) {
            if (!['AM', 'PM'].includes(String(parts.ampm)) || hour < 1 || hour > 12) return null;
            hour = hour % 12 + (parts.ampm === 'PM' ? 12 : 0);
        }
        const date = `${String(parts.year ?? 1970).padStart(4, '0')}-${String(parts.month ?? 1).padStart(2, '0')}-${String(parts.day ?? 1).padStart(2, '0')}`;
        const time = `${String(hour).padStart(2, '0')}:${String(parts.minute ?? 0).padStart(2, '0')}:${String(parts.second ?? 0).padStart(2, '0')}`;
        if (type === 'datetime') {
            if (operand(date, 'date') === null || operand(time, 'time') === null) return null;
            const zone = String(parts.timezone ?? 'UTC');
            if (/^(Z|[+-][0-9]{2}:[0-9]{2})$/.test(zone)) return operand(`${date}T${time}${zone}`, 'datetime');
            try {
                const wall = Date.parse(`${date}T${time}Z`);
                const formatter = new Intl.DateTimeFormat('en-GB', { timeZone: zone, year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23' });
                const projected = (instant: number): number => {
                    const p = Object.fromEntries(formatter.formatToParts(instant).map((part) => [part.type, part.value]));
                    return Date.parse(`${p.year.padStart(4, '0')}-${p.month}-${p.day}T${p.hour}:${p.minute}:${p.second}Z`);
                };
                const candidates = new Set([-86400000, 0, 86400000].map((delta) => wall - (projected(wall + delta) - (wall + delta))).filter((instant) => projected(instant) === wall));
                // Gaps and ambiguous local times require an explicit offset.
                return candidates.size === 1 ? [...candidates][0] / 1000 : null;
            } catch { return null; }
        }
        value = type === 'date' ? date : time;
    }
    if (typeof value !== 'string') return null;
    if (type === 'time') {
        const m = value.match(/^([0-9]{2}):([0-9]{2})(?::([0-9]{2}))?$/);
        return m && Number(m[1]) < 24 && Number(m[2]) < 60 && Number(m[3] ?? 0) < 60 ? Number(m[1]) * 3600 + Number(m[2]) * 60 + Number(m[3] ?? 0) : null;
    }
    const m = value.match(type === 'date' ? /^([0-9]{4})-([0-9]{2})-([0-9]{2})$/ : /^([0-9]{4})-([0-9]{2})-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})(Z|[+-][0-9]{2}:[0-9]{2})$/);
    if (!m || Number(m[1]) < 1) return null;
    const year = Number(m[1]);
    const leap = year % 4 === 0 && (year % 100 !== 0 || year % 400 === 0);
    const days = [31, leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    if (Number(m[2]) < 1 || Number(m[2]) > 12 || Number(m[3]) < 1 || Number(m[3]) > days[Number(m[2]) - 1]) return null;
    if (type === 'datetime' && (Number(m[4]) > 23 || Number(m[5]) > 59 || Number(m[6]) > 59 || (m[7] !== 'Z' && (Number(m[7].slice(1, 3)) > 23 || Number(m[7].slice(4)) > 59)))) return null;
    const parsed = Date.parse(type === 'date' ? `${value}T00:00:00Z` : value);
    return Number.isFinite(parsed) ? parsed / 1000 : null;
}

function flatten(value: unknown): unknown[] {
    if (value && typeof value === 'object') return Object.values(value).flatMap(flatten);
    return [value];
}

export function evaluateCondition(operator: string, actual: unknown, expected: unknown, type: ConditionValueType = 'text'): ConditionEvaluation {
    if (!schema.operators[operator as keyof typeof schema.operators]?.includes(type as never)) return invalid('unsupportedOperator');
    if (operator === 'empty' || operator === 'notEmpty') {
        if (actual && typeof actual === 'object' && !Array.isArray(actual) && !['collection', 'date', 'time', 'datetime'].includes(type)) return invalid('invalidValue');
        const empty = actual == null || (typeof actual === 'object' && actual !== null && Object.keys(actual).length === 0) || (typeof actual === 'string' && trim(actual) === '');
        return { value: operator === 'empty' ? empty : !empty, diagnostics: [] };
    }
    if (type === 'collection') {
        if (actual != null && typeof actual !== 'object') return invalid('invalidValue');
        const values = flatten(actual ?? []);
        const expectedValues = Array.isArray(expected) ? expected : [expected];
        if ([...values, ...expectedValues].some((value) => text(value) === null)) return invalid('invalidValue');
        const matches = values.some((value) => expectedValues.some((wanted) => text(value) === text(wanted)));
        return { value: ['!=', 'notContains'].includes(operator) ? !matches : matches, diagnostics: [] };
    }
    const a = operand(actual, type);
    const b = operand(expected, type);
    if (a === null || b === null) return invalid('invalidValue');
    // Unicode code points match PHP's UTF-8 lexical order (including non-BMP text).
    const lexical = (left: string, right: string): number => {
        const l = Array.from(left, (c) => c.codePointAt(0)!);
        const r = Array.from(right, (c) => c.codePointAt(0)!);
        for (let i = 0; i < Math.min(l.length, r.length); i++) if (l[i] !== r[i]) return l[i] - r[i];
        return l.length - r.length;
    };
    const order = type === 'text' ? lexical(String(a), String(b)) : a === b ? 0 : a > b ? 1 : -1;
    const result = { '=': a === b, '!=': a !== b, '>': order > 0, '<': order < 0, contains: String(a).includes(String(b)), notContains: !String(a).includes(String(b)), startsWith: String(a).startsWith(String(b)), endsWith: String(a).endsWith(String(b)) };
    return { value: result[operator as keyof typeof result], diagnostics: [] };
}

export function combineConditions(mode: string, results: ConditionEvaluation[]): ConditionEvaluation {
    const diagnostics = results.flatMap((result, rule) => result.diagnostics.map((item) => ({ ...item, rule })));
    if (!['all', 'any'].includes(mode)) return invalid('invalidSchema');
    if (diagnostics.length || results.some((result) => result.value === null)) return { value: null, diagnostics };
    return { value: mode === 'all' ? results.every((result) => result.value === true) : results.some((result) => result.value === true), diagnostics: [] };
}

// DOM input conversion is explicit; typed consumers call evaluateCondition with their projection.
export function evaluateConditionDefinition(condition: ClientConditionRule, actualValues: string[], options: { visibility?: boolean | null } = {}): ConditionEvaluation {
    if (condition.browserSafe === false) return invalid('serverOnlyReference');
    const type = condition.valueType ?? (actualValues.length > 1 ? 'collection' : 'text');
    return evaluateCondition(condition.condition, type === 'collection' ? actualValues : actualValues[0] ?? null, condition.value, type);
}

export function finalizeConditionEvaluation(settings: Pick<ClientConditionSettings, 'conditionRule' | 'showRule'>, results: Array<ConditionEvaluation | boolean>): { finalResult: boolean; shouldHide: boolean; evaluation: ConditionEvaluation } {
    const evaluation = combineConditions(settings.conditionRule, results.map((result) => typeof result === 'boolean' ? { value: result, diagnostics: [] } : result));
    const finalResult = evaluation.value === true;
    return { finalResult, shouldHide: ['show', 'enable'].includes(settings.showRule) ? !finalResult : finalResult, evaluation };
}
