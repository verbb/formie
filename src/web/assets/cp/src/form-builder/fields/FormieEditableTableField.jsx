import { useCallback, useEffect, useState } from 'react';

import {
    Button,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    EditableTable,
} from '@verbb/plugin-kit-react/components';
import { FieldLayout } from '@verbb/plugin-kit-react/forms/Field';
import { useEditableTableFieldBinding } from '@verbb/plugin-kit-react/forms';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPlus } from '@fortawesome/pro-solid-svg-icons';

import { FormieBulkOptionsDialog } from '@form-builder/components/FormieBulkOptionsDialog';
import {
    resolveOptionAvailabilityValue,
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

            const next = { ...(row && typeof row === 'object' ? row : {}) };
            delete next.disabled;
            delete next._id;

            if (availability) {
                next.availability = availability;
            } else {
                delete next.availability;
            }

            return next;
        });

        setRows(nextRows);
    }, [field.name, form, setRows]);

    const renderOptionRowMenuItems = useCallback(({ row, rowIndex }) => {
        if (!hasOptionRowMenu || row?.optgroup) {
            return null;
        }

        const currentValue = resolveOptionAvailabilityValue(row);

        return (
            <DropdownMenuRadioGroup
                value={currentValue}
                onValueChange={(value) => {
                    updateRowAvailability(rowIndex, value === 'visible' ? null : value);
                }}
            >
                <DropdownMenuRadioItem value="visible">
                    {t('Visible')}
                </DropdownMenuRadioItem>
                <DropdownMenuRadioItem value="hidden">
                    {t('Hidden')}
                </DropdownMenuRadioItem>
                <DropdownMenuRadioItem value="disabled">
                    {t('Disabled')}
                </DropdownMenuRadioItem>
            </DropdownMenuRadioGroup>
        );
    }, [hasOptionRowMenu, t, updateRowAvailability]);

    const modifyOptionRow = useCallback((row) => {
        if (!hasOptionRowMenu || row?.optgroup) {
            return null;
        }

        const availability = resolveOptionAvailabilityValue(row);

        if (availability === 'hidden') {
            return {
                cellClassName: 'bg-amber-50/80',
                title: t('Hidden from the front-end form'),
            };
        }

        if (availability === 'disabled') {
            return {
                cellClassName: 'bg-slate-100/90',
                title: t('Disabled on the front-end form'),
            };
        }

        return null;
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
                onChange={handleRowsChange}
                onCellChange={handleTableCellChange}
                addRowLabel={field.addRowLabel}
                allowReorder={field.allowReorder}
                allowAdd={field.allowAdd}
                allowDelete={field.allowDelete}
                className=""
                fieldName={field.name}
                cellErrors={cellErrors}
                modifyRow={hasOptionRowMenu ? modifyOptionRow : undefined}
                renderRowMenuItemsBeforeCore={hasOptionRowMenu ? renderOptionRowMenuItems : undefined}
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
