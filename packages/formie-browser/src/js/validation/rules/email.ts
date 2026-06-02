import type { ValidationRuleDefinition } from '#validation/types';

const email: ValidationRuleDefinition = {
    rule: ({ input, getRule }) => {
        if (!getRule('email') || !input.value || input.value.length < 1) {
            return true;
        }

        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value);
    },
    message: ({ input, label, t }) => {
        return input.getAttribute('data-formie-pattern-email-message')
            ?? input.getAttribute('data-pattern-email-message')
            ?? t('{attribute} is not a valid email address.', { attribute: label });
    },
};

export default email;
