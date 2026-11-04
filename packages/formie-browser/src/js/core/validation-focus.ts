import { appendDescribedBy, setErrorMessageReference } from '#core/field-error-aria';
import { addThemeClasses } from '#theme/theme-classes';
import { isValidationSkipped } from '#validation/skip';

function getFirstErroredField(form: HTMLFormElement): HTMLElement | null {
    const fields = Array.from(form.querySelectorAll('[data-formie-field-handle]')) as HTMLElement[];

    return fields.find((field) => {
        if (field.getAttribute('data-formie-field-has-error') === 'true') {
            return true;
        }

        return field.querySelector('[data-formie-field-error]') !== null;
    }) || null;
}

function getVisibleControl(control: HTMLElement): HTMLElement {
    if (control instanceof HTMLTextAreaElement) {
        const richText = (control as HTMLTextAreaElement & { richText?: { content: HTMLElement } }).richText;

        if (richText) {
            return richText.content;
        }
    }

    return control;
}

function findFocusableControl(nodes: Element[]): HTMLElement | null {
    for (const node of nodes) {
        const control = getVisibleControl(node as HTMLElement);

        // Checkboxes and Agree put a hidden input first, and a searchable dropdown hides its
        // select behind a search input. Focusing either does nothing, so keep looking.
        if (!(control instanceof HTMLInputElement && control.type === 'hidden') && control.getClientRects().length > 0) {
            return control;
        }
    }

    return null;
}

function getFieldFocusTarget(field: HTMLElement): HTMLElement | null {
    const invalidControls = Array.from(field.querySelectorAll('[aria-invalid="true"]')).filter((node) => {
        return !isValidationSkipped(node);
    });

    // Fall back to any usable control. This includes a searchable dropdown's search input, which
    // is skipped by validation because it holds no value, but is what a visitor actually uses.
    return findFocusableControl(invalidControls)
        || findFocusableControl(Array.from(field.querySelectorAll(
            'input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])',
        )));
}

function getFormLevelErrorTarget(form: HTMLFormElement): HTMLElement | null {
    return form.querySelector(
        '[data-formie-message-error], [data-formie-error-container], [data-formie-errors]',
    ) as HTMLElement | null;
}

export function enhanceServerRenderedFieldErrors(form: HTMLFormElement): void {
    form.querySelectorAll('[data-formie-field-handle]').forEach((fieldNode) => {
        const field = fieldNode as HTMLElement;
        const messages = Array.from(field.querySelectorAll('[data-formie-field-error]'));

        // A Group or Repeater contains the fields inside it, and an error on one of those is
        // not an error on its siblings. Only act on a field for the messages it owns.
        const hasOwnMessage = messages.some((message) => {
            return message.closest('[data-formie-field-handle]') === field;
        });
        const hasOwnFlag = field.getAttribute('data-formie-field-has-error') === 'true' && messages.length === 0;

        if (!hasOwnMessage && !hasOwnFlag) {
            return;
        }

        field.setAttribute('data-formie-field-has-error', 'true');
        addThemeClasses(field, form, 'fieldLayoutError');

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

            if (primaryErrorId) {
                // Pair aria-errormessage + aria-describedby on the message id (#2946).
                setErrorMessageReference(element, primaryErrorId);
            }

            const instructions = field.querySelector('[data-formie-instructions]') as HTMLElement | null;
            if (instructions?.id) {
                appendDescribedBy(element, instructions.id);
            }
        });
    });
}

export function hasServerRenderedValidationErrors(form: HTMLFormElement): boolean {
    return !!getFirstErroredField(form) || !!getFormLevelErrorTarget(form);
}

// Browser modules can replace a control after focus lands on it, or only create the visible one once
// they mount. Leave focus alone if the visitor has already moved it somewhere usable.
export function restoreLostValidationFocus(form: HTMLFormElement): void {
    const active = document.activeElement;

    if (!active || active === document.body || active.getClientRects().length === 0) {
        focusFirstValidationError(form);
    }
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
