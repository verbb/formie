import { validateBrowserValue } from '@verbb/formie-core';
import type { ValidationRuleDefinition } from '#validation/types';

const required: ValidationRuleDefinition = {
    rule: ({ input, getRule }) => {
        if (!getRule('required') || input.type === 'hidden') {
            return true;
        }

        // Required tables need rows, not a value in every cell. Cell values are
        // optional, including unchecked checkboxes and blank email addresses.
        if (input.closest('[data-formie-field-handle]')?.getAttribute('data-formie-field-type') === 'table') {
            return true;
        }

        if (input instanceof HTMLInputElement && input.type === 'file') {
            if (input.files?.length) {
                return true;
            }

            const field = input.closest('[data-formie-field-handle]');
            const name = input.name.replace(/\[\]$/, '');

            return Array.from(field?.querySelectorAll<HTMLInputElement>('input[type="hidden"][data-formie-file-upload-asset-id]') || [])
                .some((asset) => !asset.disabled && asset.name.startsWith(`${name}[`) && Number(asset.getAttribute('data-formie-file-upload-asset-id')) > 0);
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

        return validateBrowserValue(input.value, { type: 'required' }) === null;
    },
    message: ({ input, label, t }) => {
        return input.getAttribute('data-formie-required-message')
            ?? input.getAttribute('data-required-message')
            ?? t('{label} cannot be blank.', { label });
    },
};

export default required;
