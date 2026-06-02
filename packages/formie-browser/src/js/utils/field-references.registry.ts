import { inputNameToFieldKey } from '#utils/field-references.keys';
import type { FieldValueInput, FieldValueRegistry, FieldValueRegistryEntry } from '#utils/field-references.types';

function isFieldValueInput(node: Element): node is FieldValueInput {
    return node instanceof HTMLInputElement || node instanceof HTMLTextAreaElement || node instanceof HTMLSelectElement;
}

function addInputEntry(registry: FieldValueRegistry, key: string, input: FieldValueInput): void {
    const normalizedKey = key.trim();
    const name = String(input.name || '').trim();

    if (!normalizedKey || !name) {
        return;
    }

    const entry = registry.get(normalizedKey) || {
        key: normalizedKey,
        names: [],
        inputs: [],
    };

    if (!entry.names.includes(name)) {
        entry.names.push(name);
    }

    if (!entry.inputs.includes(input)) {
        entry.inputs.push(input);
    }

    registry.set(normalizedKey, entry);
}

export function buildFieldValueRegistry(root: ParentNode): FieldValueRegistry {
    const registry: FieldValueRegistry = new Map<string, FieldValueRegistryEntry>();
    const inputs = Array.from(root.querySelectorAll('[name]')).filter((node): node is FieldValueInput => {
        return isFieldValueInput(node);
    });

    inputs.forEach((input) => {
        const key = inputNameToFieldKey(input.name);

        if (!key) {
            return;
        }

        addInputEntry(registry, key, input);
    });

    return registry;
}
