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
        return `${label} must be between ${min} and ${max}.`;
    } if (min !== null) {
        return `${label} must be no less than ${min}.`;
    } if (max !== null) {
        return `${label} must be no greater than ${max}.`;
    }

    return `${label} has an invalid value.`;
};

export default {
    rule,
    message,
};
