export const getFormField = function($form, handle) {
    // Get the field(s) we're targeting to watch for changes. Note we need to handle multiple fields (checkboxes)
    let $fields = $form.querySelectorAll(`[name="${handle}"]`);

    // Check if we're dealing with multiple fields, like checkboxes. This overrides the above
    const $multiFields = $form.querySelectorAll(`[name="${handle}[]"]`);

    if ($multiFields.length) {
        $fields = $multiFields;
    }

    return $fields;
};

export const getFieldName = function(handle) {
    // Normalise the handle first
    handle = handle.replace('{field:', '').replace('{', '').replace('}', '').replace(']', '').split('[').join('][');

    return `fields[${handle}]`;
};

export const getFieldLabel = function($form, handle) {
    let label = '';

    handle = getFieldName(handle);

    // We'll always get back multiple inputs to normalise checkbox/radios
    const $inputs = getFormField($form, handle);

    if ($inputs) {
        $inputs.forEach(($input) => {
            const $field = $input.closest('[data-field-type]');

            if ($field) {
                const $label = $field.querySelector('[data-field-label]');

                if ($label) {
                    label = $label.childNodes[0].textContent?.trim() ?? '';
                }
            }
        });
    }

    return label;
};

export const getFieldValue = function($form, handle) {
    let value = '';

    handle = getFieldName(handle);

    // We'll always get back multiple inputs to normalise checkbox/radios
    const $inputs = getFormField($form, handle);

    if ($inputs) {
        $inputs.forEach(($input) => {
            if ($input.type === 'checkbox' || $input.type === 'radio') {
                if ($input.checked) {
                    return value = $input.value;
                }
            } else {
                return value = $input.value;
            }
        });
    }

    return value;
};
