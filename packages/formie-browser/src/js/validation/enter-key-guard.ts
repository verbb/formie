import type { FormAction } from '#contracts/common';
import { getValidationScope } from '#validation/scope';

function isConditionallyInactive(element: Element): boolean {
    return element.hasAttribute('data-formie-conditionally-hidden')
        || !!element.closest('[data-formie-conditionally-hidden]')
        || element.hasAttribute('data-formie-page-hidden')
        || !!element.closest('[data-formie-page-hidden]');
}

function isActionControlVisible(form: HTMLFormElement, action: FormAction): boolean {
    const controls = form.querySelectorAll(`[data-formie-action="${action}"]`);

    return Array.from(controls).some((control) => {
        return !isConditionallyInactive(control);
    });
}

function resolveEnterKeyAction(form: HTMLFormElement): FormAction {
    const { final } = getValidationScope(form);

    return final ? 'submit' : 'submit';
}

function shouldBlockEnterSubmit(form: HTMLFormElement): boolean {
    const action = resolveEnterKeyAction(form);

    return !isActionControlVisible(form, action);
}

export function bindEnterKeyGuard(form: HTMLFormElement): () => void {
    const handler = (event: KeyboardEvent) => {
        if (event.key !== 'Enter' || event.defaultPrevented) {
            return;
        }

        const target = event.target;

        if (!(target instanceof HTMLInputElement || target instanceof HTMLSelectElement)) {
            return;
        }

        if (target instanceof HTMLInputElement) {
            if (target.type === 'button' || target.type === 'submit' || target.type === 'reset' || target.type === 'file') {
                return;
            }
        }

        if (!shouldBlockEnterSubmit(form)) {
            return;
        }

        // Browsers can still implicit-submit through hidden/disabled submit
        // controls; block that path when conditions hide the primary action.
        event.preventDefault();
    };

    form.addEventListener('keydown', handler, true);

    return () => {
        form.removeEventListener('keydown', handler, true);
    };
}
