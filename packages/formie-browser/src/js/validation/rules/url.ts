import { validateBrowserValue } from '@verbb/formie-core';
import type { ValidationRuleDefinition } from '#validation/types';

const url: ValidationRuleDefinition = {
    rule: ({ input, getRule }) => {
        const rule = getRule('url');
        return !rule || validateBrowserValue(input.value, { ...(typeof rule === 'object' ? rule : {}), type: 'url' }) === null;
    },
    message: ({ input, label, t }) => {
        return input.getAttribute('data-formie-pattern-url-message')
            ?? input.getAttribute('data-pattern-url-message')
            ?? t('{label} is not a valid URL.', { label });
    },
};

export default url;
