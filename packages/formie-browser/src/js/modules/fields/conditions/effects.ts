const CONDITION_DISABLED_ATTR = 'data-formie-conditions-disabled';
const PRESERVED_DISABLED_ATTR = 'data-formie-preserve-disabled';
const CONDITIONAL_HIDDEN_ATTR = 'data-formie-conditionally-hidden';
const PAGE_HIDDEN_ATTR = 'data-formie-page-hidden';
const CONDITIONAL_HIDDEN_CLASS = 'formie-conditionally-hidden';
const CP_MUTED_CLASS = 'fui-cp-muted-conditional-field';
const CP_MUTED_EXPANDED_CLASS = 'fui-cp-muted-conditional-field--expanded';
const CP_MUTED_ATTR = 'data-formie-cp-muted';
const PAGE_HIDDEN_CLASS = 'formie-page-hidden';
const ROW_HIDDEN_ATTR = 'data-formie-row-hidden';
const ROW_HIDDEN_CLASS = 'formie-row-hidden';
const FIELD_COUNT_ATTR = 'data-formie-field-count';
const ROW_SELECTOR = '[data-formie-row], [data-formie-subfield-row], [data-formie-nested-field-row]';
const FIELD_SELECTOR = ':scope > [data-formie-field]';

function clearConditionNodeValues(node: Element): void {
    node.querySelectorAll('input, select, textarea').forEach((element) => {
        if (!(element instanceof HTMLInputElement) &&
            !(element instanceof HTMLSelectElement) &&
            !(element instanceof HTMLTextAreaElement)) {
            return;
        }

        if (element instanceof HTMLInputElement) {
            if (element.type === 'checkbox' || element.type === 'radio') {
                element.checked = false;
            } else if (element.type !== 'hidden') {
                element.value = '';
            }
        }

        if (element instanceof HTMLSelectElement) {
            if (element.multiple) {
                Array.from(element.options).forEach((option) => {
                    option.selected = false;
                });
            } else {
                element.selectedIndex = 0;
            }
        }

        if (element instanceof HTMLTextAreaElement) {
            element.value = '';
        }
    });
}

function setVisibilityState(node: Element, hidden: boolean): boolean {
    const isPage = node.hasAttribute('data-formie-page');
    const hiddenAttr = isPage ? PAGE_HIDDEN_ATTR : CONDITIONAL_HIDDEN_ATTR;
    const hiddenClass = isPage ? PAGE_HIDDEN_CLASS : CONDITIONAL_HIDDEN_CLASS;
    const wasHidden = node.hasAttribute(hiddenAttr);

    if (hidden) {
        if (!wasHidden) {
            node.setAttribute(hiddenAttr, 'true');
        }

        if (!node.classList.contains(hiddenClass)) {
            node.classList.add(hiddenClass);
        }
    } else {
        if (wasHidden) {
            node.removeAttribute(hiddenAttr);
        }

        if (node.classList.contains(hiddenClass)) {
            node.classList.remove(hiddenClass);
        }
    }

    return wasHidden !== hidden;
}

function syncDisabledState(node: Element, hidden: boolean): void {
    node.querySelectorAll('input, textarea, select').forEach((input) => {
        if (hidden) {
            if (!input.hasAttribute(CONDITION_DISABLED_ATTR)) {
                if (input.hasAttribute('disabled')) {
                    input.setAttribute(PRESERVED_DISABLED_ATTR, 'true');
                }

                input.setAttribute(CONDITION_DISABLED_ATTR, 'true');
            }

            input.setAttribute('disabled', 'true');
            return;
        }

        if (!input.hasAttribute(CONDITION_DISABLED_ATTR)) {
            return;
        }

        if (input.hasAttribute(PRESERVED_DISABLED_ATTR)) {
            input.setAttribute('disabled', 'true');
            input.removeAttribute(PRESERVED_DISABLED_ATTR);
        } else {
            input.removeAttribute('disabled');
        }

        input.removeAttribute(CONDITION_DISABLED_ATTR);
    });
}

function isFieldVisible(field: Element): boolean {
    return !field.hasAttribute(CONDITIONAL_HIDDEN_ATTR)
        && !field.hasAttribute(PAGE_HIDDEN_ATTR)
        && !field.hasAttribute(ROW_HIDDEN_ATTR)
        && !field.hasAttribute('hidden');
}

