import type { FormSubmitResult } from '#contracts/schema';
import { ensureFieldErrorContainer as resolveFieldErrorContainer } from '#core/field-error-container';
import { syncPageTabErrors } from '#core/page-tab-errors';
import { focusFirstValidationError } from '#core/validation-focus';
import { setFormHiddenState } from '#core/submit-result-state';
import { addThemeClasses, removeThemeClasses } from '#theme/theme-classes';

const successHideTimers = new WeakMap<HTMLFormElement, number>();

function getConfiguredSubmitAction(form: HTMLFormElement): string {
    return (form.dataset.formieSubmitAction || '').trim();
}

function getErrorMessagePosition(form: HTMLFormElement): string {
    return (form.dataset.formieErrorMessagePosition || 'top-form').trim() || 'top-form';
}

function getSuccessMessagePosition(form: HTMLFormElement): string {
    return (form.dataset.formieSubmitActionMessagePosition || '').trim();
}

function getSuccessMessageTimeoutMs(form: HTMLFormElement): number | null {
    const rawValue = (form.dataset.formieSubmitActionMessageTimeout || '').trim();

    if (!rawValue) {
        return null;
    }

    const seconds = Number.parseFloat(rawValue);

    if (!Number.isFinite(seconds) || seconds < 0) {
        return null;
    }

    return Math.round(seconds * 1000);
}

function shouldHideFormOnSuccess(form: HTMLFormElement): boolean {
    const rawValue = form.dataset.formieSubmitActionFormHide;

    if (rawValue === undefined) {
        return false;
    }

    const normalized = rawValue.trim().toLowerCase();
    return normalized === 'true' || normalized === '1' || normalized === '';
}

function clearPendingSuccessHide(form: HTMLFormElement): void {
    const timerId = successHideTimers.get(form);

    if (typeof timerId === 'number') {
        window.clearTimeout(timerId);
        successHideTimers.delete(form);
    }
}

function getTopMessageHost(form: HTMLFormElement): HTMLElement {
    return form.querySelector('[data-formie-form-messages-top]') as HTMLElement || form;
}

function getBottomMessageHost(form: HTMLFormElement): HTMLElement {
    return form.querySelector('[data-formie-form-messages-bottom]') as HTMLElement || form;
}

function getErrorMessageHost(form: HTMLFormElement, position: string): HTMLElement {
    if (position === 'bottom-form') {
        return getBottomMessageHost(form);
    }

    return getTopMessageHost(form);
}

function getSuccessMessageHost(form: HTMLFormElement, position: string): HTMLElement {
    if (position === 'top-form') {
        return getTopMessageHost(form);
    }

    // Hidden-on-success forms cannot rely on a bottom host that will disappear,
    // so their success message stays attached to the root form container.
    if (position === 'bottom-form' && !shouldHideFormOnSuccess(form)) {
        return getBottomMessageHost(form);
    }

    return form;
}

function ensureFormErrorContainer(form: HTMLFormElement): HTMLElement {
    const position = getErrorMessagePosition(form);
    const host = getErrorMessageHost(form, position);
    let container = host.querySelector('[data-formie-error-container], [data-formie-errors]') as HTMLElement | null;

    if (!container) {
        container = document.createElement('div');
        container.setAttribute('data-formie-errors', 'true');
        addThemeClasses(container, form, 'errors');
    }

    // Reuse one form-level container so repeated submits replace the current
    // message state instead of stacking duplicate wrappers in the DOM.
    container.setAttribute('data-formie-error-container', 'true');

    if (position === 'bottom-form') {
        host.append(container);
    } else {
        host.prepend(container);
    }

    return container;
}

function ensureFormErrorMessageContainer(form: HTMLFormElement, container: HTMLElement): HTMLElement {
    let messageContainer = container.querySelector('[data-formie-error-message-container], [data-formie-message][data-formie-message-error]') as HTMLElement | null;

    if (!messageContainer) {
        messageContainer = document.createElement('div');
        messageContainer.setAttribute('data-formie-error-message-container', 'true');
        container.appendChild(messageContainer);
    }

    messageContainer.setAttribute('data-formie-message', 'true');
    messageContainer.setAttribute('data-formie-message-error', 'true');
    addThemeClasses(messageContainer, form, 'message', 'messageError');
    // Form-level errors use a live region because they are often the only output
    // for submit-stage failures such as provider or transport errors.
    messageContainer.setAttribute('role', 'alert');
    messageContainer.setAttribute('aria-live', 'polite');
    messageContainer.setAttribute('aria-atomic', 'true');

    return messageContainer;
}

function ensureFormSuccessContainer(form: HTMLFormElement, position: string): HTMLElement {
    let container = form.querySelector('[data-formie-success-container]') as HTMLElement | null;
    const host = getSuccessMessageHost(form, position);

    if (!container) {
        container = document.createElement('div');
        container.setAttribute('data-formie-success-container', 'true');
        addThemeClasses(container, form, 'successes');
    }

    if (position === 'bottom-form') {
        host.append(container);
    } else if (host === form) {
        host.prepend(container);
    } else {
        host.prepend(container);
    }

    return container;
}

