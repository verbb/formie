import type { FieldReferenceTransform, ParsedFieldReference } from '#utils/field-references.types';
import { normalizeFieldKey } from '#utils/field-references.keys';

function parseTransforms(body: string): { source: string; transforms: FieldReferenceTransform[] } {
    const parts = body.split(';').map((part) => {
        return part.trim();
    }).filter(Boolean);

    if (!parts.length) {
        return {
            source: '',
            transforms: [],
        };
    }

    const [source, ...metadata] = parts;
    const transforms: FieldReferenceTransform[] = [];
    let current: FieldReferenceTransform | null = null;

    metadata.forEach((entry) => {
        if (entry.startsWith('transform=')) {
            if (current) {
                transforms.push(current);
            }

            current = {
                id: decodeURIComponent(entry.slice('transform='.length) || '').trim(),
                params: {},
            };
            return;
        }

        if (!current || !entry.includes('=')) {
            return;
        }

        const [rawKey, rawValue] = entry.split('=', 2);
        const key = (rawKey || '').trim();

        if (!key || key === 'transform') {
            return;
        }

        current.params[key] = decodeURIComponent(rawValue || '').trim();
    });

    if (current) {
        transforms.push(current);
    }

    return {
        source: source || '',
        transforms,
    };
}

export function parseFieldReference(rawValue: string): ParsedFieldReference {
    const raw = String(rawValue || '').trim();

    if (!raw) {
        return {
            raw,
            target: '',
            key: '',
            selector: '',
            defaultValue: '',
            transforms: [],
            isToken: false,
            isValid: false,
        };
    }

    const tokenMatch = raw.match(/^\{([a-zA-Z]+)(?::(.*))?\}$/);

    if (!tokenMatch) {
        return {
            raw,
            target: '',
            key: normalizeFieldKey(raw),
            selector: '',
            defaultValue: '',
            transforms: [],
            isToken: false,
            isValid: true,
        };
    }

    const targetRaw = (tokenMatch[1] || '').trim().toLowerCase();
    const bodyRaw = (tokenMatch[2] || '').trim();
    const [beforeDefault, defaultRaw = ''] = bodyRaw.split('|', 2);
    const { source, transforms } = parseTransforms(beforeDefault || '');

    if (targetRaw !== 'field') {
        return {
            raw,
            target: '',
            key: '',
            selector: '',
            defaultValue: defaultRaw.trim(),
            transforms,
            isToken: true,
            isValid: false,
        };
    }

    const separatorIndex = source.indexOf(':');
    const keyRaw = separatorIndex === -1 ? source : source.slice(0, separatorIndex);
    const selectorRaw = separatorIndex === -1 ? '' : source.slice(separatorIndex + 1);
    const key = normalizeFieldKey(keyRaw);

    return {
        raw,
        target: 'field',
        key,
        selector: selectorRaw.trim(),
        defaultValue: defaultRaw.trim(),
        transforms,
        isToken: true,
        isValid: key !== '',
    };
}
