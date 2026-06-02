import type { ValidationRuleDefinition } from '#validation/types';

const minmax: ValidationRuleDefinition = {
    rule: ({ input }) => {
        if (input.type !== 'number' || input.value.trim() === '') {
            return true;
        }

        const value = parseFloat(input.value);
        const min = input.hasAttribute('min') ? parseFloat(input.getAttribute('min') || '') : null;
        const max = input.hasAttribute('max') ? parseFloat(input.getAttribute('max') || '') : null;

        if (Number.isNaN(value)) {
            return false;
        }

        if (min !== null && value < min) {
            return false;
        }

        if (max !== null && value > max) {
            return false;
        }

        return true;
    },
    message: ({ input, label, t }) => {
        const min = input.hasAttribute('min') ? parseFloat(input.getAttribute('min') || '') : null;
        const max = input.hasAttribute('max') ? parseFloat(input.getAttribute('max') || '') : null;

        if (min !== null && max !== null) {
            return t('{attribute} must be between {min} and {max}.', { attribute: label, min, max });
        }

        if (min !== null) {
            return t('{attribute} must be no less than {min}.', { attribute: label, min });
        }

        if (max !== null) {
            return t('{attribute} must be no greater than {max}.', { attribute: label, max });
        }

        return t('{attribute} has an invalid value.', { attribute: label });
    },
};

export default minmax;
