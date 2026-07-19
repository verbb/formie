const isVisibleElement = (element) => {
    if (!(element instanceof HTMLElement)) {
        return false;
    }

    if (element.hidden || element.getAttribute('aria-hidden') === 'true') {
        return false;
    }

    if (element.closest('[hidden], [aria-hidden="true"]')) {
        return false;
    }

    if (element.getClientRects().length === 0) {
        return false;
    }

    return true;
};

const TEXT_ENTRY_INPUT_TYPES = new Set([
    '',
    'text',
    'search',
    'email',
    'url',
    'tel',
    'password',
]);

const isTextEntryControl = (element) => {
    if (element instanceof HTMLTextAreaElement) {
        return true;
    }

    if (!(element instanceof HTMLInputElement)) {
        return false;
    }

    return TEXT_ENTRY_INPUT_TYPES.has((element.type || '').toLowerCase());
};

/** True for kit text hosts (`pk-input` / `pk-textarea`) whose native control is in shadow DOM. */
const isKitTextHost = (element) => {
    const tag = element?.tagName;

    return tag === 'PK-INPUT' || tag === 'PK-TEXTAREA';
};

/** Resolve the native text control for caret APIs — the element itself, or the shadow input of a kit host. */
const resolveNativeControl = (element) => {
    if (element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement) {
        return element;
    }

    const shadowControl = element?.shadowRoot?.querySelector?.('input, textarea');

    return shadowControl instanceof HTMLInputElement || shadowControl instanceof HTMLTextAreaElement
        ? shadowControl
        : null;
};

/** Current value for either a native control or a kit host (property, not attribute). */
const readControlValue = (element) => {
    if (typeof element?.value === 'string') {
        return element.value;
    }

    return resolveNativeControl(element)?.value ?? '';
};

/** Deepest focused element across nested shadow roots. */
const getDeepActiveElement = () => {
    let active = document.activeElement;

    while (active?.shadowRoot?.activeElement) {
        active = active.shadowRoot.activeElement;
    }

    return active;
};

/**
 * Focus the first visible text control under `root` when it is empty.
 *
 * Schema forms render `pk-field` → `pk-input`/`pk-textarea` hosts: the native
 * `<input>` lives in the host's SHADOW root, so light-DOM `input` selectors never
 * match it. Query kit hosts first (host `.focus()` delegates inward), and only
 * fall back to bare light-DOM inputs (e.g. non-kit custom UIs).
 *
 * "If empty" keeps this safe for edit flows: existing fields already have a label,
 * so focus is only claimed for newly created entities with a blank label.
 */
export const focusFirstVisibleInputIfEmpty = ({
    root,
    maxAttempts = 10,
    retryDelayMs = 40,
}) => {
    let cancelled = false;
    let attempts = 0;
    let retryTimeout = null;

    const clearRetryTimeout = () => {
        if (retryTimeout !== null) {
            window.clearTimeout(retryTimeout);
            retryTimeout = null;
        }
    };

    const scheduleRetry = () => {
        attempts += 1;

        if (attempts < maxAttempts) {
            retryTimeout = window.setTimeout(run, retryDelayMs);
        }
    };

    const run = () => {
        if (cancelled || !root) {
            return;
        }

        // Preferred: the Label field's control (pk-field data-name is the schema path).
        const labelField = Array.from(
            root.querySelectorAll('pk-field[data-name="label"], pk-field[data-name$=".label"]'),
        ).find(isVisibleElement);

        const controlFromLabelField = labelField
            ? labelField.querySelector('pk-input:not([disabled]), pk-textarea:not([disabled]), input:not([type="hidden"]):not([disabled]):not([readonly]), textarea:not([disabled]):not([readonly])')
            : null;

        // Kit hosts anywhere under root (first visible one).
        const kitHosts = Array.from(
            root.querySelectorAll('pk-input:not([disabled]):not([readonly]), pk-textarea:not([disabled]):not([readonly])'),
        ).filter(isVisibleElement);

        // Legacy/light-DOM controls (label-named first, then any text input).
        const lightLabelInputs = Array.from(
            root.querySelectorAll('input[name="label"]:not([disabled]):not([readonly]), input[name$=".label"]:not([disabled]):not([readonly]), textarea[name="label"]:not([disabled]):not([readonly]), textarea[name$=".label"]:not([disabled]):not([readonly])'),
        ).filter((input) => isVisibleElement(input) && isTextEntryControl(input));

        const lightInputs = Array.from(
            root.querySelectorAll('input:not([type="hidden"]):not([disabled]):not([readonly]), textarea:not([disabled]):not([readonly])'),
        ).filter((input) => isVisibleElement(input) && isTextEntryControl(input));

        const targetInput = controlFromLabelField
            || lightLabelInputs[0]
            || kitHosts[0]
            || lightInputs[0]
            || null;

        if (!targetInput) {
            scheduleRetry();
            return;
        }

        // Kit hosts upgrade asynchronously — before upgrade, focus() is a no-op
        // and the shadow input does not exist yet. Retry until it is real.
        if (isKitTextHost(targetInput) && !resolveNativeControl(targetInput)) {
            scheduleRetry();
            return;
        }

        if (readControlValue(targetInput).length > 0) {
            return;
        }

        targetInput.focus?.();

        const nativeControl = resolveNativeControl(targetInput);
        nativeControl?.setSelectionRange?.(0, 0);

        // pk-dialog.show() focuses the <dialog> in its own rAF, which can land AFTER
        // this call when opening + creating in a single action (e.g. adding a new
        // field auto-opens the editor). Verify focus stuck and re-assert if stolen.
        attempts += 1;
        if (attempts < maxAttempts) {
            window.setTimeout(() => {
                if (cancelled) {
                    return;
                }

                const deepActive = getDeepActiveElement();
                const focusHost = deepActive?.getRootNode?.()?.host ?? deepActive;

                if (deepActive !== targetInput && deepActive !== nativeControl && focusHost !== targetInput) {
                    run();
                }
            }, 60);
        }
    };

    window.requestAnimationFrame(run);

    return () => {
        cancelled = true;
        clearRetryTimeout();
    };
};
