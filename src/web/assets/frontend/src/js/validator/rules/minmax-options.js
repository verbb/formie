export const rule = ({ field }) => {
    // Just in case we're trying to check against a non-Formie input in the form
    // because we want to check on the type of Formie field for checkboxes
    if (!field) {
        return true;
    }

    if (field.getAttribute('data-field-type') !== 'checkboxes') {
        return true; // Only apply to checkbox inputs
    }

    const $inputs = field.querySelectorAll('input[type="checkbox"]');
    const value = Array.from($inputs).filter((el) => { return el.checked; }).length;

    const min = field.hasAttribute('data-min-options') ? parseFloat(field.getAttribute('data-min-options')) : null;
    const max = field.hasAttribute('data-max-options') ? parseFloat(field.getAttribute('data-max-options')) : null;

    if (min !== null && value < min) {
        return false;
    }

    if (max !== null && value > max) {
        return false;
    }

    return true;
};

export const message = ({ field, label, t }) => {
    if (field) {
        const min = field.hasAttribute('data-min-options') ? parseFloat(field.getAttribute('data-min-options')) : null;
        const max = field.hasAttribute('data-max-options') ? parseFloat(field.getAttribute('data-max-options')) : null;

        if (min !== null && max !== null) {
            return t('{attribute} must select between {min} and {max}.', { attribute: label, min, max });
        } if (min !== null) {
            return t('{attribute} must select no less than {min}.', { attribute: label, min });
        } if (max !== null) {
            return t('{attribute} must select no greater than {max}.', { attribute: label, max });
        }
    }

    return t('{attribute} has an invalid value.', { attribute: label });
};

export default {
    rule,
    message,
};
