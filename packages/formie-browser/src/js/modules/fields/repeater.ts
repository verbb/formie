import repeaterCss from '#theme-css/fields/_repeater.css?inline';

import type { BrowserModuleDefinition } from '#contracts/modules';
import { dispatchFieldEvent, getTemplateSource, getTemplateSourceHtml } from '#modules/fields/shared';
import { ensureModuleStyles } from '#modules/styles';
import { sleep } from '#utils/async';
import { createDebug } from '#utils/debug';

const FIELD_SELECTOR = '[data-formie-repeater-field-layout]';
const CONTAINER_SELECTOR = '[data-formie-repeater-container]';
const ROW_SELECTOR = '[data-formie-repeater-item]';
const ADD_SELECTOR = '[data-formie-repeater-add]';
const REMOVE_SELECTOR = '[data-formie-repeater-remove]';
const TEMPLATE_ID_ATTR = 'data-formie-template-id';
const MODULE_ID = 'repeater';
const debug = createDebug('fields', 'repeater');

ensureModuleStyles(MODULE_ID, [repeaterCss]);

function getTemplate(field: HTMLElement, templateId?: string | null): HTMLElement | HTMLTemplateElement | HTMLScriptElement | null {
    return getTemplateSource(field, templateId);
}

function buildRowFromTemplate(templateHtml: string, rowId: number): HTMLElement | null {
    const wrapper = document.createElement('div');
    wrapper.innerHTML = templateHtml.replaceAll('__ROW__', String(rowId)).trim();

    return wrapper.firstElementChild instanceof HTMLElement ? wrapper.firstElementChild : null;
}

function getRowCount(field: HTMLElement): number {
    return field.querySelectorAll(ROW_SELECTOR).length;
}

function focusRow(row: Element | null): boolean {
    const control = Array.from(row?.querySelectorAll<HTMLElement>(
        'input:not([type="hidden"]), select, textarea, button, a[href], [role="button"], [role="combobox"], [role="textbox"], [contenteditable="true"]',
    ) || []).find((element) => {
        return element.tabIndex >= 0 && !element.matches(':disabled')
            && !element.closest('[hidden], [inert]') && element.getClientRects().length > 0;
    });

    control?.focus();
    return !!control;
}

function syncAddButton(addButton: HTMLButtonElement | null, rowCount: number): void {
    if (!addButton) {
        return;
    }

    const maxRows = parseInt(addButton.getAttribute('data-formie-max-rows') || '', 10);
    if (maxRows > 0 && rowCount >= maxRows) {
        addButton.disabled = true;
        return;
    }

    addButton.disabled = false;
}

