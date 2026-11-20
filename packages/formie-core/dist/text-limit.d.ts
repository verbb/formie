import type { ClientFieldDefinition, ClientFormDefinition } from './types.js';
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
export declare function fieldTextLimit(field: ClientFieldDefinition): ClientTextLimit | null;
/**
 * Formats the `{count, plural, ...}` messages the server translates, choosing the branch for the count the way Craft
 * does (`=N` first, then the locale's plural category, then `other`).
 */
export declare function formatPluralMessage(message: string, count: number, locale?: string): string;
/**
 * The counter shown under a text field with a maximum, matching server-rendered forms. It counts the limit's unit,
 * ignoring rich text markup, and returns null when the field has no maximum.
 */
export declare function textLimitCounter(field: ClientFieldDefinition, value: unknown, definition?: Pick<ClientFormDefinition, 'settings'> & Partial<Pick<ClientFormDefinition, 'locale'>> | null): TextLimitCounter | null;
//# sourceMappingURL=text-limit.d.ts.map