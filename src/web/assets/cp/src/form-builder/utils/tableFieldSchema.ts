const TABLE_COLUMN_TYPE_FALLBACK = 'singleline';

const TABLE_COLUMN_TYPE_ORDER = [
    'checkbox',
    'color',
    'date',
    'select',
    'email',
    'heading',
    'lightswitch',
    'multiline',
    'number',
    'time',
    'singleline',
    'url',
];

const TABLE_COLUMN_TYPE_LABELS: Record<string, string> = {
    checkbox: 'Checkbox',
    color: 'Color',
    date: 'Date',
    select: 'Dropdown',
    email: 'Email',
    heading: 'Heading',
    lightswitch: 'Lightswitch',
    multiline: 'Multi-line Text',
    number: 'Number',
    time: 'Time',
    singleline: 'Single-line Text',
    url: 'URL',
};

const TABLE_TO_EDITABLE_TYPE: Record<string, string> = {
    checkbox: 'checkbox',
    color: 'color',
    date: 'date',
    email: 'email',
    heading: 'heading',
    lightswitch: 'lightswitch',
    multiline: 'textarea',
    number: 'number',
    select: 'select',
    singleline: 'text',
    time: 'time',
    url: 'url',
};

const sanitizeString = (value: unknown) => {
    if (value === null || value === undefined) {
        return '';
    }

    return String(value);
};

export const getTableColumnTypeOptions = (t: (value: string) => string = (value) => { return value; }) => {
    return TABLE_COLUMN_TYPE_ORDER.map((value) => {
        return {
            label: t(TABLE_COLUMN_TYPE_LABELS[value] ?? value),
            value,
        };
    });
};

const normalizeSelectOptions = (value: unknown) => {
    let parsedValue = value;

    if (typeof parsedValue === 'string') {
        try {
            parsedValue = JSON.parse(parsedValue);
        } catch (error) {
            parsedValue = [];
        }
    }

    if (!Array.isArray(parsedValue)) {
        return [];
    }

    return parsedValue.map((option) => {
        return {
            label: sanitizeString((option as any)?.label),
            value: sanitizeString((option as any)?.value),
            default: Boolean((option as any)?.default ?? (option as any)?.isDefault),
        };
    });
};

const parseColumnIdValue = (value: unknown) => {
    const match = sanitizeString(value).match(/^col(\d+)$/);
    if (!match) {
        return null;
    }

    const numericValue = Number(match[1]);
    if (!Number.isFinite(numericValue)) {
        return null;
    }

    return numericValue;
};

const createColumnIdFactory = (rows: any[]) => {
    const numericIds = rows
        .map((row) => { return parseColumnIdValue(row?.id); })
        .filter((value) => { return value !== null; }) as number[];

    let nextValue = numericIds.length ? Math.max(...numericIds) + 1 : 1;

    return () => {
        const id = `col${nextValue}`;
        nextValue += 1;
        return id;
    };
};

export const normalizeTableColumnRows = (rows: unknown) => {
    const normalizedRows = Array.isArray(rows) ? rows : [];
    const getNextId = createColumnIdFactory(normalizedRows as any[]);
    const usedIds = new Set<string>();

    return normalizedRows.map((row) => {
        const rowObject = row && typeof row === 'object' ? { ...(row as Record<string, unknown>) } : {};
        const rowType = sanitizeString(rowObject.type) || TABLE_COLUMN_TYPE_FALLBACK;
        const normalizedType = TABLE_TO_EDITABLE_TYPE[rowType] ? rowType : TABLE_COLUMN_TYPE_FALLBACK;

        let id = sanitizeString(rowObject.id);
        if (!id || usedIds.has(id)) {
            id = getNextId();
        }

        usedIds.add(id);

        const normalizedRow: Record<string, unknown> = {
            ...rowObject,
            id,
            heading: sanitizeString(rowObject.heading),
            handle: sanitizeString(rowObject.handle),
            width: sanitizeString(rowObject.width),
            type: normalizedType,
        };

        if (normalizedType === 'select') {
            normalizedRow.options = normalizeSelectOptions(rowObject.options);
        } else {
            normalizedRow.options = [];
        }

        return normalizedRow;
    });
};

export const tableColumnRowsToEditableColumns = (rows: unknown, t: (value: string) => string = (value) => { return value; }) => {
    const normalizedRows = normalizeTableColumnRows(rows);

    return normalizedRows.map((row) => {
        const columnId = sanitizeString((row as any).id);
        const heading = sanitizeString((row as any).heading);
        const width = sanitizeString((row as any).width);
        const type = sanitizeString((row as any).type);

        const editableType = TABLE_TO_EDITABLE_TYPE[type] ?? 'text';

        const editableColumn: Record<string, unknown> = {
            name: columnId,
            label: heading,
            type: editableType,
            width: width || undefined,
        };

        if (editableType === 'select') {
            editableColumn.options = normalizeSelectOptions((row as any).options);
        }

        return editableColumn;
    });
};

export const normalizeDefaultRowsForColumns = (rows: unknown, columns: unknown) => {
    const normalizedRows = Array.isArray(rows) ? rows : [];
    const normalizedColumns = normalizeTableColumnRows(columns);
    const columnIds = normalizedColumns.map((column) => { return sanitizeString((column as any).id); });

    return normalizedRows.map((row) => {
        const rowObject = row && typeof row === 'object' ? row as Record<string, unknown> : {};
        const { _id, ...withoutInternalId } = rowObject;
        const normalizedRow: Record<string, unknown> = {};

        columnIds.forEach((columnId) => {
            if (Object.prototype.hasOwnProperty.call(withoutInternalId, columnId)) {
                normalizedRow[columnId] = withoutInternalId[columnId];
                return;
            }

            normalizedRow[columnId] = '';
        });

        return normalizedRow;
    });
};

export const createEmptyDefaultRow = (columns: unknown) => {
    const normalizedColumns = normalizeTableColumnRows(columns);
    const row: Record<string, unknown> = {};

    normalizedColumns.forEach((column) => {
        const columnId = sanitizeString((column as any).id);
        if (!columnId) {
            return;
        }

        row[columnId] = '';
    });

    return row;
};

export const parsePositiveInt = (value: unknown) => {
    const numericValue = Number(value);
    if (!Number.isFinite(numericValue) || numericValue <= 0) {
        return null;
    }

    return Math.floor(numericValue);
};

export const applyDefaultRowCountConstraints = (
    rows: unknown,
    columns: unknown,
    minRowsValue: unknown,
    maxRowsValue: unknown,
) => {
    const normalizedRows = normalizeDefaultRowsForColumns(rows, columns);
    const minRows = parsePositiveInt(minRowsValue);
    const maxRows = parsePositiveInt(maxRowsValue);

    let constrainedRows = [...normalizedRows];
    const resolvedMaxRows = maxRows !== null ? maxRows : null;

    if (resolvedMaxRows !== null && constrainedRows.length > resolvedMaxRows) {
        constrainedRows = constrainedRows.slice(0, resolvedMaxRows);
    }

    if (minRows !== null && constrainedRows.length < minRows) {
        const rowsToAdd = minRows - constrainedRows.length;
        const emptyRow = createEmptyDefaultRow(columns);

        for (let i = 0; i < rowsToAdd; i += 1) {
            constrainedRows.push({ ...emptyRow });
        }
    }

    return constrainedRows;
};

export const rowsAreEqual = (left: unknown, right: unknown) => {
    return JSON.stringify(left ?? []) === JSON.stringify(right ?? []);
};

