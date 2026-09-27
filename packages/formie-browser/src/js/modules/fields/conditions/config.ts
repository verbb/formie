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
        handle: typeof candidate.domHandle === 'string' ? candidate.domHandle : typeof candidate.handle === 'string' ? candidate.handle : '',
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
        const effect = String(parsed.effect ?? parsed.showRule ?? 'show');
        const valid = (parsed.version == null || parsed.version === 1) && ['all', 'any'].includes(String(parsed.mode ?? parsed.conditionRule ?? 'all')) && ['show', 'hide', 'enable', 'disable'].includes(effect);
        const rawRules = valid ? parsed.rules ?? parsed.conditions : [null];
        const conditions = Array.isArray(rawRules)
            ? rawRules.map((row) => {
                const candidate = row && typeof row === 'object' ? row as Record<string, unknown> : {};
                return {
                    field: typeof candidate.field === 'string' ? candidate.field : '',
                    source: parseConditionSource(candidate.source),
                    condition: String(candidate.operator ?? candidate.condition ?? ''),
                    valueType: candidate.valueType as ConditionDefinition['valueType'],
                    browserSafe: candidate.browserSafe !== false,
                    value: candidate.value,
                };
            })
            : [{ field: '', condition: '', value: null, browserSafe: false }];

        return {
            showRule: ['hide', 'enable', 'disable'].includes(effect) ? effect as ParsedConditionSettings['showRule'] : 'show',
            conditionRule: (parsed.mode ?? parsed.conditionRule) === 'any' ? 'any' : 'all',
            clearOnHide: parsed.clearOnHide !== false,
            isNested: Boolean(parsed.isNested),
            conditions,
        };
    } catch (error) {
        console.error('[formie] Invalid condition JSON.');
        return { showRule: 'show', conditionRule: 'all', clearOnHide: true, isNested: false, conditions: [{ field: '', condition: '', browserSafe: false }] };
    }
}
