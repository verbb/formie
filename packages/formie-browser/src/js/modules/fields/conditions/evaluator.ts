import { evaluateCondition, finalizeConditionEvaluation } from '@verbb/formie-core';

import type { ConditionDefinition, ParsedConditionSettings } from '#modules/fields/conditions/types';
import { resolveConditionSource } from '#modules/fields/conditions/references';
import { readSubmissionConditionValues } from '#modules/fields/conditions/submission-context';
import { readConditionValues, readConditionProjection } from '#modules/fields/conditions/values';

export function evaluateConditionSettings(
    settings: ParsedConditionSettings,
    getConditionInputs: (condition: ConditionDefinition) => Array<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>,
    options: {
        root?: Element;
        from?: Element;
    } = {},
): { finalResult: boolean; shouldHide: boolean } {
    const root = options.root;
    const from = options.from || root;

    const groupedResults = settings.conditions.map((condition) => {
        const inputs = getConditionInputs(condition);
        const source = resolveConditionSource(condition);

        // `{submission:*}` is not a DOM field — read the snapshot emitted on the form.
        const actualValues = source?.target === 'submission' && root && from
            ? readSubmissionConditionValues(root, source, from)
            : readConditionValues(inputs, source);

        if (condition.browserSafe === false || Boolean(source?.transformerId) || source?.isValid === false || (!inputs.length && source?.target !== 'submission')) return { value: null, diagnostics: [{ code: 'unresolvedReference' }] };
        const type = condition.valueType ?? (actualValues.length > 1 ? 'collection' : 'text');
        const value = source?.target === 'submission' ? (type === 'collection' ? actualValues : actualValues[0] ?? null) : readConditionProjection(inputs, source, type, Number(from?.querySelector('input,select,textarea')?.getAttribute('name')?.match(/\[([0-9]+)\]/)?.[1]));
        if (value && typeof value === 'object' && 'conditionDiagnostic' in value) return { value: null, diagnostics: [{ code: String(value.conditionDiagnostic) }] };
        return evaluateCondition(condition.condition, value, condition.value, type);
    });

    return finalizeConditionEvaluation(settings, groupedResults);
}
