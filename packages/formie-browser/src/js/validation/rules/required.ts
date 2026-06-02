import type { ValidationRuleDefinition } from '#validation/types';

const required: ValidationRuleDefinition = {
    rule: ({ input, getRule }) => {
        if (!getRule('required') || input.type === 'hidden') {
            return true;
        }

        if (input.type === 'checkbox' || input.type === 'radio') {
            const checkboxInputs = input.form?.querySelectorAll(`[name="${input.name}"]:not([type="hidden"]):not([disabled])`) || [];

            if (checkboxInputs.length) {
                return Array.from(checkboxInputs).some((button) => {
                    return button instanceof HTMLInputElement && button.checked;
                });
            }

            return input instanceof HTMLInputElement ? input.checked : true;
        }

        return input.value.trim() !== '';
    },
    message: ({ input, label, t }) => {
        return input.getAttribute('data-formie-required-message')
            ?? input.getAttribute('data-required-message')
            ?? t('{attribute} cannot be blank.', { attribute: label });
    },
};

export default required;
