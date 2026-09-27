import { validateBrowserValue } from '@verbb/formie-core';
import type { ValidationRuleDefinition } from '#validation/types';

const email: ValidationRuleDefinition = {
    rule: ({ input, getRule }) => {
        const rule = getRule('email');
        return !rule || validateBrowserValue(input.value, { ...(typeof rule === 'object' ? rule : {}), type: 'email' }) === null;
    },
    message: ({ input, label, t }) => {
        return input.getAttribute('data-formie-validation-email-message')
            ?? input.getAttribute('data-formie-pattern-email-message')
            ?? input.getAttribute('data-pattern-email-message')
            ?? t('{label} is not a valid email address.', { label });
    },
};

export default email;
