import type { ValidationRuleDefinition } from '#validation/types';

const number: ValidationRuleDefinition = {
    rule: ({ input, getRule }) => {
        const rule = getRule('number');

        if (!rule || !input.value || input.value.trim() === '') {
            return true;
        }

        const value = parseFloat(input.value);

        if (Number.isNaN(value)) {
            return false;
        }

        if (rule !== true && typeof rule === 'object') {
            const min = typeof rule.min === 'number' ? rule.min : null;
            const max = typeof rule.max === 'number' ? rule.max : null;

            if (min !== null && value < min) {
                return false;
            }

            if (max !== null && value > max) {
                return false;
            }
        }

        return true;
    },
    message: ({ input, label, getRule, t }) => {
        const rule = getRule('number');
        const min = rule !== true && rule && typeof rule === 'object' && typeof rule.min === 'number' ? rule.min : null;
        const max = rule !== true && rule && typeof rule === 'object' && typeof rule.max === 'number' ? rule.max : null;

        if (min !== null && max !== null) {
            return input.getAttribute('data-formie-validation-number-min-message')
                ?? t('{label} must be no less than {min}.', { label, min });
        }

        if (min !== null) {
            return input.getAttribute('data-formie-validation-number-min-message')
                ?? t('{label} must be no less than {min}.', { label, min });
        }

        if (max !== null) {
            return input.getAttribute('data-formie-validation-number-max-message')
                ?? t('{label} must be no greater than {max}.', { label, max });
        }

        return input.getAttribute('data-formie-validation-number-message')
            ?? input.getAttribute('data-formie-pattern-number-message')
            ?? input.getAttribute('data-pattern-number-message')
            ?? t('{label} is not a valid number.', { label });
    },
};

export default number;
