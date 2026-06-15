const FORM_STARTED_AT_STORAGE_PREFIX = 'formie:formStartedAt:';

export function ensureFormStartedAt(form: HTMLFormElement): void {
    const startedAtInput = form.querySelector('input[name="formStartedAt"]') as HTMLInputElement | null;

    if (!startedAtInput) {
        return;
    }

    const renderIdInput = form.querySelector('input[name="renderId"]') as HTMLInputElement | null;
    const renderId = renderIdInput?.value?.trim() ?? '';
    const storageKey = renderId ? `${FORM_STARTED_AT_STORAGE_PREFIX}${renderId}` : null;

    let startedAt = storageKey ? sessionStorage.getItem(storageKey) : null;

    if (!startedAt) {
        startedAt = String(Date.now());

        if (storageKey) {
            sessionStorage.setItem(storageKey, startedAt);
        }
    }

    startedAtInput.value = startedAt;
}
