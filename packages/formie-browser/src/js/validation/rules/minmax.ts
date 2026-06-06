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
            return t('{label} must be between {min} and {max}.', { label, min, max });
        }

        if (min !== null) {
            return t('{label} must be no less than {min}.', { label, min });
        }

        if (max !== null) {
            return t('{label} must be no greater than {max}.', { label, max });
        }

        return t('{label} has an invalid value.', { label });
    },
};

export default minmax;
