import { applyConditionSource } from '#modules/fields/conditions/transforms';
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

    return applyConditionSource(rawValues, source);
}

export function isConditionValueEmpty(values: string[]): boolean {
    return values.length === 0 || values.every((value) => {
        return value.trim() === '';
    });
}