function syncRowState(row: Element): void {
    const directFields = Array.from(row.querySelectorAll(FIELD_SELECTOR));
    const visibleFieldCount = directFields.filter((field) => {
        return isFieldVisible(field);
    }).length;

    if (visibleFieldCount > 0) {
        const visibleCount = String(visibleFieldCount);

        if (row.getAttribute(FIELD_COUNT_ATTR) !== visibleCount) {
            row.setAttribute(FIELD_COUNT_ATTR, visibleCount);
        }

        if (row.hasAttribute(ROW_HIDDEN_ATTR)) {
            row.removeAttribute(ROW_HIDDEN_ATTR);
        }

        if (row.classList.contains(ROW_HIDDEN_CLASS)) {
            row.classList.remove(ROW_HIDDEN_CLASS);
        }

        return;
    }

    if (row.hasAttribute(FIELD_COUNT_ATTR)) {
        row.removeAttribute(FIELD_COUNT_ATTR);
    }

    if (!row.hasAttribute(ROW_HIDDEN_ATTR)) {
        row.setAttribute(ROW_HIDDEN_ATTR, 'true');
    }

    if (!row.classList.contains(ROW_HIDDEN_CLASS)) {
        row.classList.add(ROW_HIDDEN_CLASS);
    }
}

function clearCpMutedState(node: Element): void {
    node.removeAttribute(CP_MUTED_ATTR);
    node.classList.remove(CP_MUTED_CLASS);
    node.classList.remove(CP_MUTED_EXPANDED_CLASS);
}

function clearHideState(node: Element): void {
    node.removeAttribute(CONDITIONAL_HIDDEN_ATTR);
    node.removeAttribute(PAGE_HIDDEN_ATTR);
    node.classList.remove(CONDITIONAL_HIDDEN_CLASS);
    node.classList.remove(PAGE_HIDDEN_CLASS);
}

function syncAncestorRows(node: Element): void {
    let currentRow = node.closest(ROW_SELECTOR);

    while (currentRow) {
        syncRowState(currentRow);
        currentRow = currentRow.parentElement?.closest(ROW_SELECTOR) || null;
    }
}

export function applyConditionVisibility(
    node: Element,
    hidden: boolean,
    clearOnHide: boolean,
    options: { displayMode?: 'hide' | 'muted' } = {},
): boolean {
    if (options.displayMode === 'muted') {
        return applyMutedConditionVisibility(node, hidden);
    }

    let stateChanged = false;

    if (node.hasAttribute(CP_MUTED_ATTR)
        || node.classList.contains(CP_MUTED_CLASS)
        || node.classList.contains(CP_MUTED_EXPANDED_CLASS)) {
        clearCpMutedState(node);
        stateChanged = true;
    }

    stateChanged = setVisibilityState(node, hidden) || stateChanged;

    syncDisabledState(node, hidden);
    syncAncestorRows(node);

    if (hidden && clearOnHide && stateChanged) {
        clearConditionNodeValues(node);
    }

    return stateChanged;
}

function applyMutedConditionVisibility(node: Element, hidden: boolean): boolean {
    let stateChanged = false;

    if (hidden) {
        if (node.hasAttribute(CONDITIONAL_HIDDEN_ATTR)
            || node.hasAttribute(PAGE_HIDDEN_ATTR)
            || node.classList.contains(CONDITIONAL_HIDDEN_CLASS)
            || node.classList.contains(PAGE_HIDDEN_CLASS)) {
            clearHideState(node);
            stateChanged = true;
        }

        if (!node.hasAttribute(CP_MUTED_ATTR)) {
            node.setAttribute(CP_MUTED_ATTR, 'true');
            stateChanged = true;
        }

        if (!node.classList.contains(CP_MUTED_CLASS)) {
            node.classList.add(CP_MUTED_CLASS);
            stateChanged = true;
        }
    } else {
        if (node.hasAttribute(CP_MUTED_ATTR)
            || node.classList.contains(CP_MUTED_CLASS)
            || node.classList.contains(CP_MUTED_EXPANDED_CLASS)) {
            clearCpMutedState(node);
            stateChanged = true;
        }

        if (node.hasAttribute(CONDITIONAL_HIDDEN_ATTR)
            || node.hasAttribute(PAGE_HIDDEN_ATTR)
            || node.classList.contains(CONDITIONAL_HIDDEN_CLASS)
            || node.classList.contains(PAGE_HIDDEN_CLASS)) {
            clearHideState(node);
            stateChanged = true;
        }
    }

    syncAncestorRows(node);

    return stateChanged;
}
