import { getClientString } from './accessibility.js';
import { getTextLimitMetrics } from './text.js';
import type { ClientFieldDefinition, ClientFormDefinition, ClientStringKey } from './types.js';

export type ClientTextLimit = {
    min: number | null;
    minType: 'characters' | 'words';
    max: number | null;
    maxType: 'characters' | 'words';
};

export type TextLimitCounter = {
    /** The number shown before the text: the limit while empty, then what's left, then how far over. */
    count: number;
    text: string;
    over: boolean;
};

export function fieldTextLimit(field: ClientFieldDefinition): ClientTextLimit | null {
    const limit = field.input.textLimit;

    if (!limit || typeof limit !== 'object') {
        return null;
    }

    const settings = limit as Record<string, unknown>;
    const bound = (value: unknown) => (typeof value === 'number' && value > 0 ? value : null);

    return {
        min: bound(settings.min),
        minType: settings.minType === 'words' ? 'words' : 'characters',
        max: bound(settings.max),
        maxType: settings.maxType === 'words' ? 'words' : 'characters',
    };
}

/**
 * Formats the `{count, plural, ...}` messages the server translates, choosing the branch for the count the way Craft
 * does (`=N` first, then the locale's plural category, then `other`).
 */
export function formatPluralMessage(message: string, count: number, locale?: string): string {
    let output = '';
    let index = 0;

    while (index < message.length) {
        const header = message[index] === '{' ? message.slice(index).match(/^\{\w+,\s*plural,\s*/) : null;

        if (!header) {
            output += message[index];
            index++;
            continue;
        }

        const branches: Record<string, string> = {};
        index += header[0].length;

        while (index < message.length && message[index] !== '}') {
            const branch = message.slice(index).match(/^\s*(\w+|=\d+)\s*\{/);

            if (!branch) {
                break;
            }

            index += branch[0].length;
            const start = index;
            let depth = 1;

            while (index < message.length && depth > 0) {
                depth += message[index] === '{' ? 1 : message[index] === '}' ? -1 : 0;
                index++;
            }

            branches[branch[1]] = message.slice(start, index - 1);
            index += message.slice(index).match(/^\s*/)?.[0].length ?? 0;
        }

        index++;
        let category = 'other';

        try {
            category = new Intl.PluralRules(locale).select(count);
        } catch {
            category = count === 1 ? 'one' : 'other';
        }

        const selected = branches[`=${count}`] ?? branches[category] ?? branches.other ?? '';
        output += selected.replace(/#/g, String(count));
    }

    return output;
}

/**
 * The counter shown under a text field with a maximum, matching server-rendered forms. It counts the limit's unit,
 * ignoring rich text markup, and returns null when the field has no maximum.
 */
export function textLimitCounter(
    field: ClientFieldDefinition,
    value: unknown,
    definition?: Pick<ClientFormDefinition, 'settings'> & Partial<Pick<ClientFormDefinition, 'locale'>> | null,
): TextLimitCounter | null {
    const limit = fieldTextLimit(field);

    if (!limit?.max) {
        return null;
    }

    const text = typeof value === 'string' ? value : '';
    const words = limit.maxType === 'words';
    const metrics = getTextLimitMetrics(text);
    const remaining = limit.max - (words ? metrics.wordCount : metrics.graphemeCount);
    const empty = words ? text.trim() === '' : text === '';
    const state = empty ? 'Allowed' : remaining < 0 ? 'Over' : 'Left';
    const count = state === 'Allowed' ? limit.max : Math.abs(remaining);
    const key = `textLimit${words ? 'Words' : 'Characters'}${state}` as ClientStringKey;
    const locale = definition?.locale ?? undefined;

    return { count, text: formatPluralMessage(getClientString(definition, key), count, locale), over: state === 'Over' };
}
