import { useCallback, useEffect, useMemo, useState } from 'react';

import {
    Button,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    EditableTable,
    Lightswitch,
} from '@verbb/plugin-kit-react/components';
import { FieldLayout } from '@verbb/plugin-kit-react/forms/Field';
import { useEditableTableFieldBinding } from '@verbb/plugin-kit-react/forms';
import { useEngineField } from '@verbb/plugin-kit-react/forms/useEngineField';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPlus } from '@fortawesome/pro-solid-svg-icons';

import { FormieBulkOptionsDialog } from '@form-builder/components/FormieBulkOptionsDialog';
import {
    resolveOptionAvailabilityValue,
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

        setRowsWithValues(nextRows);
    }, [field.name, form, setRowsWithValues]);

    const renderOptionRowMenuItems = useCallback(({ row, rowIndex }) => {
        if (row?.optgroup) {
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
    }, [t, updateRowAvailability]);

    const modifyOptionRow = useCallback((row) => {
        if (row?.optgroup) {
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
    }, [t]);

    const hasBulkOptions = Boolean(field.enableBulkOptions && field.predefinedOptions?.length);

    if (hasBulkOptions && !field.bulkOptionsAction) {
        throw new Error('LikertOptionsField requires "bulkOptionsAction" when bulk options are enabled.');
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
                            <FontAwesomeIcon icon={faPlus} className="size-3" />
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
                renderRowMenuItemsBeforeCore={field.enableOptionRowMenu ? renderOptionRowMenuItems : undefined}
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

export { LikertOptionsField };
