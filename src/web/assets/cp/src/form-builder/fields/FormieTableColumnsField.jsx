import { useCallback, useMemo, useState } from 'react';

import { DropdownMenuItem, EditableTable } from '@verbb/plugin-kit-react/components';
import { FieldLayout } from '@verbb/plugin-kit-react/forms/Field';
import { useEditableTableFieldBinding } from '@verbb/plugin-kit-react/forms';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faGear } from '@fortawesome/pro-solid-svg-icons';

import { FormieTableColumnOptionsDialog } from '@form-builder/components/FormieTableColumnOptionsDialog';
import { normalizeTableColumnRows } from '@form-builder/utils/tableFieldSchema';

function FormieTableColumnsField({ form, field }) {
    const {
        rows,
        setRows,
        errors,
        cellErrors,
        handleCellChange,
    } = useEditableTableFieldBinding(form, field.name);
    const t = useTranslation();

    const [editingColumnId, setEditingColumnId] = useState(null);
    const normalizedRows = useMemo(() => {
        return normalizeTableColumnRows(rows);
    }, [rows]);

    const tableColumns = useMemo(() => {
        return Array.isArray(field.columns) ? field.columns : [];
    }, [field.columns]);

    const handleTableChange = useCallback((nextRows) => {
        setRows(normalizeTableColumnRows(nextRows));
    }, [setRows]);

    const editingColumn = normalizedRows.find((row) => { return row.id === editingColumnId; }) || null;

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
                columns={tableColumns}
                rows={normalizedRows}
                onChange={handleTableChange}
                onCellChange={handleCellChange}
                addRowLabel={field.addRowLabel || t('Add an option')}
                allowReorder={field.allowReorder ?? true}
                allowAdd={field.allowAdd ?? true}
                allowDelete={field.allowDelete ?? true}
                newRowDefaults={field.newRowDefaults}
                className=""
                fieldName={field.name}
                cellErrors={cellErrors}
                modifyColumn={undefined}
                renderRowMenuItemsBeforeCore={({ row }) => {
                    if (row?.type !== 'select') {
                        return null;
                    }

                    return (
                        <DropdownMenuItem
                            onClick={() => { setEditingColumnId(row.id); }}
                        >
                            <FontAwesomeIcon icon={faGear} />
                            {t('Edit options')}
                        </DropdownMenuItem>
                    );
                }}
            />

            <FormieTableColumnOptionsDialog
                open={Boolean(editingColumn)}
                onOpenChange={(open) => {
                    if (!open) {
                        setEditingColumnId(null);
                    }
                }}
                columnHeading={editingColumn?.heading}
                columnOptions={editingColumn?.options || []}
                onSave={(nextOptions) => {
                    if (!editingColumn) {
                        return;
                    }

                    const nextRows = normalizedRows.map((row) => {
                        if (row.id !== editingColumn.id) {
                            return row;
                        }

                        return {
                            ...row,
                            options: nextOptions,
                        };
                    });

                    setRows(normalizeTableColumnRows(nextRows));
                    setEditingColumnId(null);
                }}
            />
        </FieldLayout>
    );
}

export { FormieTableColumnsField };
