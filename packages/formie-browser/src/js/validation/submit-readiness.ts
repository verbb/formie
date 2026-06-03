import { getValidationScope } from '#validation/scope';
import type { FormieValidator } from '#validation/validator';

const SUBMIT_VALIDATION_DISABLED_ATTR = 'data-formie-submit-validation-disabled';
const PRESERVED_DISABLED_ATTR = 'data-formie-preserve-disabled';
const SUBMIT_READY_ATTR = 'data-formie-submit-ready';

function isSubmitReadinessEnabled(form: HTMLFormElement): boolean {
    return form.dataset.formieDisableSubmitUntilValid === 'true';
}

function getPrimarySubmitButtons(form: HTMLFormElement): HTMLButtonElement[] {
    return Array.from(form.querySelectorAll('button[data-formie-action="submit"]')).filter((button): button is HTMLButtonElement => {
        return button instanceof HTMLButtonElement;
    });
}

function isSubmitButtonVisible(button: HTMLButtonElement): boolean {
    return !button.hasAttribute('data-formie-conditionally-hidden')
        && !button.closest('[data-formie-conditionally-hidden]');
}

export function syncSubmitReadiness(form: HTMLFormElement, validator: FormieValidator): void {
    if (!isSubmitReadinessEnabled(form)) {
        return;
    }

    if (form.getAttribute('data-formie-loading') === 'true') {
        return;
    }

    const { scope, final } = getValidationScope(form);
    const isReady = validator.isValid(scope, {
        includeHiddenPages: final,
    });

    form.setAttribute(SUBMIT_READY_ATTR, isReady ? 'true' : 'false');

    getPrimarySubmitButtons(form).forEach((button) => {
        if (!isSubmitButtonVisible(button)) {
            return;
        }

        if (isReady) {
            if (!button.hasAttribute(SUBMIT_VALIDATION_DISABLED_ATTR)) {
                return;
            }

            if (button.hasAttribute(PRESERVED_DISABLED_ATTR)) {
                button.disabled = true;
                button.removeAttribute(PRESERVED_DISABLED_ATTR);
            } else {
                button.disabled = false;
            }

            button.removeAttribute(SUBMIT_VALIDATION_DISABLED_ATTR);
            return;
        }

        if (!button.hasAttribute(SUBMIT_VALIDATION_DISABLED_ATTR)) {
            if (button.disabled) {
                button.setAttribute(PRESERVED_DISABLED_ATTR, 'true');
            }

            button.setAttribute(SUBMIT_VALIDATION_DISABLED_ATTR, 'true');
        }

        button.disabled = true;
    });
}

export function bindSubmitReadiness(
    form: HTMLFormElement,
    validator: FormieValidator,
    target: Element,
): () => void {
    if (!isSubmitReadinessEnabled(form)) {
        return () => {};
    }

    let syncQueued = false;

    const scheduleSync = (): void => {
        if (syncQueued) {
            return;
        }

        syncQueued = true;

        queueMicrotask(() => {
            syncQueued = false;
            syncSubmitReadiness(form, validator);
        });
    };

    scheduleSync();

    const inputHandler = (): void => {
        scheduleSync();
    };

    form.addEventListener('input', inputHandler, true);
    form.addEventListener('change', inputHandler, true);

    const resetHandler = (): void => {
        window.setTimeout(() => {
            scheduleSync();
        }, 0);
    };

    form.addEventListener('reset', resetHandler);

    const conditionsHandler = (): void => {
        scheduleSync();
    };

    target.addEventListener('formie:conditions:evaluated', conditionsHandler as EventListener);

    const observer = new MutationObserver((mutations) => {
        const shouldSync = mutations.some((mutation) => {
            if (mutation.type === 'attributes') {
                const attributeName = mutation.attributeName || '';

                return attributeName === 'data-formie-page-hidden'
                    || attributeName === 'data-formie-conditionally-hidden'
                    || attributeName === 'data-formie-loading'
                    || attributeName === 'disabled';
            }

            return mutation.type === 'childList';
        });

        if (shouldSync) {
            scheduleSync();
        }
    });

    observer.observe(form, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: [
            'data-formie-page-hidden',
            'data-formie-conditionally-hidden',
            'data-formie-loading',
            'disabled',
        ],
    });

    return () => {
        form.removeEventListener('input', inputHandler, true);
        form.removeEventListener('change', inputHandler, true);
        form.removeEventListener('reset', resetHandler);
        target.removeEventListener('formie:conditions:evaluated', conditionsHandler as EventListener);
        observer.disconnect();
    };
}
