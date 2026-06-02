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

    const run = () => {
        if (cancelled || !root) {
            return;
        }

        const labelCandidates = Array.from(
            root.querySelectorAll('input[name="label"]:not([disabled]):not([readonly]), input[name$=".label"]:not([disabled]):not([readonly]), textarea[name="label"]:not([disabled]):not([readonly]), textarea[name$=".label"]:not([disabled]):not([readonly])'),
        ).filter((input) => {
            return isVisibleElement(input) && isTextEntryControl(input);
        });
        const inputCandidates = Array.from(
            root.querySelectorAll('input:not([type="hidden"]):not([disabled]):not([readonly]), textarea:not([disabled]):not([readonly])'),
        ).filter((input) => {
            return isVisibleElement(input) && isTextEntryControl(input);
        });
        const targetInput = labelCandidates[0] || inputCandidates[0];

        if (!targetInput) {
            attempts += 1;

            if (attempts < maxAttempts) {
                retryTimeout = window.setTimeout(run, retryDelayMs);
            }

            return;
        }

        if ((targetInput.value || '').length > 0) {
            return;
        }

        targetInput.focus?.();

        window.requestAnimationFrame(() => {
            if (cancelled) {
                return;
            }

            if (document.activeElement !== targetInput) {
                targetInput.focus?.();
            }

            targetInput.setSelectionRange?.(0, 0);
        });
    };

    window.requestAnimationFrame(run);

    return () => {
        cancelled = true;
        clearRetryTimeout();
    };
};
