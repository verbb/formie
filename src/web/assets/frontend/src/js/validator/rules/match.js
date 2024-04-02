const getSourceField = (field, match) => {
    // Get the source field to match against
    const form = field.closest('form');

    if (!form) {
        return false;
    }

    return form.querySelector(`[data-field-handle="${match}"]`);
};

// eslint-disable-next-line
export const rule = ({ field, input, config, getRule }) => {
    const match = getRule('match');

    // Ignore any field that doesn't have a "match" rule
    if (!match) {
        return true;
    }

    const sourceField = getSourceField(field, match);

    if (!sourceField) {
        return true;
    }

    const sourceInput = sourceField.querySelector(config.fieldsSelector);

    if (!sourceInput) {
        return true;
    }

    // Serialize the form, then lookup value. We only support simple comparing right now
    const sourceValue = sourceInput.value;
    const destinationValue = input.value;

    return sourceValue === destinationValue;
};

// eslint-disable-next-line
export const message = ({ field, label, t, getRule }) => {
    const match = getRule('match');
    const sourceField = getSourceField(field, match);
    const sourceLabel = sourceField?.querySelector('[data-field-label]')?.childNodes[0].textContent?.trim() ?? '';

    return t('{name} must match {value}.', { name: label, value: sourceLabel });
};

export default {
    rule,
    message,
};
