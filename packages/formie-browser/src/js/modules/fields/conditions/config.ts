import type { ParsedConditionSettings, ConditionDefinition, ConditionSource } from '#modules/fields/conditions/types';

export const CONDITION_SELECTOR = '[data-formie-conditions]';

function parseConditionSource(value: unknown): ConditionSource | null {
    if (!value || typeof value !== 'object') {
        return null;
    }

    const candidate = value as Record<string, unknown>;
    const transformerParams = candidate.transformerParams;

    return {
        raw: typeof candidate.raw === 'string' ? candidate.raw : '',
        target: typeof candidate.target === 'string' ? candidate.target : '',
        handle: typeof candidate.handle === 'string' ? candidate.handle : '',
        selector: typeof candidate.selector === 'string' ? candidate.selector : '',
        defaultValue: typeof candidate.defaultValue === 'string' ? candidate.defaultValue : '',
        transformerId: typeof candidate.transformerId === 'string' ? candidate.transformerId : '',
        transformerParams: transformerParams && typeof transformerParams === 'object'
            ? Object.fromEntries(Object.entries(transformerParams as Record<string, unknown>).map(([key, item]) => {
                return [key, String(item ?? '')];
            }))
            : {},
        isValid: candidate.isValid !== false,
    };
}

export function getConditionNodes(root: Element): Element[] {
    const nodes = Array.from(root.querySelectorAll(CONDITION_SELECTOR));

    if (root.matches(CONDITION_SELECTOR)) {
        return [root, ...nodes];
    }

    return nodes;
}

export function parseConditionSettings(node: Element): ParsedConditionSettings | null {
    const raw = node.getAttribute('data-formie-conditions');

    if (!raw) {
        return null;
    }

    try {
        const parsed = JSON.parse(raw) as Record<string, unknown>;
        const conditions = Array.isArray(parsed.conditions)
            ? parsed.conditions.filter((condition): condition is ConditionDefinition => {
                if (!condition || typeof condition !== 'object') {
                    return false;
                }

                const candidate = condition as Record<string, unknown>;
                return typeof candidate.field === 'string' && typeof candidate.condition === 'string';
            }).map((condition) => {
                const candidate = condition as Record<string, unknown>;

                return {
                    field: condition.field,
                    source: parseConditionSource(candidate.source),
                    condition: condition.condition,
                    value: condition.value,
                };
            })
            : [];

        return {
            showRule: parsed.showRule === 'hide' ? 'hide' : 'show',
            conditionRule: parsed.conditionRule === 'any' ? 'any' : 'all',
            clearOnHide: parsed.clearOnHide !== false,
            isNested: Boolean(parsed.isNested),
            conditions,
        };
    } catch (error) {
        console.error('[formie] Invalid condition JSON.', error);
        return null;
    }
}
