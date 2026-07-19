import { useCallback, useMemo, useState } from 'react';

import { EditableTable } from '@verbb/plugin-kit-react/components';
import { FieldLayout } from '@verbb/plugin-kit-react/forms';
import { useEditableTableFieldBinding } from '@utils/useEditableTableFieldBinding';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';

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

    const getRowMenuItems = useCallback((row) => {
        if (row?.type !== 'select') {
            return null;
        }

        return [
            {
                label: t('Edit options'),
                action: 'edit-options',
                icon: 'gear',
            },
        ];
    }, [t]);

    const handleRowMenuSelect = useCallback((detail) => {
        if (detail?.action !== 'edit-options') {
            return;
        }

        setEditingColumnId(detail.row?.id ?? null);
    }, []);

    const editingColumn = normalizedRows.find((row) => { return row.id === editingColumnId; }) || null;

    return (
        <>
            <FieldLayout
                name={field.name}
                label={field.label}
                instructions={field.instructions}
                warning={field.warning}
                required={field.required}
                errors={errors}
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
                    fieldName={field.name}
                    cellErrors={cellErrors}
                    getRowMenuItems={getRowMenuItems}
                    onRowMenuSelect={handleRowMenuSelect}
                />
            </FieldLayout>

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
        </>
    );
}

export { FormieTableColumnsField };
