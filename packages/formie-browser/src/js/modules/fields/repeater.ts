import repeaterCss from '#theme-css/fields/_repeater.css?inline';

import type { FormieModuleDefinition } from '#contracts/modules';
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

function bindRepeaterField(field: HTMLElement): () => void {
    const container = field.matches(CONTAINER_SELECTOR)
        ? field
        : field.querySelector(CONTAINER_SELECTOR);
    const addButton = field.querySelector(ADD_SELECTOR);

    if (!(container instanceof HTMLElement)) {
        debug.warn('Missing repeater container; skipping field.');
        return () => {};
    }

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

                row.remove();
                syncAddButton(addButton instanceof HTMLButtonElement ? addButton : null, getRowCount(field));
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

    const addRow = async() => {
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

        container.appendChild(row);
        // Give downstream field modules a tick to see the new row before we emit
        // the public append/init-row lifecycle events used for nested enhancements.
        await sleep(50);
        bindRemoveButtons();
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
        void addRow();
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

    dispatchFieldEvent(field, MODULE_ID, 'init', {
        repeater: field,
    });
    debug.log('Field initialized.', {
        rowCount: getRowCount(field),
    });

    return () => {
        if (addButton instanceof HTMLButtonElement) {
            addButton.removeEventListener('click', addHandler);
        }

        removeHandlers.forEach((handler, button) => {
            button.removeEventListener('click', handler);
        });
    };
}

export const repeaterModule: FormieModuleDefinition = {
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

        const destroyBindings = fields.map((field) => {
            return bindRepeaterField(field);
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
