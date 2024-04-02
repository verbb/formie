export const rule = ({ input }) => {
    if (!input.hasAttribute('required') || input.type === 'hidden') {
        return true;
    }

    // For checkboxes (singular and group) and radio buttons
    if (input.type === 'checkbox' || input.type === 'radio') {
        const checkboxInputs = input.form.querySelectorAll(`[name="${input.name}"]:not([type="hidden"])`);

        if (checkboxInputs.length) {
            const checkedInputs = Array.prototype.filter.call(checkboxInputs, ((btn) => {
                return btn.checked;
            }));

            return checkedInputs.length;
        }

        return input.checked;
    }

    return input.value.trim() !== '';
};

export const message = ({ input, label, t }) => {
    return input.getAttribute('data-required-message') ?? t('{attribute} cannot be blank.', { attribute: label });
};

export default {
    rule,
    message,
};
