import { isRichTextEmpty } from '@verbb/plugin-kit-react/utils';

/**
 * Normalize stored rich-text values into TipTap schema content arrays.
 * Mirrors Formie PHP `RichText::from()` for client-side boundaries (builder previews/editors).
 */
export const normalizeRichTextValue = (value) => {
    if (value == null || value === '') {
        return [];
    }

    if (Array.isArray(value)) {
        return value;
    }

    if (typeof value === 'object') {
        if (value.type === 'doc' && Array.isArray(value.content)) {
            return value.content;
        }

        if (value.type) {
            return [value];
        }
    }

    if (typeof value === 'string') {
        try {
            const parsed = JSON.parse(value);

            return normalizeRichTextValue(parsed);
        } catch {
            const text = value.trim();

            if (!text) {
                return [];
            }

            return [{
                type: 'paragraph',
                content: [{ type: 'text', text: value }],
            }];
        }
    }

    return [];
};

export const normalizeFieldInstructions = (field) => {
    if (!field || !Object.prototype.hasOwnProperty.call(field, 'instructions')) {
        return field;
    }

    return {
        ...field,
        instructions: normalizeRichTextValue(field.instructions),
    };
};

export const normalizeFieldEditorValues = (field) => {
    if (!field) {
        return field;
    }

    let next = normalizeFieldInstructions(field);

    if (Object.prototype.hasOwnProperty.call(field, 'builderNote')) {
        next = {
            ...next,
            builderNote: normalizeRichTextValue(field.builderNote),
        };
    }

    return next;
};

export const hasRichTextValue = (value) => {
    return !isRichTextEmpty(value);
};
