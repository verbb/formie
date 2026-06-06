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

function parsePluralBranches(body: string): Record<string, string> {
    const branches: Record<string, string> = {};
    let index = 0;

    while (index < body.length) {
        while (index < body.length && /\s/.test(body[index])) {
            index++;
        }

        if (index >= body.length) {
            break;
        }

        const branchMatch = body.slice(index).match(/^(\w+|=\d+)\{/);

        if (!branchMatch) {
            break;
        }

        const key = branchMatch[1];
        index += branchMatch[0].length;

        let depth = 1;
        const contentStart = index;

        while (index < body.length && depth > 0) {
            if (body[index] === '{') {
                depth++;
            } else if (body[index] === '}') {
                depth--;
            }

            if (depth > 0) {
                index++;
            }
        }

        branches[key] = body.slice(contentStart, index);
        index++;
    }

    return branches;
}

function selectPluralBranch(count: number, branches: Record<string, string>): string {
    const exactKey = `=${count}`;

    if (Object.prototype.hasOwnProperty.call(branches, exactKey)) {
        return branches[exactKey];
    }

    if (typeof Intl !== 'undefined' && typeof Intl.PluralRules === 'function') {
        const category = new Intl.PluralRules().select(count);

        if (Object.prototype.hasOwnProperty.call(branches, category)) {
            return branches[category];
        }
    }

    if (count === 1 && Object.prototype.hasOwnProperty.call(branches, 'one')) {
        return branches.one;
    }

    if (Object.prototype.hasOwnProperty.call(branches, 'other')) {
        return branches.other;
    }

    const firstKey = Object.keys(branches)[0];

    return firstKey ? branches[firstKey] : '';
}

function parsePluralExpression(message: string, startIndex: number): { param: string; body: string; endIndex: number } | null {
    const headerMatch = message.slice(startIndex).match(/^\{(\w+),\s*plural,\s*/);

    if (!headerMatch) {
        return null;
    }

    const param = headerMatch[1];
    const bodyStart = startIndex + headerMatch[0].length;
    let index = bodyStart;

    while (index < message.length) {
        while (index < message.length && /\s/.test(message[index])) {
            index++;
        }

        if (index >= message.length || message[index] === '}') {
            break;
        }

        const branchMatch = message.slice(index).match(/^(\w+|=\d+)\{/);

        if (!branchMatch) {
            return null;
        }

        index += branchMatch[0].length;

        let depth = 1;

        while (index < message.length && depth > 0) {
            if (message[index] === '{') {
                depth++;
            } else if (message[index] === '}') {
                depth--;
            }

            if (depth > 0) {
                index++;
            }
        }

        index++;
    }

    if (index >= message.length || message[index] !== '}') {
        return null;
    }

    return {
        param,
        body: message.slice(bodyStart, index),
        endIndex: index,
    };
}

function formatPluralBlocks(message: string, replacements: TranslationReplacements): string {
    let output = '';
    let index = 0;

    while (index < message.length) {
        if (message[index] !== '{') {
            output += message[index];
            index++;
            continue;
        }

        const parsed = parsePluralExpression(message, index);

        if (!parsed) {
            output += message[index];
            index++;
            continue;
        }

        const rawValue = replacements[parsed.param];
        const count = typeof rawValue === 'number'
            ? rawValue
            : Number.parseInt(String(rawValue ?? ''), 10) || 0;
        const branches = parsePluralBranches(parsed.body);
        let selected = selectPluralBranch(count, branches);

        selected = selected.replace(/#/g, String(count));

        output += selected;
        index = parsed.endIndex + 1;
    }

    return output;
}

function formatNumberBlocks(message: string, replacements: TranslationReplacements): string {
    return message.replace(/\{(\w+),\s*number\}/g, (match, key) => {
        if (!Object.prototype.hasOwnProperty.call(replacements, key)) {
            return match;
        }

        const value = replacements[key];

        return typeof value === 'number' ? value.toLocaleString() : String(value);
    });
}

function replaceSimpleTokens(message: string, replacements: TranslationReplacements): string {
    return message.replace(/\{(\w+)\}/g, (match, key) => {
        if (Object.prototype.hasOwnProperty.call(replacements, key)) {
            return String(replacements[key]);
        }

        return match;
    });
}

export function t(message: string, replacements: TranslationReplacements = {}): string {
    let output = getTranslationStore()[message] || message;

    output = formatPluralBlocks(output, replacements);
    output = formatNumberBlocks(output, replacements);
    output = replaceSimpleTokens(output, replacements);

    return output;
}

export const translate = t;
