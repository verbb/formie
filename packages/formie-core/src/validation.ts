import { conditionNumber } from './conditions';

export type BrowserValidationRule = { type: string; message?: string; messages?: Record<string, string>; min?: number | null; max?: number | null; [key: string]: unknown };
export type BrowserValidationContext = { label?: string; comparison?: unknown; comparisonLabel?: string; empty?: boolean };
export type BrowserRule = (value: unknown, rule: BrowserValidationRule, context: BrowserValidationContext) => string | null;
const rules = new Map<string, BrowserRule>();
export function registerBrowserValidationRule(type: string, rule: BrowserRule): () => void {
    rules.set(type, rule);
    return () => { if (rules.get(type) === rule) rules.delete(type); };
}
export function browserValueEmpty(value: unknown): boolean {
    return value == null || value === false || (typeof value === 'string' && value.trim() === '') || (Array.isArray(value) && value.length === 0);
}
export function validateBrowserValue(value: unknown, rule: BrowserValidationRule, context: BrowserValidationContext = {}): string | null {
    const empty = context.empty ?? browserValueEmpty(value);
    const label = context.label ?? 'This field';
    const message = (key: string, fallback: string) => rule.messages?.[key] ?? rule.message ?? fallback;
    if (rule.type === 'required') return empty ? message('required', `${label} cannot be blank.`) : null;
    if (empty) return null;
    if (rule.type === 'email') return typeof value === 'string' && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) ? null : message('email', `${label} is not a valid email address.`);
    if (rule.type === 'number') {
        const number = conditionNumber(value);
        if (number === null) return message('number', `${label} is not a valid number.`);
        if (rule.min != null && number < rule.min) return message('numberMin', `${label} must be no less than ${rule.min}.`);
        if (rule.max != null && number > rule.max) return message('numberMax', `${label} must be no greater than ${rule.max}.`);
        return null;
    }
    if (rule.type === 'url') {
        try { const url = new URL(String(value)); if (['http:', 'https:'].includes(url.protocol)) return null; } catch { /* A malformed URL remains a validation error. */ }
        return message('url', `${label} is not a valid URL.`);
    }
    if (rule.type === 'pattern') {
        try {
            const pattern = rule.pattern instanceof RegExp ? rule.pattern : typeof rule.pattern === 'string' ? new RegExp(`^(?:${rule.pattern})$`) : null;
            if (!pattern) return null;
            pattern.lastIndex = 0;
            return pattern.test(String(value)) ? null : message('pattern', `${label} is not a valid format.`);
        } catch { return null; }
    }
    if (rule.type === 'match') return context.comparison === undefined || value === context.comparison ? null : message('match', `${label} must match ${context.comparisonLabel ?? 'the other field'}.`);
    if (rule.type === 'minmaxOptions' && Array.isArray(value)) {
        if (rule.min != null && value.length < rule.min) return message('minOptions', `Please select at least ${rule.min} options.`);
        if (rule.max != null && value.length > rule.max) return message('maxOptions', `Please select no more than ${rule.max} options.`);
    }
    // Unknown/specialist rules defer to server authority until their module registers.
    return rules.get(rule.type)?.(value, rule, context) ?? null;
}
