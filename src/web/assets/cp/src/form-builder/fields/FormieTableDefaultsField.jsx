import {
    useCallback, useEffect, useMemo, useSyncExternalStore,
} from 'react';

import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import { EditableTable } from '@verbb/plugin-kit-react/components';
import { FieldLayout } from '@verbb/plugin-kit-react/forms';
import { useEditableTableFieldBinding } from '@utils/useEditableTableFieldBinding';

import {
    applyDefaultRowCountConstraints,
    parsePositiveInt,
    normalizeTableColumnRows,
    rowsAreEqual,
    tableColumnRowsToEditableColumns,
} from '@form-builder/utils/tableFieldSchema';

function FormieTableDefaultsField({ form, field }) {
    const {
        rows,
        setRows,
        errors,
        cellErrors,
        handleCellChange,
    } = useEditableTableFieldBinding(form, field.name);
    const t = useTranslation();
    const sourceColumnsCache = useMemo(() => {
        return { deps: '', snapshot: [] };
    }, []);

    const columnsSource = field.columnsSource || 'columns';
    const sourceColumns = useSyncExternalStore(
        form?.store?.subscribe?.bind(form.store) ?? (() => { return () => { }; }),
        () => {
            const nextColumns = form?.getFieldValue?.(columnsSource) ?? [];
            const deps = JSON.stringify(nextColumns);

            if (sourceColumnsCache.deps === deps) {
                return sourceColumnsCache.snapshot;
            }

            const snapshot = JSON.parse(JSON.stringify(nextColumns));
            sourceColumnsCache.deps = deps;
            sourceColumnsCache.snapshot = snapshot;

            return snapshot;
        },
        () => { return sourceColumnsCache.snapshot; },
    );

    const isStatic = useSyncExternalStore(
        form?.store?.subscribe?.bind(form.store) ?? (() => { return () => { }; }),
        () => {
            return Boolean(form?.getFieldValue?.('static'));
        },
        () => { return false; },
    );

    const minRows = useSyncExternalStore(
        form?.store?.subscribe?.bind(form.store) ?? (() => { return () => { }; }),
        () => {
            return parsePositiveInt(form?.getFieldValue?.('minRows'));
        },
        () => { return null; },
    );

    const maxRows = useSyncExternalStore(
        form?.store?.subscribe?.bind(form.store) ?? (() => { return () => { }; }),
        () => {
            return parsePositiveInt(form?.getFieldValue?.('maxRows'));
        },
        () => { return null; },
    );

    const normalizedSourceColumns = useMemo(() => {
        return normalizeTableColumnRows(sourceColumns);
    }, [sourceColumns]);

    const tableColumns = useMemo(() => {
        return tableColumnRowsToEditableColumns(normalizedSourceColumns, t);
    }, [normalizedSourceColumns, t]);
    const headingColumnNames = useMemo(() => {
        return new Set(tableColumns.filter((column) => {
            return column?.type === 'heading';
        }).map((column) => {
            return column.name;
        }));
    }, [tableColumns]);

    const normalizedRows = useMemo(() => {
        return applyDefaultRowCountConstraints(rows, normalizedSourceColumns, minRows, maxRows);
    }, [maxRows, minRows, normalizedSourceColumns, rows]);

    useEffect(() => {
        if (rowsAreEqual(rows, normalizedRows)) {
            return;
        }

        setRows(normalizedRows);
    }, [normalizedRows, rows, setRows]);

    const handleTableChange = useCallback((nextRows) => {
        setRows(applyDefaultRowCountConstraints(nextRows, normalizedSourceColumns, minRows, maxRows));
    }, [maxRows, minRows, normalizedSourceColumns, setRows]);
    const modifyColumn = useCallback((_row, columnName) => {
        if (!headingColumnNames.has(columnName)) {
            return undefined;
        }

        return {
            type: 'text',
        };
    }, [headingColumnNames, t]);

    const atMinRows = minRows !== null && normalizedRows.length <= minRows;
    const atMaxRows = maxRows !== null && normalizedRows.length >= maxRows;
    const isLockedStatic = isStatic || (minRows !== null && maxRows !== null && minRows === maxRows);

    return (
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
                allowReorder={(field.allowReorder ?? true) && !isLockedStatic}
                allowAdd={(field.allowAdd ?? true) && !isLockedStatic && !atMaxRows}
                allowDelete={(field.allowDelete ?? true) && !isLockedStatic && !atMinRows}
                fieldName={field.name}
                cellErrors={cellErrors}
                modifyColumn={modifyColumn}
            />
        </FieldLayout>
    );
}

export { FormieTableDefaultsField };
