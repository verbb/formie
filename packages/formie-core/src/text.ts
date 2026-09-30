export type TextLimitMetrics = {
    graphemeCount: number;
    wordCount: number;
};

type SegmenterLike = {
    segment(input: string): Iterable<unknown>;
};

type IntlWithSegmenter = typeof Intl & {
    Segmenter?: new(
        locales?: string | string[],
        options?: {
            granularity?: 'grapheme' | 'word' | 'sentence';
        },
    ) => SegmenterLike;
};

const graphemeSegmenter = (() => {
    if (typeof Intl === 'undefined') {
        return null;
    }

    const segmenterCtor = (Intl as IntlWithSegmenter).Segmenter;

    if (!segmenterCtor) {
        return null;
    }

    try {
        return new segmenterCtor(undefined, { granularity: 'grapheme' });
    } catch {
        return null;
    }
})();

const WORD_PATTERN = /[^\s\u0085]+/gu;
const GRAPHEME_PATTERN = (() => {
    try {
        return new RegExp('(?:\\p{Regional_Indicator}{2}|\\p{Extended_Pictographic}[\\p{Emoji_Modifier}\\p{M}\\u{E0020}-\\u{E007F}]*(?:\\u{200D}\\p{Extended_Pictographic}[\\p{Emoji_Modifier}\\p{M}\\u{E0020}-\\u{E007F}]*)*|[\\s\\S])\\p{M}*', 'gu');
    } catch {
        return null;
    }
})();

function stripTags(value: string): string {
    if (typeof DOMParser !== 'undefined') {
        const doc = new DOMParser().parseFromString(value, 'text/html');
        return doc.body.textContent || '';
    }

    return value.replace(/<[^>]*>/g, '');
}

function getPlainText(value: string): string {
    return stripTags(value);
}

export function normalizeText(value: string): string {
    return getPlainText(value).replace(/[\s\u0085]+/g, ' ').trim();
}

export function countGraphemes(value: string): number {
    if (graphemeSegmenter) {
        return Array.from(graphemeSegmenter.segment(value)).length;
    }

    return GRAPHEME_PATTERN ? value.match(GRAPHEME_PATTERN)?.length || 0 : Array.from(value).length;
}

export function getWordCount(value: string): number {
    return value.match(WORD_PATTERN)?.length || 0;
}

export function getTextLimitMetrics(value: string): TextLimitMetrics {
    const plainText = getPlainText(value);
    const normalizedText = normalizeText(value);

    return {
        graphemeCount: countGraphemes(plainText),
        wordCount: getWordCount(normalizedText),
    };
}
