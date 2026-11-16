import type { FormAction } from '#contracts/common';
import { dispatchFormieDomEvent } from '#core/dom-events';
import { dispatchPageClientEventForSubmit } from '#core/page-client-event';
import { clearSubmitFeedback } from '#core/submit-flow';
import { applySubmitResultUi } from '#core/submit-result-ui';
import { applyPageState, scrollToFormTop } from '#core/submit-result-state';
import { focusFirstValidationError } from '#core/validation-focus';
import { getFormPages } from '#validation/scope';
import type { FormieValidator } from '#validation/validator';

// Editing a completed submission saves it as one revision with every answer, so its pages change in the
// browser. Only the last page sends the save.

export function isReviseForm(form: HTMLFormElement): boolean {
    return form.hasAttribute('data-formie-revise');
}

function getNavigablePages(form: HTMLFormElement): HTMLElement[] {
    return getFormPages(form).filter((page) => {
        return !page.hasAttribute('data-formie-conditionally-hidden');
    });
}

function getCurrentPage(form: HTMLFormElement): HTMLElement | null {
    return getFormPages(form).find((page) => {
        return !page.hasAttribute('data-formie-page-hidden');
    }) || null;
}

function pageHasErrors(page: HTMLElement): boolean {
    return !!page.querySelector('[data-formie-field-has-error]');
}

function validatePage(form: HTMLFormElement, page: HTMLElement, validator: FormieValidator | null, validate: boolean): boolean {
    if (!validate || !validator) {
        return true;
    }

    const errors = validator.submit(page, { final: false });

    if (!errors.length) {
        return true;
    }

    applySubmitResultUi(form, {
        ok: false,
        stage: 'validate',
        code: 'VALIDATION_FAILED',
        message: validator.config.errorMessage || 'Validation failed.',
        fieldErrors: validator.getFieldErrors(errors),
        formErrors: [validator.config.errorMessage || 'Validation failed.'],
    });
    focusFirstValidationError(form);

    return false;
}

function showPage(form: HTMLFormElement, page: HTMLElement, validator: FormieValidator | null): void {
    const pageId = page.getAttribute('data-formie-page-id');

    if (!pageId) {
        return;
    }

    clearSubmitFeedback(form);
    validator?.resetLiveState();
    applyPageState(form, pageId);
    scrollToFormTop(form);
    dispatchFormieDomEvent(form, 'formie:page:navigate:after', { pageId });
}

/**
 * Changes page for a page button or Back on a revise form. Returns false when the request should be sent
 * as usual, which is the submit button on the last page.
 */
export function handleRevisePageAction(
    form: HTMLFormElement,
    action: FormAction,
    validator: FormieValidator | null,
    validate: boolean,
): boolean {
    if (action !== 'submit' && action !== 'back') {
        return false;
    }

    const pages = getNavigablePages(form);
    const current = getCurrentPage(form);
    const index = current ? pages.indexOf(current) : -1;

    if (!current || index === -1) {
        return false;
    }

    if (action === 'back') {
        if (index > 0) {
            showPage(form, pages[index - 1], validator);
        }

        return true;
    }

    const next = pages[index + 1];

    if (!next) {
        return false;
    }

    if (validatePage(form, current, validator, validate)) {
        dispatchPageClientEventForSubmit(form, action);
        showPage(form, next, validator);
    }

    return true;
}

/**
 * Changes page for a page tab on a revise form. Moving forward checks the current page first.
 */
export function handleRevisePageTab(form: HTMLFormElement, pageId: string, validator: FormieValidator | null, validate: boolean): void {
    const pages = getNavigablePages(form);
    const current = getCurrentPage(form);
    const target = pages.find((page) => {
        return page.getAttribute('data-formie-page-id') === pageId;
    });

    if (!target || target === current) {
        return;
    }

    if (current && pages.indexOf(target) > pages.indexOf(current) && !validatePage(form, current, validator, validate)) {
        return;
    }

    showPage(form, target, validator);
}

/**
 * Shows the first page with an error when the visible page has none, such as after the server rejects an
 * answer on an earlier page.
 */
export function showRevisePageWithErrors(form: HTMLFormElement): void {
    const current = getCurrentPage(form);

    if (current && pageHasErrors(current)) {
        return;
    }

    const page = getNavigablePages(form).find(pageHasErrors);
    const pageId = page?.getAttribute('data-formie-page-id');

    if (!pageId) {
        return;
    }

    applyPageState(form, pageId);
    focusFirstValidationError(form);
}