function ensureFieldErrorContainer(fieldNode: Element): HTMLElement {
    return resolveFieldErrorContainer(fieldNode, (container) => {
        addThemeClasses(container, fieldNode, 'fieldErrors');
    });
}

function removeDescribedBy(input: HTMLElement, describedById: string): void {
    const current = (input.getAttribute('aria-describedby') || '').trim();

    if (!current) {
        return;
    }

    const nextValue = current.split(/\s+/).filter((item) => {
        return item !== describedById;
    }).join(' ').trim();

    if (nextValue) {
        input.setAttribute('aria-describedby', nextValue);
        return;
    }

    input.removeAttribute('aria-describedby');
}

function setErrorMessageReference(input: HTMLElement, errorMessageId: string): void {
    input.setAttribute('aria-errormessage', errorMessageId);
}

function clearErrorMessageReference(input: HTMLElement, errorMessageId: string): void {
    if (input.getAttribute('aria-errormessage') === errorMessageId) {
        input.removeAttribute('aria-errormessage');
    }
}

export function clearFieldErrors(form: HTMLFormElement): void {
    form.querySelectorAll('[data-formie-field-handle]').forEach((fieldNode) => {
        const fieldElement = fieldNode as HTMLElement;
        const container = fieldElement.querySelector('[data-formie-field-errors]') as HTMLElement | null;
        const containerId = container?.id || '';
        const errorMessageIds = Array.from(fieldElement.querySelectorAll('[data-formie-field-error]')).map((node) => {
            return (node as HTMLElement).id;
        }).filter(Boolean);

        removeThemeClasses(fieldElement, form, 'fieldLayoutError');
        fieldElement.removeAttribute('data-formie-field-has-error');

        fieldElement.querySelectorAll('[data-formie-field-error]').forEach((node) => {
            node.remove();
        });

        if (container && !container.querySelector('[data-formie-field-error]')) {
            container.innerHTML = '';
        }

        fieldElement.querySelectorAll('input, select, textarea').forEach((input) => {
            const element = input as HTMLElement;
            element.removeAttribute('aria-invalid');
            removeThemeClasses(element, form, 'fieldControlError');
            element.removeAttribute('data-formie-input-has-error');

            if (containerId) {
                removeDescribedBy(element, containerId);
            }

            errorMessageIds.forEach((errorMessageId) => {
                clearErrorMessageReference(element, errorMessageId);
            });
        });
    });

    syncPageTabErrors(form);
}

export function clearFormErrors(form: HTMLFormElement): void {
    form.querySelectorAll('[data-formie-error-container], [data-formie-errors]').forEach((node) => {
        const container = node as HTMLElement;

        container.querySelectorAll('[data-formie-error]').forEach((errorNode) => {
            errorNode.remove();
        });

        removeThemeClasses(container, form, 'message', 'messageError');
        container.removeAttribute('data-formie-message');
        container.removeAttribute('data-formie-message-error');
        container.removeAttribute('role');
        container.removeAttribute('aria-live');
        container.removeAttribute('aria-atomic');

        if (!container.querySelector('[data-formie-error]')) {
            container.innerHTML = '';
        }
    });
}

export function clearFormSuccess(form: HTMLFormElement): void {
    clearPendingSuccessHide(form);

    form.querySelectorAll('[data-formie-message-success]:not([data-formie-success-container])').forEach((node) => {
        node.remove();
    });

    form.querySelectorAll('[data-formie-success-container]').forEach((node) => {
        const container = node as HTMLElement;

        container.querySelectorAll('[data-formie-success]').forEach((successNode) => {
            successNode.remove();
        });

        removeThemeClasses(container, form, 'message', 'messageSuccess');
        container.removeAttribute('data-formie-message');
        container.removeAttribute('data-formie-message-success');
        container.removeAttribute('role');
        container.removeAttribute('aria-live');
        container.removeAttribute('aria-atomic');

        if (!container.querySelector('[data-formie-success]')) {
            container.innerHTML = '';
        }
    });

    if (!(getConfiguredSubmitAction(form) === 'message' && shouldHideFormOnSuccess(form))) {
        setFormHiddenState(form, false);
    }
}

export function clearAriaInvalid(form: HTMLFormElement): void {
    form.querySelectorAll('[aria-invalid="true"]').forEach((node) => {
        node.removeAttribute('aria-invalid');
    });
}

function appendDescribedBy(input: HTMLElement, describedById: string): void {
    const current = (input.getAttribute('aria-describedby') || '').trim();
    const items = current ? current.split(/\s+/) : [];

    if (!items.includes(describedById)) {
        items.push(describedById);
    }

    input.setAttribute('aria-describedby', items.join(' ').trim());
}

