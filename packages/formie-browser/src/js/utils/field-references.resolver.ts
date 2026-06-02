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

export function resolveFieldReferenceLive(
    reference: string,
    registry: FieldValueRegistry,
): ResolveFieldValueResult {
    const parsed = parseFieldReference(reference);
    const key = parsed.key;
    const entry = getEntry(registry, key);

    if (!entry) {
        return {
            key,
            value: parsed.defaultValue,
            found: false,
        };
    }

    const value = readInputsValue(entry.inputs);

    return {
        key,
        value: value === '' && parsed.defaultValue !== '' ? parsed.defaultValue : value,
        found: true,
    };
}

export function resolveFieldReferenceFromFormData(
    reference: string,
    formData: FormData,
    registry?: FieldValueRegistry,
): ResolveFieldValueResult {
    const parsed = parseFieldReference(reference);
    const key = parsed.key;

    if (!key) {
        return {
            key,
            value: parsed.defaultValue,
            found: false,
        };
    }

    const entry = registry ? getEntry(registry, key) : null;
    const names = entry?.names?.length ? entry.names : [fieldKeyToInputName(key)];
    const values = names.flatMap((name) => {
        const collected = formData.getAll(name).map((value) => {
            return String(value ?? '');
        });

        if (collected.length) {
            return collected;
        }

        return formData.getAll(`${name}[]`).map((value) => {
            return String(value ?? '');
        });
    }).filter((value) => value !== '');

    if (!values.length) {
        return {
            key,
            value: parsed.defaultValue,
            found: false,
        };
    }

    return {
        key,
        value: values.length > 1 ? values : values[0],
        found: true,
    };
}
