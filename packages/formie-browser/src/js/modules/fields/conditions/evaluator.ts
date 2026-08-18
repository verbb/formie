import { evaluateConditionDefinition, finalizeConditionEvaluation } from '@verbb/formie-core';

import type { ConditionDefinition, ParsedConditionSettings, ConditionInput } from '#modules/fields/conditions/types';
import { resolveConditionSource } from '#modules/fields/conditions/references';
import { readSubmissionConditionValues } from '#modules/fields/conditions/submission-context';
import { readConditionValues } from '#modules/fields/conditions/values';

function isInputVisible(input: ConditionInput): boolean {
    if (
        input.closest('[data-formie-conditionally-hidden]')
        || input.closest('[data-formie-page-hidden]')
        || input.closest('[hidden]')
        || input.closest('[aria-hidden="true"]')
    ) {
        return false;
    }

    return !!(input.offsetWidth || input.offsetHeight || input.getClientRects().length);
}

function getConditionVisibility(inputs: ConditionInput[]): boolean | null {
    if (!inputs.length) {
        return null;
    }

    return inputs.some((input) => {
        return isInputVisible(input);
    });
}

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

        return evaluateConditionDefinition(condition, actualValues, {
            visibility: getConditionVisibility(inputs),
        });
    });

    return finalizeConditionEvaluation(settings, groupedResults);
}
