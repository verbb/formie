import type { FormieModuleDefinition } from '#contracts/modules';
import { dispatchFieldEvent, escapeSelectorValue, releaseFormValidators, retainFormValidators } from '#modules/fields/shared';
import { createDebug } from '#utils/debug';

const FIELD_SELECTOR = '[data-formie-checkboxes-field-layout], [data-formie-radio-field-layout]';
const CHECKBOX_MINMAX_VALIDATOR = 'minmaxOptions';
const MAX_DISABLED_ATTR = 'data-formie-checkbox-radio-max-disabled';
const MODULE_ID = 'checkbox-radio';
const VALIDATOR_SCOPE = 'checkbox-radio';
const debug = createDebug('fields', 'checkbox-radio');

function isToggleCheckbox(input: HTMLInputElement): boolean {
    return input.hasAttribute('data-checkbox-toggle') || input.hasAttribute('data-formie-checkbox-toggle');
}

function getMinMaxRule(getRule: (rule: string) => unknown): { min: number | null; max: number | null } {
    const rule = getRule(CHECKBOX_MINMAX_VALIDATOR);

    if (!rule || rule === true || typeof rule !== 'object') {
        return {
            min: null,
            max: null,
        };
    }

    const candidate = rule as Record<string, unknown>;

    return {
        min: typeof candidate.min === 'number' ? candidate.min : null,
        max: typeof candidate.max === 'number' ? candidate.max : null,
    };
}

function registerValidators(form: HTMLFormElement | null): void {
    // Several checkbox/radio module instances can exist in one form, but the
    // custom validator should only be registered once per form lifecycle.
    retainFormValidators(form, VALIDATOR_SCOPE, (validator) => {
        validator.addValidator(
            CHECKBOX_MINMAX_VALIDATOR,
        ({ field, getRule }) => {
            if (!field || !getRule(CHECKBOX_MINMAX_VALIDATOR)) {
                return true;
            }

            const inputs = Array.from(field.querySelectorAll('input[type="checkbox"]')).filter((input): input is HTMLInputElement => {
                return input instanceof HTMLInputElement && !isToggleCheckbox(input);
            });
            const selected = inputs.filter((input) => {
                return input.checked;
            }).length;
            const { min, max } = getMinMaxRule(getRule);

            if (min !== null && selected < min) {
                return false;
            }

            if (max !== null && selected > max) {
                return false;
            }

            return true;
        },
        ({ field, label, t, getRule }) => {
            const { min, max } = field ? getMinMaxRule(getRule) : { min: null, max: null };

            if (min !== null && max !== null) {
                return t('{attribute} must select between {min} and {max}.', { attribute: label, min, max });
            }

            if (min !== null) {
                return t('{attribute} must select no less than {min}.', { attribute: label, min });
            }

            if (max !== null) {
                return t('{attribute} must select no greater than {max}.', { attribute: label, max });
            }

            return t('{attribute} has an invalid value.', { attribute: label });
        },
    );
    });
}

function unregisterValidators(form: HTMLFormElement | null): void {
    releaseFormValidators(form, VALIDATOR_SCOPE, [CHECKBOX_MINMAX_VALIDATOR]);
}

function syncCheckedAttribute(input: HTMLInputElement): void {
    if (input.checked) {
        input.setAttribute('checked', '');
    } else {
        input.removeAttribute('checked');
    }
}

function syncRequiredCheckboxes(field: HTMLElement): void {
    const requiredCheckboxes = Array.from(field.querySelectorAll('input[type="checkbox"][required][data-formie-checkbox-input]')).filter((input): input is HTMLInputElement => {
        return input instanceof HTMLInputElement;
    });

    if (!requiredCheckboxes.length) {
        return;
    }

    const hasCheckedValue = requiredCheckboxes.some((input) => {
        return input.checked;
    });

    // Native required checkbox behavior is per-input, while Formie treats the
    // field as one logical group. Toggle `required` to preserve that contract.
    requiredCheckboxes.forEach((input) => {
        if (hasCheckedValue) {
            input.removeAttribute('required');
            input.setAttribute('aria-required', 'false');
            return;
        }

        input.setAttribute('required', 'true');
        input.setAttribute('aria-required', 'true');
    });
}

