import { afterEach, describe, expect, it, vi } from 'vitest';
import { countGraphemes, getTextLimitMetrics, getWordCount, normalizeText } from './text';

describe('text limit metrics', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('counts words as non-whitespace runs', () => {
        const value = 'H₂O₂, 1,023 (the comma splits it) and factor–circadian splits it.';

        expect(getWordCount(value)).toBe(10);
        expect(getWordCount(`one\u00A0two\u0085three`)).toBe(3);
        expect(normalizeText(` one\u00A0\ttwo\u0085three `)).toBe('one two three');
    });

    it('counts extended grapheme clusters', () => {
        expect(countGraphemes('e\u0301')).toBe(1);
        expect(countGraphemes('👨‍👩‍👧‍👦')).toBe(1);
        expect(countGraphemes('🇦🇺')).toBe(1);
        expect(countGraphemes('❤️‍🔥')).toBe(1);
        expect(countGraphemes('🙂‍↔️')).toBe(1);
    });

    it('retains emoji-aware counting without Intl.Segmenter', async () => {
        vi.stubGlobal('Intl', { Segmenter: undefined });
        vi.resetModules();

        const { countGraphemes: countWithFallback } = await import('./text');

        expect(countWithFallback('👨‍👩‍👧‍👦')).toBe(1);
        expect(countWithFallback('🇦🇺')).toBe(1);
        expect(countWithFallback('🙂‍↔️')).toBe(1);
    });

    it('uses the same plain-text value for both limits', () => {
        expect(getTextLimitMetrics('<strong>one</strong> two 👩🏽‍💻')).toEqual({
            graphemeCount: 9,
            wordCount: 3,
        });
    });
});
