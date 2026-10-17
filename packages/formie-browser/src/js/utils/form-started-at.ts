const FORM_STARTED_AT_STORAGE_PREFIX = 'formie:formStartedAt:';

export function ensureFormStartedAt(form: HTMLFormElement): void {
    const startedAtInput = form.querySelector('input[name="formStartedAt"]') as HTMLInputElement | null;

    if (!startedAtInput) {
        return;
    }

    const renderIdInput = form.querySelector('input[name="renderId"]') as HTMLInputElement | null;
    const renderId = renderIdInput?.value?.trim() ?? '';
    const storageKey = renderId ? `${FORM_STARTED_AT_STORAGE_PREFIX}${renderId}` : null;

    const continuing = ['submissionUid', 'progressId'].some((name) =>
        form.querySelector<HTMLInputElement>(`input[name="${name}"]`)?.value.trim()
    );

    // Reuse elapsed time for an existing draft or live mount, not a fresh visit.
    let startedAt = startedAtInput.value || (continuing && storageKey ? sessionStorage.getItem(storageKey) : null);

    if (!startedAt) {
        startedAt = String(Date.now());
    }

    if (storageKey) {
        sessionStorage.setItem(storageKey, startedAt);
    }

    startedAtInput.value = startedAt;
}

export function resetFormStartedAt(form: HTMLFormElement): void {
    const input = form.querySelector<HTMLInputElement>('input[name="formStartedAt"]');

    if (input) {
        // Hidden input values survive form.reset(), so start the next submission explicitly.
        input.value = String(Date.now());
        ensureFormStartedAt(form);
    }
}
