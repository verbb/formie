import { useCallback, useEffect, useMemo, useState } from 'react';

import { Button, EditableTable, Icon, Lightswitch } from '@verbb/plugin-kit-react/components';
import { FieldLayout, useEngineField } from '@verbb/plugin-kit-react/forms';
import { useEditableTableFieldBinding } from '@utils/useEditableTableFieldBinding';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';

import { FormieBulkOptionsDialog } from '@form-builder/components/FormieBulkOptionsDialog';
import {
    applyOptionAvailabilityMenuSelect,
    getOptionAvailabilityRowMenuItems,
    getOptionAvailabilityRowModifier,
    patchRowAvailability,
} from '@form-builder/utils/optionAvailability';
import { syncLikertColumnValues } from '@form-builder/utils/likertColumnValues';

function LikertOptionsField({ form, field }) {
    const {
        rows,
        setRows,
        errors,
        cellErrors,
        handleCellChange,
    } = useEditableTableFieldBinding(form, field.name);
    const { value: scoringEnabled, setValue: setScoringEnabled } = useEngineField(form, 'scoringEnabled');
    const t = useTranslation();
    const [isBulkDialogOpen, setIsBulkDialogOpen] = useState(false);

    const columns = useMemo(() => {
        const nextColumns = [
            {
                type: 'text',
                name: 'label',
                label: t('Label'),
                required: true,
            },
        ];

        if (scoringEnabled) {
            nextColumns.push({
                type: 'number',
                name: 'points',
                label: t('Score'),
                thin: true,
                width: '72px',
            });
        }

        return nextColumns;
    }, [t, scoringEnabled]);

    const columnsLayoutKey = useMemo(() => {
        return columns.map((column) => column.name).join('-');
    }, [columns]);

    const setRowsWithValues = useCallback((nextRows) => {
        setRows(syncLikertColumnValues(nextRows));
    }, [setRows]);

    useEffect(() => {
        if (!Array.isArray(rows) || rows.length === 0) {
            return;
        }

        const syncedRows = syncLikertColumnValues(rows);
        const rowsJson = JSON.stringify(rows);
        const syncedJson = JSON.stringify(syncedRows);

        if (rowsJson !== syncedJson) {
            setRows(syncedRows);
        }
    }, [rows, setRows]);

    const handleLikertCellChange = useCallback((...args) => {
        handleCellChange(...args);

        if (args[1] !== 'label') {
            return;
        }

        queueMicrotask(() => {
            const currentRows = Array.isArray(form.getFieldValue(field.name))
                ? form.getFieldValue(field.name)
                : [];
            setRows(syncLikertColumnValues(currentRows));
        });
    }, [field.name, form, handleCellChange, setRows]);

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
        setRowsWithValues(nextRows);
    }, [rows, setRowsWithValues]);

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

        setRowsWithValues(nextRows);
    }, [field.name, form, setRowsWithValues]);

    const getRowMenuItems = useCallback((row) => {
        return getOptionAvailabilityRowMenuItems(row, t);
    }, [t]);

    const handleRowMenuSelect = useCallback((detail) => {
        applyOptionAvailabilityMenuSelect(detail, updateRowAvailability);
    }, [updateRowAvailability]);

    const modifyOptionRow = useCallback((row) => {
        return getOptionAvailabilityRowModifier(row, t);
    }, [t]);

    const hasBulkOptions = Boolean(field.enableBulkOptions && field.predefinedOptions?.length);

    if (hasBulkOptions && !field.bulkOptionsAction) {
        throw new Error('LikertOptionsField requires "bulkOptionsAction" when bulk options are enabled.');
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
                headerEnd={(
                    <div className="flex items-center gap-3">
                        <div className="flex items-center gap-2">
                            <span className="text-sm text-gray-600">{t('Enable Scoring')}</span>
                            <Lightswitch
                                checked={Boolean(scoringEnabled)}
                                onCheckedChange={(checked) => { setScoringEnabled(checked); }}
                                size="sm"
                            />
                        </div>
                        {hasBulkOptions ? (
                            <Button type="button" size="sm" onClick={() => { setIsBulkDialogOpen(true); }}>
                                <Icon slot="start" icon="plus" className="size-3" />
                                {t('Bulk add options')}
                            </Button>
                        ) : null}
                    </div>
                )}
            >
                <EditableTable
                    key={columnsLayoutKey}
                    columns={columns}
                    rows={rows}
                    onChange={setRowsWithValues}
                    onCellChange={handleLikertCellChange}
                    addRowLabel={field.addRowLabel}
                    allowReorder={field.allowReorder}
                    allowAdd={field.allowAdd}
                    allowDelete={field.allowDelete}
                    fieldName={field.name}
                    cellErrors={cellErrors}
                    modifyRow={field.enableOptionRowMenu ? modifyOptionRow : undefined}
                    getRowMenuItems={field.enableOptionRowMenu ? getRowMenuItems : undefined}
                    onRowMenuSelect={field.enableOptionRowMenu ? handleRowMenuSelect : undefined}
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

export { LikertOptionsField };
