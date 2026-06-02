import { useMemo } from 'react';
import { EditableTable } from '@verbb/plugin-kit-react/components';
import { FieldLayout } from '@verbb/plugin-kit-react/forms/Field';
import { useEngineField } from '@verbb/plugin-kit-react/forms/useEngineField';

import useAppStore from '@form-builder/hooks/useAppStore';
import { useFormValues, getFieldReferenceOptions } from '@form-builder/hooks/useFormTools';

const normalizeColumnsConfig = (columnsConfig) => {
    if (Array.isArray(columnsConfig)) {
        return columnsConfig;
    }

    if (!columnsConfig || typeof columnsConfig !== 'object') {
        return [];
    }

    return Object.entries(columnsConfig).map(([key, value]) => {
        const config = (value && typeof value === 'object') ? value : {};

        return {
            name: config.name || key,
            ...config,
        };
    });
};

const normalizeRowsConfig = (rowsConfig) => {
    if (Array.isArray(rowsConfig)) {
        return rowsConfig;
    }

    if (!rowsConfig || typeof rowsConfig !== 'object') {
        return [];
    }

    return Object.entries(rowsConfig).map(([key, value]) => {
        const row = (value && typeof value === 'object') ? value : {};

        return {
            key,
            ...row,
        };
    });
};

const getFirstConfiguredArray = (...values) => {
    return values.find((value) => { return Array.isArray(value) && value.length; }) || [];
};

const mapColumns = (columnsConfig, formValues, getFieldTypeByType) => {
    return (columnsConfig || []).map((config, index) => {
        const className = String(config?.class || '');
        const columnName = config?.name || `col${index + 1}`;
        const type = config?.type || 'text';
        const isThin = Boolean(config?.thin || className.includes('thin'));

        if (type === 'fieldSelect') {
            const includedTypes = getFirstConfiguredArray(config?.includedTypes, config?.fieldTypes);
            const variableTypes = getFirstConfiguredArray(config?.variableConfig?.types, config?.types);
            const options = getFieldReferenceOptions(formValues, {
                getFieldTypeByType,
                target: 'fieldSelect',
                includedTypes,
                excludedTypes: config?.excludedTypes || [],
                excludedFields: Array.isArray(config?.excludedFields) ? config.excludedFields : [],
                variableTypes,
            });

            options.unshift({
                label: config?.emptyOptionLabel || Craft.t('formie', 'Select an option'),
                value: '',
            });

            return {
                name: columnName,
                label: config?.label ?? config?.heading ?? columnName,
                type: 'select',
                required: Boolean(config?.required),
                options,
                thin: isThin,
                className,
            };
        }

        return {
            name: columnName,
            label: config?.label ?? config?.heading ?? columnName,
            type,
            required: Boolean(config?.required),
            options: config?.options,
            placeholder: config?.placeholder,
            thin: isThin,
            className,
        };
    });
};

const mapRowsToArray = (rowEntries, columns, fieldValue) => {
    const editableColumnNames = (columns || [])
        .filter((column) => { return !['heading', 'label'].includes(column.type || ''); })
        .map((column) => { return column.name; });

    return rowEntries.map((defaultRow, index) => {
        const rowKey = String(defaultRow?.key || defaultRow?.name || `row${index + 1}`);
        const savedRow = fieldValue?.[rowKey] ?? '';
        const savedRowData = (savedRow && typeof savedRow === 'object') ? savedRow : {};

        const normalizedRow = {
            ...defaultRow,
            ...savedRowData,
            __staticRowKey: rowKey,
            __staticRowIndex: index,
        };

        if (editableColumnNames.length === 1 && typeof savedRow !== 'object') {
            normalizedRow[editableColumnNames[0]] = savedRow ?? '';
        }

        return normalizedRow;
    });
};

export const FormieStaticTableField = ({ form, field }) => {
    const {
        value,
        setValue,
        setTouched,
        errors,
    } = useEngineField(form, field.name);
    const formValues = useFormValues();
    const getFieldTypeByType = useAppStore((state) => { return state.getFieldTypeByType; });

    const rowEntries = useMemo(() => {
        return normalizeRowsConfig(field.rows);
    }, [field.rows]);

    const columns = useMemo(() => {
        return mapColumns(normalizeColumnsConfig(field.columns), formValues, getFieldTypeByType);
    }, [field.columns, formValues, getFieldTypeByType]);

    const rows = useMemo(() => {
        return mapRowsToArray(rowEntries, columns, value || {});
    }, [columns, rowEntries, value]);

    return (
        <FieldLayout
            name={field.name}
            label={field.label}
            instructions={field.instructions}
            warning={field.warning}
            required={field.required}
            errors={errors}
            withControl={false}
        >
            <EditableTable
                columns={columns}
                rows={rows}
                onChange={(nextRows) => {
                    const nextValue = {};
                    const editableColumns = columns.filter((column) => {
                        return !['heading', 'label'].includes(column.type || '');
                    });

                    nextRows.forEach((row, index) => {
                        const fallbackKey = String(rowEntries[index]?.key || rowEntries[index]?.name || `row${index + 1}`);
                        const rowKey = row.__staticRowKey || fallbackKey;

                        if (editableColumns.length === 1) {
                            const columnName = editableColumns[0].name;
                            nextValue[rowKey] = row[columnName] ?? '';
                            return;
                        }

                        const normalizedRow = {};
                        editableColumns.forEach((column) => {
                            normalizedRow[column.name] = row[column.name] ?? '';
                        });
                        nextValue[rowKey] = normalizedRow;
                    });

                    setValue(nextValue);
                    setTouched();
                }}
                allowAdd={false}
                allowDelete={false}
                allowReorder={false}
                className=""
            />
        </FieldLayout>
    );
};