function bindRepeaterField(field: HTMLElement, isCpEdit: boolean): () => void {
    const container = field.matches(CONTAINER_SELECTOR)
        ? field
        : field.querySelector(CONTAINER_SELECTOR);
    const addButton = field.querySelector(ADD_SELECTOR);

    if (!(container instanceof HTMLElement)) {
        debug.warn('Missing repeater container; skipping field.');
        return () => {};
    }

    const form = field.closest('form');
    let disposed = false;
    let generation = 0;
    const removeHandlers = new Map<HTMLElement, EventListener>();
    // Start after the highest existing row id so SSR rows and client-added rows
    // keep producing unique `__ROW__` replacements across one repeater instance.
    let rowCounter = Array.from(field.querySelectorAll(ROW_SELECTOR)).reduce((max, row) => {
        const current = parseInt((row as HTMLElement).getAttribute('data-formie-repeater-item-id') || '', 10);
        return Number.isNaN(current) ? max : Math.max(max, current + 1);
    }, 0);

    const bindRemoveButtons = () => {
        field.querySelectorAll(REMOVE_SELECTOR).forEach((button) => {
            if (!(button instanceof HTMLElement) || removeHandlers.has(button)) {
                return;
            }

            const handler: EventListener = (event) => {
                event.preventDefault();
                const row = button.closest(ROW_SELECTOR);

                if (!(row instanceof HTMLElement)) {
                    return;
                }

                const minRows = parseInt((addButton instanceof HTMLButtonElement ? addButton.getAttribute('data-formie-min-rows') : '') || '', 10);
                if (minRows > 0 && getRowCount(field) <= minRows) {
                    return;
                }

                const restoreFocus = row.contains(document.activeElement)
                    && (!isCpEdit || (event instanceof MouseEvent && event.detail === 0));
                const nextRow = row.nextElementSibling;
                const previousRow = row.previousElementSibling;
                row.remove();
                syncAddButton(addButton instanceof HTMLButtonElement ? addButton : null, getRowCount(field));
                // Removing the focused control otherwise sends keyboard users back
                // to the document body. Keep them in the nearest surviving row.
                if (restoreFocus && !focusRow(nextRow) && !focusRow(previousRow)) {
                    if (addButton instanceof HTMLButtonElement) addButton.focus();
                }
                debug.log('Row removed.', {
                    rowCount: getRowCount(field),
                });
                dispatchFieldEvent(field, MODULE_ID, 'remove', {
                    repeater: field,
                    row,
                });
            };

            button.addEventListener('click', handler);
            removeHandlers.set(button, handler);
        });
    };

    const addRow = async(focusNewRow = false) => {
        if (!(addButton instanceof HTMLButtonElement)) {
            return;
        }

        const handle = addButton.getAttribute('data-formie-repeater-add');
        if (!handle) {
            debug.warn('Add handle missing.');
            return;
        }

        const templateId = addButton.getAttribute(TEMPLATE_ID_ATTR) || field.getAttribute(TEMPLATE_ID_ATTR);

        const maxRows = parseInt(addButton.getAttribute('data-formie-max-rows') || '', 10);
        if (maxRows > 0 && getRowCount(field) >= maxRows) {
            return;
        }

        const template = getTemplate(field, templateId);
        if (!template) {
            debug.warn('Template not found for add action.', { handle });
            return;
        }

        const row = buildRowFromTemplate(getTemplateSourceHtml(template), rowCounter++);
        if (!row) {
            debug.warn('Failed to build row from template.');
            return;
        }

        const addedGeneration = generation;
        container.appendChild(row);
        // Give downstream field modules a tick to see the new row before we emit
        // the public append/init-row lifecycle events used for nested enhancements.
        await sleep(50);
        if (disposed || addedGeneration !== generation || !row.isConnected) return;
        bindRemoveButtons();
        // Rows are inserted before Add, so leaving focus there skips the new
        // inputs on Tab. Do not steal focus if the user moved on while mounting.
        if (focusNewRow && document.activeElement === addButton) focusRow(row);
        syncAddButton(addButton, getRowCount(field));
        debug.log('Row appended.', {
            rowCount: getRowCount(field),
        });
        dispatchFieldEvent(field, MODULE_ID, 'append', {
            repeater: field,
            row,
        });
        dispatchFieldEvent(field, MODULE_ID, 'init-row', {
            repeater: field,
            row,
        });
    };

    const addHandler = (event: Event) => {
        event.preventDefault();
        // Pointer additions in the CP should not focus Craft's field wrappers
        // and show a focus ring around the newly inserted field.
        const focusNewRow = !isCpEdit || (event instanceof MouseEvent && event.detail === 0);
        void addRow(focusNewRow);
    };

    if (addButton instanceof HTMLButtonElement) {
        addButton.addEventListener('click', addHandler);
    }

    bindRemoveButtons();
    syncAddButton(addButton instanceof HTMLButtonElement ? addButton : null, getRowCount(field));

    if (addButton instanceof HTMLButtonElement && getRowCount(field) === 0) {
        const minRows = parseInt(addButton.getAttribute('data-formie-min-rows') || '', 10);
        // Seed required minimum rows through the same add flow so templates,
        // events, and row ids behave exactly like user-triggered additions.
        for (let index = 0; index < minRows; index += 1) {
            void addRow();
        }
    }

    const initialRows = Array.from(container.children).map((row) => row.cloneNode(true));

    const onReset = (event: Event) => {
        queueMicrotask(() => {
            if (event.defaultPrevented || disposed) return;
            generation++;
            removeHandlers.forEach((handler, button) => button.removeEventListener('click', handler));
            removeHandlers.clear();
            // Replace rows so removed initial rows return and added rows disappear.
            // The module loader disposes old controls and hydrates the new rows.
            container.replaceChildren(...initialRows.map((row) => row.cloneNode(true)));
            bindRemoveButtons();
            syncAddButton(addButton instanceof HTMLButtonElement ? addButton : null, getRowCount(field));
        });
    };
    form?.addEventListener('reset', onReset);

    dispatchFieldEvent(field, MODULE_ID, 'init', {
        repeater: field,
    });
    debug.log('Field initialized.', {
        rowCount: getRowCount(field),
    });

    return () => {
        disposed = true;
        form?.removeEventListener('reset', onReset);
        if (addButton instanceof HTMLButtonElement) {
            addButton.removeEventListener('click', addHandler);
        }

        removeHandlers.forEach((handler, button) => {
            button.removeEventListener('click', handler);
        });
    };
}

export const repeaterModule: BrowserModuleDefinition = {
    moduleId: `formie:${MODULE_ID}`,
    version: 2,
    surfaces: ['server-rendered', 'client-rendered', 'cp-edit'],
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

        const destroyBindings = fields.map((field) => {
            return bindRepeaterField(field, ctx.surface === 'cp-edit');
        });
        debug.log('Module setup.', { fieldCount: fields.length });

        await ctx.emit('formie:module:repeater:init', {
            count: fields.length,
        });

        return {
            destroy: () => {
                destroyBindings.forEach((destroyBinding) => {
                    destroyBinding();
                });

                debug.log('Module destroy.', { fieldCount: fields.length });
                void ctx.emit('formie:module:repeater:destroy', {});
            },
        };
    },
};
