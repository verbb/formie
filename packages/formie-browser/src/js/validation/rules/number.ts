import { validateBrowserValue } from '@verbb/formie-core';
import type { ValidationRuleDefinition } from '#validation/types';

const number: ValidationRuleDefinition = {
    rule: ({ input, getRule }) => {
        const rule = getRule('number');
        return !rule || validateBrowserValue(input.value, { ...(typeof rule === 'object' ? rule : {}), type: 'number' }) === null;
    },
    message: ({ input, label, getRule, t }) => {
        const rule = getRule('number');
        return validateBrowserValue(input.value, { ...(typeof rule === 'object' ? rule : {}), type: 'number' }, { label }) ?? t('{label} is not a valid number.', { label });
    },
};

export default number;