function enforceMaxOptions(field: HTMLElement): void {
    const maxOptions = parseInt(field.closest('[data-formie-field-handle]')?.getAttribute('data-formie-max-options') || '', 10);

    if (!(maxOptions > 0)) {
        return;
    }

    const checkboxes = Array.from(field.querySelectorAll('input[type="checkbox"]')).filter((input): input is HTMLInputElement => {
        return input instanceof HTMLInputElement && !isToggleCheckbox(input);
    });

    const checked = checkboxes.filter((input) => {
        return input.checked;
    });
    const disableUnchecked = checked.length >= maxOptions;

    // Disable only the unchecked options once the max is reached so the user can
    // still back out of a choice without getting trapped in a disabled state.
    checkboxes.forEach((input) => {
        const shouldDisableForMax = disableUnchecked && !input.checked;
        const wasDisabledForMax = input.hasAttribute(MAX_DISABLED_ATTR);

        if (shouldDisableForMax) {
            if (!input.disabled) {
                input.disabled = true;
                input.setAttribute(MAX_DISABLED_ATTR, 'true');
            }

            return;
        }

        if (wasDisabledForMax) {
            input.disabled = false;
            input.removeAttribute(MAX_DISABLED_ATTR);
        }
    });
}

function toggleCheckboxGroup(field: HTMLElement, toggle: HTMLInputElement): void {
    const checkboxes = Array.from(field.querySelectorAll('input[type="checkbox"]')).filter((input): input is HTMLInputElement => {
        return input instanceof HTMLInputElement && input !== toggle && !isToggleCheckbox(input);
    });

    checkboxes.forEach((input) => {
        if (input.disabled && !input.checked) {
            return;
        }

        // Re-emit change/input so downstream modules (conditions, calculations,
        // validation) react exactly as if the user toggled each box manually.
        input.checked = toggle.checked;
        syncCheckedAttribute(input);
        input.dispatchEvent(new Event('change', { bubbles: true }));
        input.dispatchEvent(new Event('input', { bubbles: true }));
    });
}

function syncRadioGroup(input: HTMLInputElement, field: HTMLElement): void {
    if (!input.checked || !input.name) {
        syncCheckedAttribute(input);
        return;
    }

    const group = Array.from(field.querySelectorAll(`input[type="radio"][name="${escapeSelectorValue(input.name)}"]`)).filter((radio): radio is HTMLInputElement => {
        return radio instanceof HTMLInputElement;
    });

    group.forEach((radio) => {
        syncCheckedAttribute(radio);
    });
}

function bindField(field: HTMLElement): () => void {
    const inputs = Array.from(field.querySelectorAll('input[type="checkbox"], input[type="radio"]')).filter((input): input is HTMLInputElement => {
        return input instanceof HTMLInputElement;
    });

    if (!inputs.length) {
        debug.log('No checkbox/radio inputs found for field.');
        return () => {};
    }

    const listeners = inputs.map((input) => {
        const eventName = input.type === 'radio' ? 'change' : 'click';
        const handler = () => {
            syncCheckedAttribute(input);

            if (input.type === 'checkbox' && isToggleCheckbox(input)) {
                toggleCheckboxGroup(field, input);
            }

            if (input.type === 'radio') {
                syncRadioGroup(input, field);
            }

            syncRequiredCheckboxes(field);
            enforceMaxOptions(field);
            debug.log('Input interaction processed.', {
                inputName: input.name,
                inputType: input.type,
                checked: input.checked,
            });
        };

        input.addEventListener(eventName, handler);
        syncCheckedAttribute(input);

        return () => {
            input.removeEventListener(eventName, handler);
        };
    });

    syncRequiredCheckboxes(field);
    enforceMaxOptions(field);
    dispatchFieldEvent(field, MODULE_ID, 'init', {
        checkboxRadio: field,
    });

    return () => {
        listeners.forEach((unbind) => {
            unbind();
        });
    };
}

export const checkboxRadioModule: FormieModuleDefinition = {
    id: MODULE_ID,
    kind: 'field',
    match: (ctx) => {
        return ctx.target instanceof HTMLElement && (
            ctx.target.matches(FIELD_SELECTOR) ||
            !!ctx.target.querySelector(FIELD_SELECTOR)
        );
    },
    setup: async(ctx) => {
        if (!(ctx.target instanceof HTMLElement)) {
            return;
        }

        const fields = ctx.target.matches(FIELD_SELECTOR)
            ? [ctx.target]
            : Array.from(ctx.target.querySelectorAll(FIELD_SELECTOR)).filter((field): field is HTMLElement => {
                return field instanceof HTMLElement;
            });

        registerValidators(ctx.form);
        debug.log('Module setup.', {
            fieldCount: fields.length,
        });

        const destroyBindings = fields.map((field) => {
            return bindField(field);
        });

        await ctx.emit('formie:module:checkbox-radio:init', {
            count: fields.length,
        });

        return {
            destroy: () => {
                destroyBindings.forEach((destroyBinding) => {
                    destroyBinding();
                });

                unregisterValidators(ctx.form);
                debug.log('Module destroy.', {
                    fieldCount: fields.length,
                });
                void ctx.emit('formie:module:checkbox-radio:destroy', {});
            },
        };
    },
};
