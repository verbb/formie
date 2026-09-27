import { selectConditionRows } from '@verbb/formie-core';
import type { ConditionInput, ConditionSource } from '#modules/fields/conditions/types';

function getInputKey(input: ConditionInput, index: number): string {
    return input.name || `__condition_input_${index}`;
}

function getInputLabel(input: HTMLInputElement): string {
    const explicitLabel = input.id
        ? input.ownerDocument.querySelector(`label[for="${input.id}"]`)?.textContent?.trim()
        : '';

    if (explicitLabel) {
        return explicitLabel;
    }

    return input.closest('label')?.textContent?.trim() || '';
}

function readInputGroupValues(inputs: ConditionInput[], selector = ''): string[] {
    const firstInput = inputs[0];

    if (!firstInput) {
        return [];
    }

    if (firstInput instanceof HTMLInputElement) {
        if (firstInput.type === 'checkbox') {
            const checkedInputs = inputs.filter((input): input is HTMLInputElement => {
                return input instanceof HTMLInputElement && input.checked;
            });

            if (selector === 'label') {
                return checkedInputs.map((input) => {
                    return getInputLabel(input);
                }).filter(Boolean);
            }

            return checkedInputs.map((input) => {
                return input.value;
            });
        }

        if (firstInput.type === 'radio') {
            const checkedInputs = inputs.filter((input): input is HTMLInputElement => {
                return input instanceof HTMLInputElement && input.checked;
            });

            if (selector === 'label') {
                return checkedInputs.map((input) => {
                    return getInputLabel(input);
                }).filter(Boolean);
            }

            return checkedInputs.map((input) => {
                return input.value;
            });
        }

        if (firstInput.type === 'file') {
            return Array.from(firstInput.files || []).map((file) => {
                return file.name;
            });
        }
    }

    if (firstInput instanceof HTMLSelectElement && firstInput.multiple) {
        if (selector === 'label') {
            return Array.from(firstInput.selectedOptions).map((option) => {
                return option.label || option.text;
            });
        }

        return Array.from(firstInput.selectedOptions).map((option) => {
            return option.value;
        });
    }

    if (firstInput instanceof HTMLSelectElement && selector === 'label') {
        return Array.from(firstInput.selectedOptions).map((option) => {
            return option.label || option.text;
        });
    }

    return inputs.map((input) => {
        return input.value;
    });
}

export function getConditionInputEventNames(_input: ConditionInput): string[] {
    return ['input', 'change'];
}

export function readConditionValues(inputs: ConditionInput[], source: ConditionSource | null = null): string[] {
    const groupedInputs = new Map<string, ConditionInput[]>();

    inputs.forEach((input, index) => {
        const key = getInputKey(input, index);
        const existing = groupedInputs.get(key) || [];
        existing.push(input);
        groupedInputs.set(key, existing);
    });

    const rawValues = Array.from(groupedInputs.values()).flatMap((group) => {
        return readInputGroupValues(group, source?.selector || '');
    });

    return (rawValues.length === 0 || rawValues.every((value) => value === '')) && source?.defaultValue ? [source.defaultValue] : rawValues;
}

// DOM controls adapt to the same normalized condition projection as client-rendered values.
export function readConditionProjection(inputs: ConditionInput[], source: ConditionSource | null, type: import('@verbb/formie-core').ConditionValueType, currentRow?: number): unknown {
    if (source?.transformerParams.scope) {
        const grouped = new Map<string, ConditionInput[]>();
        for (const input of inputs) {
            const row = input.name.match(/\[([0-9]+)\]/)?.[1];
            if (row != null) grouped.set(row, [...(grouped.get(row) ?? []), input]);
        }
        const rows = [...grouped.values()].map((group) => readConditionProjection(group, { ...source, transformerParams: {} }, type === 'collection' ? (group.length > 1 ? 'collection' : 'text') : type));
        const selected = selectConditionRows(rows, source.transformerParams, currentRow);
        if (selected.diagnostic) return { conditionDiagnostic: selected.diagnostic };
        return source.transformerParams.scope === 'rows' && !Array.isArray(selected.value) ? [selected.value] : selected.value;
    }
    if (type === 'boolean') {
        const checkbox = inputs.find((input) => input instanceof HTMLInputElement && input.type === 'checkbox') as HTMLInputElement | undefined;
        if (checkbox) return checkbox.checked;
    }
    if (['date', 'time', 'datetime'].includes(type)) {
        const parts: Record<string, string> = {};
        for (const input of inputs) {
            const key = input.name.match(/\[(year|month|day|hour|minute|second|ampm|timezone)\]$/)?.[1];
            if (key && input.value !== '') parts[key] = input.value;
        }
        if (Object.keys(parts).length) return parts;
    }
    const values = readConditionValues(inputs, source);
    return type === 'collection' ? values : values[0] ?? null;
}
