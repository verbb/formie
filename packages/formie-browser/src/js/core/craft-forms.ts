import { getErrorAriaLivePreference } from '#core/error-aria-live';
import { focusFirstValidationError } from '#core/validation-focus';
import { registerThemeClassMap } from '#theme/theme-classes';
import { FormieValidator } from '#validation/validator';
import type { BrowserThemeClassMap } from '#contracts/theme';

const SELECTOR = 'form[data-formie-craft-form]:not([data-formie-init="false"])';

type CraftFormBinding = {
    validator: FormieValidator;
    owners: Set<symbol>;
    destroy: () => void;
};

const bindings = new WeakMap<HTMLFormElement, CraftFormBinding>();

export type CraftFormsApp = {
    get: (form: HTMLFormElement) => FormieValidator | null;
    rescan: () => void;
    destroy: () => void;
};

function bindCraftForm(form: HTMLFormElement): CraftFormBinding {
    const existing = bindings.get(form);
    if (existing) return existing;

    // Parse before changing native validation so malformed configuration keeps
    // the ordinary Craft form usable. No submission modules or tokens are loaded.
    const theme = JSON.parse(form.dataset.formieThemeClasses || '{}') as BrowserThemeClassMap;
    const classes = registerThemeClassMap(form, theme);
    const originalNoValidate = form.getAttribute('novalidate');
    const validator = new FormieValidator(form, {
        live: form.dataset.formieValidationOnFocus === 'true',
        errorAriaLive: getErrorAriaLivePreference(form),
        fieldContainerErrorClass: classes.fieldLayoutError || [],
        inputErrorClass: classes.fieldControlError || [],
        messagesClass: classes.fieldErrors || [],
        messageClass: classes.fieldError || [],
    });

    const submit = (event: SubmitEvent): void => {
        const submitter = event.submitter;
        if (event.defaultPrevented || originalNoValidate !== null
            || form.dataset.formieValidationOnSubmit === 'false'
            || submitter?.hasAttribute('formnovalidate')) return;

        if (validator.submit().length) {
            event.preventDefault();
            focusFirstValidationError(form);
        }
        // A valid form keeps the original submit event and submitter, including
        // its name/value and overrides. Craft handles the response and redirects.
    };
    const reset = (event: Event): void => {
        queueMicrotask(() => {
            if (!event.defaultPrevented && bindings.has(form)) validator.resetLiveState();
        });
    };
    form.addEventListener('submit', submit);
    form.addEventListener('reset', reset);

    const binding: CraftFormBinding = {
        validator,
        owners: new Set(),
        destroy: () => {
            form.removeEventListener('submit', submit);
            form.removeEventListener('reset', reset);
            validator.removeEventListeners();
            validator.resetLiveState();
            if (originalNoValidate === null) form.removeAttribute('novalidate');
            else form.setAttribute('novalidate', originalNoValidate);
            bindings.delete(form);
        },
    };
    bindings.set(form, binding);
    form.dispatchEvent(new CustomEvent('formie:validator:ready', {
        bubbles: true,
        detail: {
            validator,
            addValidator: validator.addValidator.bind(validator),
            removeValidator: validator.removeValidator.bind(validator),
        },
    }));
    return binding;
}

/** Enhance Craft action forms without taking ownership of their requests. */
export function craftForms({ root = document, observe = false }: {
    root?: ParentNode;
    observe?: boolean;
} = {}): CraftFormsApp {
    const owner = Symbol('craftForms');
    const mounted = new Map<HTMLFormElement, CraftFormBinding>();
    let destroyed = false;

    const release = (form: HTMLFormElement, binding: CraftFormBinding): void => {
        binding.owners.delete(owner);
        if (!binding.owners.size) binding.destroy();
        mounted.delete(form);
    };
    const rescan = (): void => {
        if (destroyed) return;
        const forms = new Set(root.querySelectorAll<HTMLFormElement>(SELECTOR));
        if (root instanceof HTMLFormElement && root.matches(SELECTOR)) forms.add(root);

        for (const [form, binding] of mounted) {
            if (!forms.has(form)) release(form, binding);
        }
        for (const form of forms) {
            if (mounted.has(form)) continue;
            try {
                const binding = bindCraftForm(form);
                binding.owners.add(owner);
                mounted.set(form, binding);
            } catch (error) {
                // One malformed form must not prevent other forms from mounting.
                console.warn('Formie could not initialize Craft form validation.', error);
            }
        }
    };
    rescan();
    const observer = observe ? new MutationObserver(rescan) : null;
    observer?.observe(root, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['data-formie-craft-form', 'data-formie-init'],
    });

    return {
        get: (form) => mounted.get(form)?.validator || null,
        rescan,
        destroy: () => {
            destroyed = true;
            observer?.disconnect();
            for (const [form, binding] of mounted) release(form, binding);
        },
    };
}
