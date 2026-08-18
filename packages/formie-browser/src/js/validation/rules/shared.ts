import type { ValidationContext, ValidationInput } from '#validation/types';
import { isValidationSkipped } from '#validation/skip';

export function stripTags(value: string): string {
    const doc = new DOMParser().parseFromString(value, 'text/html');
    return doc.body.textContent || '';
}

export function countGraphemes(value: string): number {
    // Validation rules share grapheme counting with the field module path so
    // visible counters and submit-time validation agree on what "1 character" means.
    const unicodeRegExp = /(?:\p{Extended_Pictographic}[\p{Emoji_Modifier}\p{M}]*(?:\p{Join_Control}\p{Extended_Pictographic}[\p{Emoji_Modifier}\p{M}]*)*|\s|.)\p{M}*/gu;
    const graphemes = value.match(unicodeRegExp) || [];

    return graphemes.length;
}

export function getWordCount(value: string): number {
    return stripTags(value).split(/\S+/).length - 1;
}

export function getLabelText(field: HTMLElement | null): string {
    return field?.querySelector('[data-formie-field-label]')?.childNodes[0]?.textContent?.trim() || '';
}

export function getComparableInput(ctx: ValidationContext): ValidationInput | null {
    const match = ctx.getRule('match');

    if (!match || match === true || typeof match !== 'object' || !ctx.field) {
        return null;
    }

    const fieldHandle = typeof match.fieldHandle === 'string' ? match.fieldHandle.trim() : '';

    if (!fieldHandle) {
        return null;
    }

    // Match validation resolves by field handle, not raw input name, so it stays
    // aligned with the DOM contract used throughout the rest of the browser client.
    const sourceField = ctx.form.querySelector(`[data-formie-field-handle="${fieldHandle}"]`) as HTMLElement | null;

    if (!sourceField) {
        return null;
    }

    return Array.from(sourceField.querySelectorAll(ctx.config.fieldsSelector)).find((node): node is ValidationInput => {
        return (node instanceof HTMLInputElement || node instanceof HTMLSelectElement || node instanceof HTMLTextAreaElement)
            && !isValidationSkipped(node);
    }) ?? null;
}
