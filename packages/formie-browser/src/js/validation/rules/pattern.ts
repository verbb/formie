import { validateBrowserValue } from '@verbb/formie-core';
import type { ValidationRuleDefinition } from '#validation/types';

const pattern: ValidationRuleDefinition = {
    rule: ({ input, config }) => {
        const pattern = input.getAttribute('pattern') || config.patterns[input.type];
        return validateBrowserValue(input.value, { type: 'pattern', pattern }) === null;
    },
    message: ({ input, label, t }) => {
        const messages = {
            email: t('{label} is not a valid email address.', { label }),
            url: t('{label} is not a valid URL.', { label }),
            number: t('{label} is not a valid number.', { label }),
            default: t('{label} is not a valid format.', { label }),
        };

        return input.getAttribute(`data-formie-pattern-${input.type}-message`)
            ?? input.getAttribute(`data-pattern-${input.type}-message`)
            ?? messages[input.type as keyof typeof messages]
            ?? messages.default;
    },
};

export default pattern;
