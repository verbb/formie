import { useMemo } from 'react';

import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import { useEngineField, FieldLayout } from '@verbb/plugin-kit-react/forms';
import { EditableTable } from '@verbb/plugin-kit-react/components';
import useAppStore from '@form-builder/hooks/useAppStore';
import { useFormValues } from '@form-builder/hooks/useFormTools';
import { buildConditionFieldPicker } from '@form-builder/fields/utils/conditionFieldPicker';
import { useVariableCategories } from '@form-builder/hooks/useVariableCategories';
import { ConditionVariablePickerCell } from '@form-builder/fields/components/ConditionVariablePickerCell';

const CONDITION_FIELD_VARIABLE_CONFIG = {
    content: 'singleLine',
    types: ['text'],
    groupFieldsByPage: true,
    groups: [
        'fieldsVariables',
        'staticFormVariables',
        'staticGeneralVariables',
        'staticSiteVariables',
    ],
};

function NotificationRecipientsField({ field, form }) {
    const {
        value, setValue, setTouched, errors,
    } = useEngineField(form, field.name);
    const formValues = useFormValues();
    const getFieldTypeByType = useAppStore((state) => { return state.getFieldTypeByType; });
    const variableCategoryLabels = useAppStore((state) => { return state.variableCategoryLabels || {}; });
    const variableCategoryOrder = useAppStore((state) => { return state.variableCategoryOrder || []; });
    const variableTransformerRegistry = useAppStore((state) => { return state.variableCategoriesConfig?.transformerRegistry || {}; });
    const variableCategories = useVariableCategories(CONDITION_FIELD_VARIABLE_CONFIG);

    const t = useTranslation();

    const { conditionOptions, fieldOptions } = field;

    const { modifyValueColumn } = useMemo(() => {
        return buildConditionFieldPicker({
            baseFieldOptions: fieldOptions,
            formValues,
            getFieldTypeByType,
            t,
        });
    }, [fieldOptions, formValues, getFieldTypeByType, t]);

    const rows = Array.isArray(value) ? value : [];

    return (
        <FieldLayout
            name={field.name}
            label={field.label}
            instructions={field.instructions}
            required={field.required}
            errors={errors}
        >
            <EditableTable
                columns={[
                    {
                        name: 'email',
                        label: t('Email'),
                        type: 'text',
                    },
                    {
                        name: 'field',
                        label: t('Field'),
                        type: 'variablePicker',
                        variableCategories,
                        variableCategoryLabels,
                        variableCategoryOrder,
                        variableTransformerRegistry,
                        noneOptionLabel: t('Select an option'),
                        renderCell: ({
                            column, value, isInvalid, updateValue,
                        }) => {
                            return (
                                <ConditionVariablePickerCell
                                    column={column}
                                    value={value}
                                    isInvalid={isInvalid}
                                    updateValue={updateValue}
                                />
                            );
                        },
                        pickerContentClassName: 'min-w-[260px] max-w-[360px] p-0 overflow-hidden flex flex-col',
                    },
                    {
                        name: 'condition',
                        label: t('Condition'),
                        type: 'select',
                        options: conditionOptions,
                        thin: true,
                    },
                    {
                        name: 'value',
                        label: t('Value'),
                        type: 'text',
                    },
                ]}
                rows={rows}
                onChange={(nextRows) => {
                    setValue(nextRows);
                    setTouched();
                }}
                modifyColumn={modifyValueColumn}
                addRowLabel={t('Add a condition')}
                allowReorder={false}
                allowAdd={true}
                allowDelete={true}
            />
        </FieldLayout>
    );
}

export { NotificationRecipientsField };
