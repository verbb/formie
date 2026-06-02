export type TranslationReplacements = Record<string, string | number>;

type FormieWindow = Window & {
    FormieTranslations?: Record<string, string>;
};

function getWindowTranslationStore(): Record<string, string> {
    return (window as FormieWindow).FormieTranslations || {};
}

function hydrateTranslationsFromDom(): void {
    if (typeof document === 'undefined') {
        return;
    }

    const scripts = Array.from(document.querySelectorAll<HTMLScriptElement>('script[type="application/json"][data-formie-translations]:not([data-formie-translations-loaded="true"])'));

    if (scripts.length === 0) {
        return;
    }

    let nextStore: Record<string, string> | null = null;

    for (const script of scripts) {
        // Mark each seed as consumed so repeated reads stay cheap and predictable.
        script.dataset.formieTranslationsLoaded = 'true';

        const payload = script.textContent?.trim();

        if (!payload) {
            continue;
        }

        try {
            const translations = JSON.parse(payload);

            if (!translations || Array.isArray(translations) || typeof translations !== 'object') {
                continue;
            }

            nextStore = {
                ...(nextStore ?? getWindowTranslationStore()),
                ...(translations as Record<string, string>),
            };
        } catch {
            continue;
        }
    }

    if (nextStore) {
        (window as FormieWindow).FormieTranslations = nextStore;
    }
}

function getTranslationStore(): Record<string, string> {
    hydrateTranslationsFromDom();

    return getWindowTranslationStore();
}

export function getFormieTranslations(): Record<string, string> {
    return { ...getTranslationStore() };
}

export function setFormieTranslations(translations: Record<string, string>): Record<string, string> {
    (window as FormieWindow).FormieTranslations = { ...translations };

    return getFormieTranslations();
}

export function mergeFormieTranslations(translations: Record<string, string>): Record<string, string> {
    // Merge lets SSR/server-rendered code seed defaults while later consumers layer in
    // feature- or locale-specific strings without wiping the existing store.
    (window as FormieWindow).FormieTranslations = {
        ...getTranslationStore(),
        ...translations,
    };

    return getFormieTranslations();
}

export function t(message: string, replacements: TranslationReplacements = {}): string {
    let output = getTranslationStore()[message] || message;

    // Keep replacement syntax intentionally small and predictable because these
    // messages are often shared across PHP-rendered and JS-rendered validation UI.
    output = output.replace(/{([a-zA-Z0-9]+)}/g, (match, key) => {
        if (Object.prototype.hasOwnProperty.call(replacements, key)) {
            return String(replacements[key]);
        }

        return match;
    });

    return output;
}

export const translate = t;
