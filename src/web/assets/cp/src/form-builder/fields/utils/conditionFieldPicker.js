import { clone, findRecursive } from '@verbb/plugin-kit-react/utils';
import { getFieldReferenceOptions } from '@form-builder/hooks/useFormTools';

export const buildConditionFieldPicker = ({
    baseFieldOptions = [],
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
    };

    return {
        fieldColumnOptions,
        modifyValueColumn,
    };
};
