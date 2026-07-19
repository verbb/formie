import { generateHTML } from '@tiptap/core';
import Document from '@tiptap/extension-document';
import Paragraph from '@tiptap/extension-paragraph';
import Text from '@tiptap/extension-text';

const INVISIBLE_CHAR_PATTERN = /[\u200B\u200C\u200D\u2060\uFEFF]/g;

const normalizeRichTextNodes = (value) => {
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
            return normalizeRichTextNodes(JSON.parse(value));
        } catch {
            const text = value.replace(INVISIBLE_CHAR_PATTERN, '').trim();

            return text
                ? [{ type: 'paragraph', content: [{ type: 'text', text: value }] }]
                : [];
        }
    }

    return [];
};

const collectRichTextPlainText = (nodes) => {
    let text = '';

    const visit = (node) => {
        if (!node || typeof node !== 'object') {
            return;
        }

        if (node.type === 'text' && typeof node.text === 'string') {
            text += node.text.replace(INVISIBLE_CHAR_PATTERN, '');
            return;
        }

        if (node.type === 'variableTag') {
            const label = typeof node.attrs?.label === 'string' ? node.attrs.label : '';
            const variableValue = typeof node.attrs?.value === 'string' ? node.attrs.value : '';
            text += (label || variableValue).replace(INVISIBLE_CHAR_PATTERN, '');
            return;
        }

        if (Array.isArray(node.content)) {
            node.content.forEach(visit);
        }
    };

    nodes.forEach(visit);

    return text.trim();
};

export const getRichTextHtml = (json) => {
    if (!json) {
        return '';
    }

    if (typeof json === 'string') {
        try {
            json = JSON.parse(json);
        } catch {
            return json;
        }
    }

    return generateHTML({
        type: 'doc',
        content: json,
    }, [
        Document,
        Paragraph,
        Text,
    ]);
};

export const getRichTextText = (json) => {
    if (!json) {
        return '';
    }

    const html = getRichTextHtml(json);

    return html
        .replace(/<[^>]*>/g, ' ')
        .replace(/&nbsp;/g, ' ')
        .replace(/&amp;/g, '&')
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>')
        .replace(/&quot;/g, '"')
        .replace(/&#39;/g, "'")
        .replace(/\s+/g, ' ')
        .trim();
};

export const isRichTextEmpty = (value) => {
    const nodes = normalizeRichTextNodes(value);

    if (!nodes.length) {
        return true;
    }

    return collectRichTextPlainText(nodes).length === 0;
};
