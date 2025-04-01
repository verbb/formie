export const rule = ({ input, config }) => {
    if (input.type !== 'number') {
        return true; // Only apply to number inputs
    }

    const value = parseFloat(input.value);

    const min = input.hasAttribute('min') ? parseFloat(input.getAttribute('min')) : null;
    const max = input.hasAttribute('max') ? parseFloat(input.getAttribute('max')) : null;

    if (min !== null && value < min) {
        return false;
    }

    if (max !== null && value > max) {
        return false;
    }

    return true;
};

export const message = ({ input, label, t }) => {
    const min = input.hasAttribute('min') ? parseFloat(input.getAttribute('min')) : null;
    const max = input.hasAttribute('max') ? parseFloat(input.getAttribute('max')) : null;

    if (min !== null && max !== null) {
        return t('{attribute} must be between {min} and {max}.', { attribute: label, min, max });
    } if (min !== null) {
        return t('{attribute} must be no less than {min}.', { attribute: label, min });
    } if (max !== null) {
        return t('{attribute} must be no greater than {max}.', { attribute: label, max });
    }

    return t('{attribute} has an invalid value.', { attribute: label });
};

export default {
    rule,
    message,
};
