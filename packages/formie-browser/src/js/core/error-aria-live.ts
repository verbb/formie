export type ErrorAriaLivePreference = 'polite' | 'assertive' | 'off';

export function getErrorAriaLivePreference(form: HTMLFormElement): ErrorAriaLivePreference {
    const value = (form.dataset.formieErrorAriaLive || 'polite').trim().toLowerCase();

    if (value === 'assertive' || value === 'off') {
        return value;
    }

    return 'polite';
}

export function resolveValidationErrorAriaLive(
    preference: ErrorAriaLivePreference,
    submitted: boolean,
): 'polite' | 'assertive' | null {
    if (preference === 'off') {
        return null;
    }

    if (!submitted) {
        return 'polite';
    }

    return preference;
}

export function resolveSubmitErrorAriaLive(preference: ErrorAriaLivePreference): 'polite' | 'assertive' | null {
    if (preference === 'off') {
        return null;
    }

    return preference;
}

export function applyErrorAriaLive(element: HTMLElement, ariaLive: 'polite' | 'assertive' | null): void {
    if (ariaLive) {
        element.setAttribute('aria-live', ariaLive);
        element.setAttribute('aria-atomic', 'true');
        return;
    }

    element.removeAttribute('aria-live');
    element.removeAttribute('aria-atomic');
}
