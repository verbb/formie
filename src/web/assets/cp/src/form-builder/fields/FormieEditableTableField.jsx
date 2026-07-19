import { useCallback, useEffect, useState } from 'react';

import { Button, EditableTable, Icon } from '@verbb/plugin-kit-react/components';
import { FieldLayout } from '@verbb/plugin-kit-react/forms';
import { useEditableTableFieldBinding } from '@utils/useEditableTableFieldBinding';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';

import { FormieBulkOptionsDialog } from '@form-builder/components/FormieBulkOptionsDialog';
import {
    applyOptionAvailabilityMenuSelect,
    getOptionAvailabilityRowMenuItems,
    getOptionAvailabilityRowModifier,
    patchRowAvailability,
} from '@form-builder/utils/optionAvailability';
import { syncLikertRowValues } from '@form-builder/utils/likertRowValues';
import { syncQuestionOptionValues } from '@form-builder/utils/questionOptionValues';

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
    const hasOptionRowMenu = field.enableOptionRowMenu === true || field.name === 'options';

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

    const updateRowAvailability = useCallback((rowIndex, availability) => {
        const currentRows = Array.isArray(form.getFieldValue(field.name))
            ? form.getFieldValue(field.name)
            : [];

        const nextRows = currentRows.map((row, index) => {
            if (index !== rowIndex) {
                return row;
            }

            return patchRowAvailability(row, availability);
        });

        setRows(nextRows);
    }, [field.name, form, setRows]);

    const getRowMenuItems = useCallback((row) => {
        if (!hasOptionRowMenu) {
            return null;
        }

        return getOptionAvailabilityRowMenuItems(row, t);
    }, [hasOptionRowMenu, t]);

    const handleRowMenuSelect = useCallback((detail) => {
        applyOptionAvailabilityMenuSelect(detail, updateRowAvailability);
    }, [updateRowAvailability]);

    const modifyOptionRow = useCallback((row) => {
        if (!hasOptionRowMenu) {
            return null;
        }

        return getOptionAvailabilityRowModifier(row, t);
    }, [hasOptionRowMenu, t]);

    const hasBulkOptions = Boolean(field.enableBulkOptions && field.predefinedOptions?.length);
    const shouldSyncLikertRowValues = field.syncLikertRowValues === true;
    const shouldSyncQuestionOptionValues = field.syncQuestionOptionValues === true;

    const syncRows = useCallback((nextRows) => {
        if (shouldSyncLikertRowValues) {
            return syncLikertRowValues(nextRows);
        }

        if (shouldSyncQuestionOptionValues) {
            return syncQuestionOptionValues(nextRows);
        }

        return nextRows;
    }, [shouldSyncLikertRowValues, shouldSyncQuestionOptionValues]);

    const handleRowsChange = useCallback((nextRows) => {
        setRows(syncRows(nextRows));
    }, [setRows, syncRows]);

    const handleTableCellChange = useCallback((...args) => {
        handleCellChange(...args);

        if (!shouldSyncQuestionOptionValues || args[1] !== 'label') {
            return;
        }

        queueMicrotask(() => {
            const currentRows = Array.isArray(form.getFieldValue(field.name))
                ? form.getFieldValue(field.name)
                : [];
            setRows(syncQuestionOptionValues(currentRows));
        });
    }, [field.name, form, handleCellChange, setRows, shouldSyncQuestionOptionValues]);

    useEffect(() => {
        if (!shouldSyncLikertRowValues && !shouldSyncQuestionOptionValues) {
            return;
        }

        if (!Array.isArray(rows) || rows.length === 0) {
            return;
        }

        const syncedRows = syncRows(rows);
        const rowsJson = JSON.stringify(rows);
        const syncedJson = JSON.stringify(syncedRows);

        if (rowsJson !== syncedJson) {
            setRows(syncedRows);
        }
    }, [rows, setRows, shouldSyncLikertRowValues, shouldSyncQuestionOptionValues, syncRows]);

    if (hasBulkOptions && !field.bulkOptionsAction) {
        throw new Error('FormieEditableTableField requires "bulkOptionsAction" when bulk options are enabled.');
    }

    return (
        <>
            <FieldLayout
                name={field.name}
                label={field.label}
                instructions={field.instructions}
                warning={field.warning}
                required={field.required}
                errors={errors}
                headerEnd={hasBulkOptions ? (
                    <Button type="button" size="sm" onClick={() => { setIsBulkDialogOpen(true); }}>
                        <Icon slot="start" icon="plus" className="size-3" />
                        {t('Bulk add options')}
                    </Button>
                ) : null}
            >
                <EditableTable
                    columns={field.columns}
                    rows={rows}
                    onChange={handleRowsChange}
                    onCellChange={handleTableCellChange}
                    addRowLabel={field.addRowLabel}
                    allowReorder={field.allowReorder}
                    allowAdd={field.allowAdd}
                    allowDelete={field.allowDelete}
                    fieldName={field.name}
                    cellErrors={cellErrors}
                    modifyRow={hasOptionRowMenu ? modifyOptionRow : undefined}
                    getRowMenuItems={hasOptionRowMenu ? getRowMenuItems : undefined}
                    onRowMenuSelect={hasOptionRowMenu ? handleRowMenuSelect : undefined}
                />
            </FieldLayout>

            {hasBulkOptions && (
                <FormieBulkOptionsDialog
                    open={isBulkDialogOpen}
                    onOpenChange={setIsBulkDialogOpen}
                    predefinedOptions={field.predefinedOptions}
                    bulkOptionsAction={field.bulkOptionsAction}
                    onSave={handleBulkSave}
                />
            )}
        </>
    );
}

export { FormieEditableTableField };
