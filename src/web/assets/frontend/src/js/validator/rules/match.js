const getSourceField = (field) => {
    if (!field) {
        return false;
    }

    let match = field.getAttribute('data-match-field');

    if (!match) {
        return false;
    }

    match = match.replace('{', '').replace('}', '').replace('field:', '');

    // Get the source field to match against
    const form = field.closest('form');

    if (!form) {
        return false;
    }

    return form.querySelector(`[data-field-handle="${match}"]`);
};

export const rule = ({ field, input, config }) => {
    const sourceField = getSourceField(field);

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

export const message = ({ field, label, t }) => {
    let sourceLabel = '';
    const sourceField = getSourceField(field);

    if (sourceField) {
        sourceLabel = sourceField.querySelector('[data-field-label]')?.childNodes[0].textContent?.trim() ?? '';
    }

    return t('{name} must match {value}.', { name: label, value: sourceLabel });
};

export default {
    rule,
    message,
};
