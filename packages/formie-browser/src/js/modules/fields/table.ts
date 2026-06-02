import tableCss from '#theme/fields/_table.css?inline';

import type { FormieModuleDefinition } from '#contracts/modules';
import { dispatchFieldEvent, getTemplateSource, getTemplateSourceHtml } from '#modules/fields/shared';
import { ensureModuleStyles } from '#modules/styles';
import { sleep } from '#utils/async';

const FIELD_SELECTOR = '[data-formie-table-field-layout]';
const TABLE_SELECTOR = '[data-formie-table]';
const TABLE_BODY_SELECTOR = '[data-formie-table-body]';
const ROW_SELECTOR = '[data-formie-table-row]';
const ADD_SELECTOR = '[data-formie-table-add]';
const REMOVE_SELECTOR = '[data-formie-table-remove]';
const TEMPLATE_ID_ATTR = 'data-formie-template-id';
const ROW_ID_ATTR = 'data-formie-table-row-id';
const MODULE_ID = 'table';

ensureModuleStyles(MODULE_ID, [tableCss]);

type TableOptions = {
    static?: boolean;
};

function getTemplate(field: HTMLElement, templateId?: string | null): HTMLElement | HTMLTemplateElement | HTMLScriptElement | null {
    return getTemplateSource(field, templateId);
}

function getRowCount(field: HTMLElement): number {
    return field.querySelectorAll(ROW_SELECTOR).length;
}

function getNextRowId(field: HTMLElement): number {
    return Array.from(field.querySelectorAll(ROW_SELECTOR)).reduce((max, row) => {
        const current = parseInt((row as HTMLElement).getAttribute(ROW_ID_ATTR) || '', 10);
        return Number.isNaN(current) ? max : Math.max(max, current + 1);
    }, 0);
}

function syncAddButton(addButton: HTMLButtonElement | null, rowCount: number): void {
    if (!addButton) {
        return;
    }

    const maxRows = parseInt(addButton.getAttribute('data-formie-max-rows') || '', 10);
    addButton.disabled = maxRows > 0 && rowCount >= maxRows;
}

function bindTableField(field: HTMLElement, options: TableOptions): () => void {
    const table = field.querySelector(TABLE_SELECTOR);
    const tbody = field.querySelector(TABLE_BODY_SELECTOR);
    const addButton = field.querySelector(ADD_SELECTOR);

    if (!(table instanceof HTMLElement) || !(tbody instanceof HTMLElement)) {
        return () => {};
    }

    const removeHandlers = new Map<HTMLElement, EventListener>();
    // Tables use a monotonically increasing row token so newly-added rows keep a
    // stable name/index even if earlier rows were removed.
    let rowCounter = getNextRowId(field);

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
                dispatchFieldEvent(field, MODULE_ID, 'remove', {
                    table: field,
                    row,
                });
            };

            button.addEventListener('click', handler);
            removeHandlers.set(button, handler);
        });
    };

    const addRow = async() => {
        if (options.static || !(addButton instanceof HTMLButtonElement)) {
            return;
        }

        const handle = addButton.getAttribute('data-formie-table-add');
        if (!handle) {
            return;
        }

        const templateId = addButton.getAttribute(TEMPLATE_ID_ATTR) || field.getAttribute(TEMPLATE_ID_ATTR);

        const maxRows = parseInt(addButton.getAttribute('data-formie-max-rows') || '', 10);
        if (maxRows > 0 && getRowCount(field) >= maxRows) {
            return;
        }

        const template = getTemplate(field, templateId);
        if (!template) {
            return;
        }

        const html = getTemplateSourceHtml(template).replaceAll('__ROW__', String(rowCounter++));
        const row = document.createElement('tr');
        row.setAttribute('data-formie-table-row', 'true');
        row.setAttribute(ROW_ID_ATTR, String(rowCounter - 1));
        row.innerHTML = html;
        tbody.appendChild(row);

        // Delay follow-up wiring long enough for nested controls/modules inside the
        // new row to exist before downstream listeners react to append events.
        await sleep(50);
        bindRemoveButtons();
        syncAddButton(addButton, getRowCount(field));
        dispatchFieldEvent(field, MODULE_ID, 'append', {
            table: field,
            row,
        });
    };

    const addHandler = (event: Event) => {
        event.preventDefault();
        void addRow();
    };

    if (addButton instanceof HTMLButtonElement && !options.static) {
        addButton.addEventListener('click', addHandler);
    }

    bindRemoveButtons();
    syncAddButton(addButton instanceof HTMLButtonElement ? addButton : null, getRowCount(field));
    dispatchFieldEvent(field, MODULE_ID, 'init', {
        table: field,
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

export const tableModule: FormieModuleDefinition = {
    id: MODULE_ID,
    kind: 'field',
    match: (ctx) => {
        return ctx.target instanceof HTMLElement && (
            ctx.target.matches(FIELD_SELECTOR) ||
            !!ctx.target.querySelector(FIELD_SELECTOR)
        );
    },
    setup: async(ctx) => {
        const options = (ctx.options || {}) as TableOptions;
        if (!(ctx.target instanceof HTMLElement)) {
            return;
        }

        const fields = ctx.target.matches(FIELD_SELECTOR)
            ? [ctx.target]
            : Array.from(ctx.target.querySelectorAll(FIELD_SELECTOR)).filter((field): field is HTMLElement => {
                return field instanceof HTMLElement;
            });

        const destroyBindings = fields.map((field) => {
            return bindTableField(field, options);
        });

        await ctx.emit('formie:module:table:init', {
            count: fields.length,
        });

        return {
            destroy: () => {
                destroyBindings.forEach((destroyBinding) => {
                    destroyBinding();
                });
                void ctx.emit('formie:module:table:destroy', {});
            },
        };
    },
};
