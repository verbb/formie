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
    const segmenterCtor = (Intl as IntlWithSegmenter).Segmenter;

    return segmenterCtor
        ? new segmenterCtor(undefined, { granularity: 'grapheme' })
        : null;
})();

const WORD_PATTERN = /[\p{L}\p{N}\p{M}]+(?:['’._-][\p{L}\p{N}\p{M}]+)*/gu;

function stripTags(value: string): string {
    if (typeof DOMParser !== 'undefined') {
        const doc = new DOMParser().parseFromString(value, 'text/html');
        return doc.body.textContent || '';
    }

    return value.replace(/<[^>]*>/g, ' ');
}

function getPlainText(value: string): string {
    return stripTags(value);
}

export function normalizeText(value: string): string {
    return getPlainText(value).replace(/[\s\t\n\r]+/g, ' ').trim();
}

export function countGraphemes(value: string): number {
    if (graphemeSegmenter) {
        return Array.from(graphemeSegmenter.segment(value)).length;
    }

    return Array.from(value).length;
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

