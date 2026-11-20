import { describe, expect, it } from 'vitest';

import { formatPluralMessage, textLimitCounter } from './text-limit.js';
import type { ClientFieldDefinition } from './types.js';
import { validateBrowserValue } from './validation.js';

function textField(textLimit: Record<string, unknown> | null): ClientFieldDefinition {
    return { id: 'f', handle: 'notes', type: 'single-line-text', label: 'Notes', input: { textLimit } } as unknown as ClientFieldDefinition;
}

describe('text limits', () => {
    it('formats plural messages the way the server-rendered counter does', () => {
        const message = '{count, plural, one{character left} other{characters left}}';

        expect(formatPluralMessage(message, 1, 'en')).toBe('character left');
        expect(formatPluralMessage(message, 4, 'en')).toBe('characters left');
        expect(formatPluralMessage('{count, plural, =0{none} other{# left}} now', 0)).toBe('none now');
        expect(formatPluralMessage('{count, plural, =0{none} other{# left}} now', 3)).toBe('3 left now');
    });

    it('counts down to the maximum and past it, in characters or words', () => {
        const characters = textField({ max: 5, maxType: 'characters' });
        const words = textField({ max: 2, maxType: 'words' });

        expect(textLimitCounter(characters, '')).toEqual({ count: 5, text: 'characters allowed', over: false });
        expect(textLimitCounter(characters, 'abcd')).toEqual({ count: 1, text: 'character left', over: false });
        expect(textLimitCounter(characters, 'abcdefg')).toEqual({ count: 2, text: 'characters over limit', over: true });
        expect(textLimitCounter(words, 'one two three')).toEqual({ count: 1, text: 'word over limit', over: true });
        expect(textLimitCounter(words, '<p>one</p>')).toEqual({ count: 1, text: 'word left', over: false });
        expect(textLimitCounter(textField({ min: 2, minType: 'words' }), 'x')).toBeNull();
        expect(textLimitCounter(textField(null), 'x')).toBeNull();
    });

    it('uses the translated counter strings from the definition', () => {
        const definition = { settings: { strings: { textLimitCharactersLeft: '{count, plural, one{Zeichen übrig} other{Zeichen übrig}}' } } } as never;

        expect(textLimitCounter(textField({ max: 5, maxType: 'characters' }), 'ab', definition)?.text).toBe('Zeichen übrig');
    });

    it('checks minimum and maximum characters and words with the server messages', () => {
        const rule = { type: 'textLimit', min: 3, minType: 'characters', max: 2, maxType: 'words', messages: { minCharacters: 'Too short', maxWords: 'Too many words' } };

        expect(validateBrowserValue('ab', rule)).toBe('Too short');
        expect(validateBrowserValue('abc', rule)).toBeNull();
        expect(validateBrowserValue('one two three', rule)).toBe('Too many words');
        expect(validateBrowserValue('<p>one</p> <p>two</p>', rule)).toBeNull();
        expect(validateBrowserValue('', rule)).toBeNull();
        expect(validateBrowserValue('ab', { type: 'textLimit', min: 3, minType: 'characters', max: null, maxType: 'characters' }, { label: 'Notes' })).toBe('Notes must be no less than 3 characters.');
    });
});
