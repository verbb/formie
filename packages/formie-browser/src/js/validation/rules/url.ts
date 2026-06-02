import type { ValidationRuleDefinition } from '#validation/types';

const url: ValidationRuleDefinition = {
    rule: ({ input, getRule }) => {
        if (!getRule('url') || !input.value || input.value.length < 1) {
            return true;
        }

        try {
            new URL(input.value);
            return true;
        } catch {
            return false;
        }
    },
    message: ({ input, label, t }) => {
        return input.getAttribute('data-formie-pattern-url-message')
            ?? input.getAttribute('data-pattern-url-message')
            ?? t('{attribute} is not a valid URL.', { attribute: label });
    },
};

export default url;
