import { useCallback, useState } from 'react';

import { Button, EditableTable } from '@verbb/plugin-kit-react/components';
import { FieldLayout } from '@verbb/plugin-kit-react/forms/Field';
import { useEditableTableFieldBinding } from '@verbb/plugin-kit-react/forms';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPlus } from '@fortawesome/pro-solid-svg-icons';

import { FormieBulkOptionsDialog } from '@form-builder/components/FormieBulkOptionsDialog';

function FormieEditableTableField({ form, field }) {
    const {
        rows,
        setRows,
        errors,
        cellErrors,
        handleCellChange,
    } = useEditableTableFieldBinding(form, field.name);
    const t = useTranslation();
    const [isBulkDialogOpen, setIsBulkDialogOpen] = useState(false);

    const handleBulkSave = useCallback((parsedRows, mode) => {
        if (!Array.isArray(parsedRows) || parsedRows.length === 0) {
            return;
        }

        const stripInternalId = (row) => {
            if (!row || typeof row !== 'object') {
                return row;
            }

            const { _id, ...rest } = row;
            return rest;
        };

        const currentRows = rows.map(stripInternalId);
        const nextRows = mode === 'replace' ? parsedRows : [...currentRows, ...parsedRows];
        setRows(nextRows);
    }, [rows, setRows]);

    const hasBulkOptions = Boolean(field.enableBulkOptions && field.predefinedOptions?.length);

    if (hasBulkOptions && !field.bulkOptionsAction) {
        throw new Error('FormieEditableTableField requires "bulkOptionsAction" when bulk options are enabled.');
    }

    return (
        <FieldLayout
            name={field.name}
            label={field.label}
            instructions={field.instructions}
            warning={field.warning}
            required={field.required}
            errors={errors}
            withControl={false}
            headerEnd={hasBulkOptions ? (
                <Button type="button" size="sm" onClick={() => { setIsBulkDialogOpen(true); }}>
                    <FontAwesomeIcon icon={faPlus} className="size-3" />
                    {t('Bulk add options')}
                </Button>
            ) : null}
        >
            <EditableTable
                columns={field.columns}
                rows={rows}
                onChange={setRows}
                onCellChange={handleCellChange}
                addRowLabel={field.addRowLabel}
                allowReorder={field.allowReorder}
                allowAdd={field.allowAdd}
                allowDelete={field.allowDelete}
                className=""
                fieldName={field.name}
                cellErrors={cellErrors}
                modifyColumn={undefined}
            />

            {hasBulkOptions && (
                <FormieBulkOptionsDialog
                    open={isBulkDialogOpen}
                    onOpenChange={setIsBulkDialogOpen}
                    predefinedOptions={field.predefinedOptions}
                    bulkOptionsAction={field.bulkOptionsAction}
                    onSave={handleBulkSave}
                />
            )}
        </FieldLayout>
    );
}

export { FormieEditableTableField };
