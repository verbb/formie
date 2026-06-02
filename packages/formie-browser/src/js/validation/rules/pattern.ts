import type { ValidationRuleDefinition } from '#validation/types';

const pattern: ValidationRuleDefinition = {
    rule: ({ input, config }) => {
        const rawPattern = input.getAttribute('pattern');
        const patternToMatch = rawPattern ? new RegExp(`^(?:${rawPattern})$`) : config.patterns[input.type];

        if (!patternToMatch || !input.value || input.value.length < 1) {
            return true;
        }

        return patternToMatch.test(input.value);
    },
    message: ({ input, label, t }) => {
        const messages = {
            email: t('{attribute} is not a valid email address.', { attribute: label }),
            url: t('{attribute} is not a valid URL.', { attribute: label }),
            number: t('{attribute} is not a valid number.', { attribute: label }),
            default: t('{attribute} is not a valid format.', { attribute: label }),
        };

        return input.getAttribute(`data-formie-pattern-${input.type}-message`)
            ?? input.getAttribute(`data-pattern-${input.type}-message`)
            ?? messages[input.type as keyof typeof messages]
            ?? messages.default;
    },
};

export default pattern;
