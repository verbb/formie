import { isKnownFrontendFieldType, type FrontendFieldDefinition } from '@verbb/formie-core';

export function isFieldDefinition(candidate: unknown): candidate is FrontendFieldDefinition {
    return !!candidate && typeof candidate === 'object' && 'id' in candidate && 'handle' in candidate && 'type' in candidate;
}

export function resolveFieldRendererType(field: FrontendFieldDefinition): FrontendFieldDefinition['type'] {
    if (isKnownFrontendFieldType(field.type)) {
        return field.type;
    }

    const input = field.input && typeof field.input === 'object' ? field.input as Record<string, unknown> : {};
    const fieldKind = typeof input.fieldKind === 'string' ? input.fieldKind : null;

    if (fieldKind === 'text') {
        return 'single-line-text';
    }

    if (fieldKind === 'textarea') {
        return 'multi-line-text';
    }

    if (fieldKind === 'boolean') {
        return 'agree';
    }

    if (fieldKind === 'file') {
        return 'file';
    }

    return field.type;
}
