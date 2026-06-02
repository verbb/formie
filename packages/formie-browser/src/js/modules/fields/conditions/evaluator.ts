import { evaluateConditionDefinition, finalizeConditionEvaluation } from '@verbb/formie-core';

import type { ConditionDefinition, ParsedConditionSettings, ConditionInput } from '#modules/fields/conditions/types';
import { resolveConditionSource } from '#modules/fields/conditions/references';
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
): { finalResult: boolean; shouldHide: boolean } {
    const groupedResults = settings.conditions.map((condition) => {
        const inputs = getConditionInputs(condition);
        const source = resolveConditionSource(condition);

        return evaluateConditionDefinition(condition, readConditionValues(inputs, source), {
            visibility: getConditionVisibility(inputs),
        });
    });

    return finalizeConditionEvaluation(settings, groupedResults);
}
