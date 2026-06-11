import TomSelect from 'tom-select/dist/esm/tom-select.complete.js';

import type { FormieModuleDefinition } from '#contracts/modules';
import comboboxThemeCss from '#theme-css/fields/_combobox.css?inline';
import { dispatchFieldEvent, getModuleFieldContainers } from '#modules/fields/shared';
import { ensureModuleStyles } from '#modules/styles';
import { createDebug } from '#utils/debug';

const SELECT_SELECTOR = 'select[data-formie-combobox-input]';
const MODULE_ID = 'combobox';
const debug = createDebug('fields', 'combobox');

ensureModuleStyles(MODULE_ID, [comboboxThemeCss]);

const NATIVE_THEME_CLASSES = [
    'formie-select',
    'formie-dropdown-input',
    'formie-input-error',
] as const;

export type FormieComboboxOptions = {
    multiple?: boolean;
    placeholder?: string | null;
};

type TomSelectInstance = InstanceType<typeof TomSelect> & {
    wrapper: HTMLElement;
    dropdown?: HTMLElement;
};

type SelectElement = HTMLSelectElement & {
    _formieTomSelect?: TomSelectInstance;
};

function stripNativeThemeClasses(select: HTMLSelectElement): string[] {
    const removed: string[] = [];

    NATIVE_THEME_CLASSES.forEach((className) => {
        if (select.classList.contains(className)) {
            select.classList.remove(className);
            removed.push(className);
        }
    });

    return removed;
}

function restoreNativeThemeClasses(select: HTMLSelectElement, classNames: string[]): void {
    classNames.forEach((className) => {
        select.classList.add(className);
    });
}

function cleanLeakedThemeClasses(element: HTMLElement): void {
    NATIVE_THEME_CLASSES.forEach((className) => {
        element.classList.remove(className);
    });
}

function resolvePlaceholder(select: HTMLSelectElement, configuredPlaceholder?: string | null): string | null {
    const configured = configuredPlaceholder?.trim();

    if (configured) {
        return configured;
    }

    const emptyOption = select.querySelector('option[value=""]');

    return emptyOption?.textContent?.trim() || null;
}

function removeEmptyOptionFromCombobox(instance: TomSelectInstance): void {
    if (instance.options['']) {
        instance.removeOption('', true);
    }
}

export function initFormieCombobox(select: SelectElement, options: FormieComboboxOptions = {}): () => void {
    select._formieTomSelect?.destroy();

    const multiple = options.multiple === true;
    const restoredClasses = stripNativeThemeClasses(select);
    const placeholder = resolvePlaceholder(select, options.placeholder);

    const mergedOptions: Record<string, unknown> = {
        create: false,
        maxItems: multiple ? null : 1,
        plugins: multiple ? ['remove_button'] : [],
        hideSelected: multiple ? true : null,
        clearAfterSelect: multiple,
        closeAfterSelect: !multiple,
        allowEmptyOption: !multiple,
        openOnFocus: true,
        diacritics: true,
        // Tom Select copies the native select class attribute onto its wrapper,
        // which would duplicate Formie select chrome if left on the <select>.
        copyClassesToDropdown: false,
        wrapperClass: 'ts-wrapper formie-combobox',
        onChange: () => {
            // Mirror Tom Select changes back through the native select so validation,
            // conditions, and calculations respond to combobox-driven updates.
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
        },
    };

    if (placeholder) {
        mergedOptions.placeholder = placeholder;
    }

    dispatchFieldEvent(select, MODULE_ID, 'before-init', {
        select,
        options: mergedOptions,
    });

    const instance = new TomSelect(select, mergedOptions) as TomSelectInstance;
    removeEmptyOptionFromCombobox(instance);
    cleanLeakedThemeClasses(instance.wrapper);

    if (instance.dropdown) {
        cleanLeakedThemeClasses(instance.dropdown);
    }

    // Tom Select keeps the native select as a preceding sibling; ensure it never
    // remains visible once enhanced (notably for multi-select listboxes).
    select.style.display = 'none';

    select._formieTomSelect = instance;

    debug.log('Initialized.', {
        inputName: select.name,
        multiple,
    });

    dispatchFieldEvent(select, MODULE_ID, 'after-init', {
        combobox: instance,
        options: mergedOptions,
    });

    return () => {
        instance.destroy();
        select.style.removeProperty('display');
        restoreNativeThemeClasses(select, restoredClasses);
        delete select._formieTomSelect;
        debug.log('Destroyed.', {
            inputName: select.name,
        });
    };
}

export const comboboxModule: FormieModuleDefinition = {
    id: MODULE_ID,
    kind: 'field',
    match: (ctx) => {
        return !!ctx.target.querySelector(SELECT_SELECTOR);
    },
    setup: async (ctx) => {
        const options = (ctx.options || {}) as FormieComboboxOptions;
        const fields = getModuleFieldContainers(ctx);
        const cleanups = fields.map((field) => {
            const select = field.querySelector(SELECT_SELECTOR);

            if (!(select instanceof HTMLSelectElement)) {
                debug.warn('Field missing combobox select; skipping.');
                return () => { };
            }

            return initFormieCombobox(select as SelectElement, options);
        });

        debug.log('Module setup.', { fieldCount: fields.length });

        await ctx.emit('formie:module:combobox:init', {
            count: cleanups.length,
        });

        return {
            destroy: () => {
                cleanups.forEach((cleanup) => {
                    cleanup();
                });

                debug.log('Module destroy.', { fieldCount: fields.length });
                void ctx.emit('formie:module:combobox:destroy', {});
            },
        };
    },
};
