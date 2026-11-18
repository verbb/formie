import { resolveClientRendererType, type ClientFieldDefinition } from '@verbb/formie-core';

export function isFieldDefinition(candidate: unknown): candidate is ClientFieldDefinition {
    return !!candidate && typeof candidate === 'object' && 'id' in candidate && 'handle' in candidate && 'type' in candidate;
}

export function resolveFieldRendererType(field: ClientFieldDefinition): ClientFieldDefinition['type'] {
    return resolveClientRendererType(field);
}
