import { parseReference, resolveReference } from '@verbb/formie-core';
import { fieldKeyToInputName, normalizeFieldKey } from '#utils/field-references.keys';
import { parseFieldReference } from '#utils/field-references.parser';
import type {
    FieldValueInput,
    FieldValueRegistry,
    ResolveFieldValueResult,
} from '#utils/field-references.types';

function readInputsValue(inputs: FieldValueInput[]): string | string[] {
    if (!inputs.length) {
        return '';
    }

    const first = inputs[0];

    if (first instanceof HTMLSelectElement && first.multiple) {
        return Array.from(first.selectedOptions).map((option) => {
            return option.value;
        });
    }

    const hasCheckboxOrRadio = inputs.some((input) => {
        return input instanceof HTMLInputElement && (input.type === 'checkbox' || input.type === 'radio');
    });

    if (hasCheckboxOrRadio) {
        const selected = inputs.flatMap((input) => {
            if (!(input instanceof HTMLInputElement) || !input.checked) {
                return [];
            }

            return [input.value];
        });

        return selected.length > 1 ? selected : (selected[0] || '');
    }

    return first.value;
}

function getEntry(registry: FieldValueRegistry, key: string) {
    return registry.get(normalizeFieldKey(key)) || null;
}

function resolvedProjection(reference: string, key: string, value: string | string[]): ResolveFieldValueResult {
    const token = reference.trim().startsWith('{') ? reference : `{field:${encodeURIComponent(key)}}`;
    const expression = parseReference(token);
    const id = `field:${expression.identifier}`;
    const valueId = expression.selector ? `${id}:${expression.selector}` : id;
    const resolved = resolveReference(token, {
        definitions: { [id]: { id, selectors: expression.selector ? [expression.selector] : [], availability: { server: true, browser: true } } },
        values: { [valueId]: value },
    });
    return { key, value: resolved.diagnostic ? '' : resolved.value as string | string[], found: !resolved.diagnostic, diagnostic: resolved.diagnostic };
}

export function resolveFieldReferenceLive(reference: string, registry: FieldValueRegistry): ResolveFieldValueResult {
    const parsed = parseFieldReference(reference);
    const key = parsed.key;
    const lookup = parsed.selector ? `${key}.${parsed.selector.replace(/:/g, '.')}` : key;
    const entry = parsed.isValid ? getEntry(registry, lookup) : null;
    if (!entry) return { key, value: '', found: false, diagnostic: !parsed.isValid ? 'invalidExpression' : parsed.selector ? 'invalidSelector' : 'missingField' };
    return resolvedProjection(reference, key, readInputsValue(entry.inputs));
}

export function resolveFieldReferenceFromFormData(reference: string, formData: FormData, registry?: FieldValueRegistry): ResolveFieldValueResult {
    const parsed = parseFieldReference(reference);
    const key = parsed.key;
    const lookup = parsed.selector ? `${key}.${parsed.selector.replace(/:/g, '.')}` : key;
    const entry = registry ? getEntry(registry, lookup) : null;
    if (!parsed.isValid || (registry && !entry)) return { key, value: '', found: false, diagnostic: parsed.isValid ? 'missingField' : 'invalidExpression' };
    const names = entry?.names?.length ? entry.names : [fieldKeyToInputName(lookup)];
    const present = !!entry || names.some((name) => formData.has(name) || formData.has(`${name}[]`));
    if (!present) return { key, value: '', found: false, diagnostic: 'missingField' };
    const values = names.flatMap((name) => [...formData.getAll(name), ...formData.getAll(`${name}[]`)]).map((value) => String(value ?? ''));
    return resolvedProjection(reference, key, values.length > 1 ? values : values[0] ?? '');
}
