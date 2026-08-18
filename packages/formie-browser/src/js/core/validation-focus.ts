import { addThemeClasses } from '#theme/theme-classes';
import { isValidationSkipped } from '#validation/skip';

function appendDescribedBy(input: HTMLElement, describedById: string): void {
    const current = (input.getAttribute('aria-describedby') || '').trim();
    const items = current ? current.split(/\s+/) : [];

    if (!items.includes(describedById)) {
        items.push(describedById);
    }

    input.setAttribute('aria-describedby', items.join(' ').trim());
}

function getFirstErroredField(form: HTMLFormElement): HTMLElement | null {
    const fields = Array.from(form.querySelectorAll('[data-formie-field-handle]')) as HTMLElement[];

    return fields.find((field) => {
        if (field.getAttribute('data-formie-field-has-error') === 'true') {
            return true;
        }

        return field.querySelector('[data-formie-field-error]') !== null;
    }) || null;
}

function getFieldFocusTarget(field: HTMLElement): HTMLElement | null {
    const invalidControl = Array.from(field.querySelectorAll('[aria-invalid="true"]')).find((node) => {
        return !isValidationSkipped(node);
    }) as HTMLElement | undefined;

    if (invalidControl) {
        return invalidControl;
    }

    return Array.from(field.querySelectorAll(
        'input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])',
    )).find((node) => {
        return !isValidationSkipped(node);
    }) as HTMLElement | undefined ?? null;
}

function getFormLevelErrorTarget(form: HTMLFormElement): HTMLElement | null {
    return form.querySelector(
        '[data-formie-message-error], [data-formie-error-container], [data-formie-errors]',
    ) as HTMLElement | null;
}

export function enhanceServerRenderedFieldErrors(form: HTMLFormElement): void {
    form.querySelectorAll('[data-formie-field-handle]').forEach((fieldNode) => {
        const field = fieldNode as HTMLElement;
        const hasServerError = field.getAttribute('data-formie-field-has-error') === 'true'
            || field.querySelector('[data-formie-field-error]') !== null;

        if (!hasServerError) {
            return;
        }

        field.setAttribute('data-formie-field-has-error', 'true');
        addThemeClasses(field, form, 'fieldLayoutError');

        const errorContainer = field.querySelector('[data-formie-field-errors]') as HTMLElement | null;
        const errorContainerId = errorContainer?.id || '';
        const primaryError = field.querySelector('[data-formie-field-error]') as HTMLElement | null;
        const primaryErrorId = primaryError?.id || '';

        field.querySelectorAll('input, select, textarea').forEach((input) => {
            const element = input as HTMLElement;

            if (isValidationSkipped(element)) {
                return;
            }

            element.setAttribute('aria-invalid', 'true');
            addThemeClasses(element, form, 'fieldControlError');
            element.setAttribute('data-formie-input-has-error', 'true');

            if (errorContainerId) {
                appendDescribedBy(element, errorContainerId);
            }

            if (primaryErrorId) {
                element.setAttribute('aria-errormessage', primaryErrorId);
            }
        });
    });
}

export function hasServerRenderedValidationErrors(form: HTMLFormElement): boolean {
    return !!getFirstErroredField(form) || !!getFormLevelErrorTarget(form);
}

export function focusFirstValidationError(form: HTMLFormElement): boolean {
    const field = getFirstErroredField(form);

    if (field) {
        const target = getFieldFocusTarget(field);

        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });

            if (typeof target.focus === 'function') {
                try {
                    target.focus({ preventScroll: true });
                } catch {
                    target.focus();
                }
            }

            return true;
        }

        field.scrollIntoView({ behavior: 'smooth', block: 'center' });

        return true;
    }

    const formError = getFormLevelErrorTarget(form);

    if (!formError) {
        return false;
    }

    formError.scrollIntoView({ behavior: 'smooth', block: 'center' });

    return true;
}
