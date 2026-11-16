import { clone, findRecursive } from '@verbb/plugin-kit-core';
import { getFieldReferenceOptions } from '@form-builder/hooks/useFormTools';
import { resolveVariableCategories } from '@form-builder/hooks/useVariableCategories';
import { buildVariableOptionIndex } from '@form-builder/fields/utils/variablePicker';

// Condition rules compare text, and boolean values such as an Agree field's.
export const CONDITION_FIELD_TYPES = ['text', 'boolean'];

// New rules are limited to preceding fields, but a stored legacy rule can point at any field and still needs its label.
export const buildStoredRuleOptionIndex = (config, formValues, variableConfig, options = {}) => {
    return buildVariableOptionIndex(resolveVariableCategories(config, formValues, variableConfig, options));
};

export const buildConditionFieldPicker = ({
    baseFieldOptions = [],
    conditionOptions = [],
    formValues = {},
    getFieldTypeByType,
    t,
    fieldReferenceOptions = {},
}) => {
    const fieldColumnOptions = clone(baseFieldOptions);

    const fieldInnerOptions = getFieldReferenceOptions(formValues, {
        getFieldTypeByType,
        target: 'fieldSelect',
        includeColumnMeta: true,
        ...fieldReferenceOptions,
    });

    if (fieldInnerOptions.length) {
        fieldColumnOptions.push({
            group: t('Fields'),
            options: fieldInnerOptions,
        });
    }

    const modifyValueColumn = (row, columnName) => {
        const selected = findRecursive(fieldColumnOptions, (item) => item.value === row.field);
        if (columnName === 'condition') {
            const allowed = selected?.conditionOperators || ['=', '!=', '>', '<', 'contains', 'notContains', 'startsWith', 'endsWith', 'empty', 'notEmpty'];
            const options = conditionOptions.filter((option) => option.value === '' || allowed.includes(option.value));
            if (row.condition && !allowed.includes(row.condition)) options.push({ label: t('Unsupported condition'), value: row.condition, disabled: true });
            return { type: 'select', options };
        }
        if (columnName !== 'value') {
            return;
        }

        if (row.condition !== '=' && row.condition !== '!=') {
            return;
        }

        const foundField = findRecursive(fieldColumnOptions, (item) => { return item.value === row.field; });

        if (foundField && foundField.column) {
            return foundField.column;
        }

        if (foundField?.conditionValueType === 'boolean') {
            return {
                type: 'select',
                options: [
                    { label: t('Checked'), value: '1' },
                    { label: t('Unchecked'), value: '0' },
                ],
            };
        }
    };

    return {
        fieldColumnOptions,
        modifyValueColumn,
    };
};