export function renderFieldErrors(form: HTMLFormElement, fieldErrors: Record<string, string[]>): void {
    Object.entries(fieldErrors).forEach(([handle, messages]) => {
        const fieldNode = form.querySelector(`[data-formie-field-handle="${handle}"]`);

        if (!fieldNode) {
            return;
        }

        const container = ensureFieldErrorContainer(fieldNode);
        const containerId = (container.id && container.id.trim())
            ? container.id
            : `${handle}-errors`;
        container.id = containerId;
        container.setAttribute('aria-live', 'polite');
        container.setAttribute('aria-atomic', 'true');
        addThemeClasses(fieldNode as HTMLElement, form, 'fieldLayoutError');
        (fieldNode as HTMLElement).setAttribute('data-formie-field-has-error', 'true');

        messages.forEach((message, index) => {
            const errorNode = document.createElement('div');
            errorNode.setAttribute('data-formie-field-error', 'true');
            errorNode.setAttribute('role', 'alert');
            errorNode.id = `${containerId}-${index + 1}`;
            addThemeClasses(errorNode, form, 'fieldError');
            errorNode.textContent = message;
            container.appendChild(errorNode);
        });

        // Point every control in the field at the first rendered message while
        // still rendering the full list for visual output.
        const primaryErrorId = (container.querySelector('[data-formie-field-error]') as HTMLElement | null)?.id;

        fieldNode.querySelectorAll('input, select, textarea').forEach((input) => {
            const element = input as HTMLElement;
            element.setAttribute('aria-invalid', 'true');
            addThemeClasses(element, form, 'fieldControlError');
            element.setAttribute('data-formie-input-has-error', 'true');
            appendDescribedBy(element, containerId);

            if (primaryErrorId) {
                setErrorMessageReference(element, primaryErrorId);
            }

            const instructions = fieldNode.querySelector('[data-formie-instructions]') as HTMLElement | null;
            if (instructions?.id) {
                appendDescribedBy(element, instructions.id);
            }
        });
    });

    syncPageTabErrors(form);
}

export function renderFormErrors(form: HTMLFormElement, formErrors: string[]): void {
    const container = ensureFormErrorContainer(form);
    const messageContainer = ensureFormErrorMessageContainer(form, container);

    addThemeClasses(container, form, 'errors');

    formErrors.forEach((error) => {
        const errorNode = document.createElement('div');
        errorNode.setAttribute('data-formie-error', 'true');
        errorNode.setAttribute('role', 'alert');
        addThemeClasses(errorNode, form, 'error');
        errorNode.innerHTML = error;
        messageContainer.appendChild(errorNode);
    });
}

function shouldRenderSuccessMessage(form: HTMLFormElement, result: FormSubmitResult): boolean {
    if (!result.message || result.nextPage || result.redirect) {
        // Page changes and redirects are transitional outcomes, not terminal
        // success states that should leave an in-form success message behind.
        return false;
    }

    if (result.action === 'save') {
        return true;
    }

    return getConfiguredSubmitAction(form) === 'message' && getSuccessMessagePosition(form) !== '';
}

export function renderFormSuccess(form: HTMLFormElement, message: string): void {
    const position = getSuccessMessagePosition(form);

    if (!position) {
        return;
    }

    const container = ensureFormSuccessContainer(form, position);

    addThemeClasses(container, form, 'message', 'messageSuccess');
    container.setAttribute('data-formie-message', 'true');
    container.setAttribute('data-formie-message-success', 'true');
    container.setAttribute('role', 'status');
    container.setAttribute('aria-live', 'polite');
    container.setAttribute('aria-atomic', 'true');
    const successNode = document.createElement('div');
    successNode.setAttribute('data-formie-success', 'true');
    addThemeClasses(successNode, form, 'success');
    successNode.innerHTML = message;
    container.appendChild(successNode);

    if (shouldHideFormOnSuccess(form)) {
        setFormHiddenState(form, true);
    }

    const timeoutMs = getSuccessMessageTimeoutMs(form);

    if (timeoutMs !== null) {
        const timerId = window.setTimeout(() => {
            successHideTimers.delete(form);
            clearFormSuccess(form);
        }, timeoutMs);

        successHideTimers.set(form, timerId);
    }
}

export function applySubmitResultUi(form: HTMLFormElement, result: FormSubmitResult): void {
    clearFieldErrors(form);
    clearFormErrors(form);
    clearFormSuccess(form);
    clearAriaInvalid(form);

    if (result.ok) {
        if (shouldRenderSuccessMessage(form, result)) {
            renderFormSuccess(form, result.message || '');
        }

        return;
    }

    if (result.fieldErrors) {
        renderFieldErrors(form, result.fieldErrors);
    }

    if (result.formErrors?.length) {
        renderFormErrors(form, result.formErrors);
    } else if (!result.fieldErrors && result.message) {
        renderFormErrors(form, [result.message]);
    }

    focusFirstValidationError(form);
}
