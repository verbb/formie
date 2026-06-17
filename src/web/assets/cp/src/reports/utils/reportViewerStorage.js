import { normalizeDateRange } from '@reports/utils/reportViewerDates';

export const viewerColumnsStorageKey = (reportId) => `formie.reportViewer.${reportId}.columns`;
export const viewerSortStorageKey = (reportId) => `formie.reportViewer.${reportId}.sort`;
export const viewerDateRangeStorageKey = (reportId) => `formie.reportViewer.${reportId}.dateRange`;

const cloneColumns = (columns = []) => {
    return columns.map((column) => ({
        type: column.type || 'attribute',
        handle: column.handle,
        label: column.label || column.header || column.handle,
        enabled: column.enabled !== false,
    }));
};

export const loadStoredViewerColumns = (reportId, fallbackColumns) => {
    const fallback = cloneColumns(fallbackColumns);

    if (!reportId) {
        return fallback;
    }

    try {
        const stored = sessionStorage.getItem(viewerColumnsStorageKey(reportId));

        if (stored) {
            const parsed = JSON.parse(stored);

            if (Array.isArray(parsed) && parsed.length) {
                return cloneColumns(parsed);
            }
        }
    } catch {
        // Ignore invalid session storage payloads.
    }

    return fallback;
};

export const loadStoredViewerSort = (reportId, fallbackSort) => {
    if (!reportId) {
        return fallbackSort;
    }

    try {
        const stored = sessionStorage.getItem(viewerSortStorageKey(reportId));

        if (stored) {
            const parsed = JSON.parse(stored);

            if (parsed?.handle) {
                return {
                    handle: parsed.handle,
                    dir: parsed.dir === 'asc' ? 'asc' : 'desc',
                };
            }
        }
    } catch {
        // Ignore invalid session storage payloads.
    }

    return fallbackSort;
};

export const loadStoredViewerDateRange = (reportId, fallbackDateRange) => {
    const fallback = normalizeDateRange(fallbackDateRange);

    if (!reportId) {
        return fallback;
    }

    try {
        const stored = sessionStorage.getItem(viewerDateRangeStorageKey(reportId));

        if (stored) {
            const parsed = JSON.parse(stored);

            if (parsed && typeof parsed === 'object') {
                const normalized = normalizeDateRange(parsed);

                if (
                    !normalized.startDate
                    && !normalized.endDate
                    && (fallback.startDate || fallback.endDate)
                ) {
                    return fallback;
                }

                return normalized;
            }
        }
    } catch {
        // Ignore invalid session storage payloads.
    }

    return fallback;
};

export const persistViewerColumns = (reportId, nextColumns) => {
    if (!reportId) {
        return;
    }

    try {
        sessionStorage.setItem(
            viewerColumnsStorageKey(reportId),
            JSON.stringify(nextColumns),
        );
    } catch {
        // Ignore storage failures.
    }
};

export const persistViewerSort = (reportId, nextSort) => {
    if (!reportId) {
        return;
    }

    try {
        sessionStorage.setItem(viewerSortStorageKey(reportId), JSON.stringify(nextSort));
    } catch {
        // Ignore storage failures.
    }
};

export const persistViewerDateRange = (reportId, nextDateRange) => {
    if (!reportId) {
        return;
    }

    try {
        sessionStorage.setItem(
            viewerDateRangeStorageKey(reportId),
            JSON.stringify(normalizeDateRange(nextDateRange)),
        );
    } catch {
        // Ignore storage failures.
    }
};

export const clearStoredViewerPreferences = (reportId) => {
    if (!reportId) {
        return;
    }

    try {
        sessionStorage.removeItem(viewerColumnsStorageKey(reportId));
        sessionStorage.removeItem(viewerSortStorageKey(reportId));
        sessionStorage.removeItem(viewerDateRangeStorageKey(reportId));
    } catch {
        // Ignore storage failures.
    }
};

const serializeColumns = (columns = []) => {
    return JSON.stringify(cloneColumns(columns).map((column) => ({
        type: column.type,
        handle: column.handle,
        enabled: column.enabled,
    })));
};

export const hasViewerPreferenceChanges = ({
    columns,
    defaultColumns,
    sort,
    defaultSort,
    dateRange,
    defaultDateRange,
    search = '',
}) => {
    const normalizedDateRange = normalizeDateRange(dateRange);
    const normalizedDefaultDateRange = normalizeDateRange(defaultDateRange);

    if (search.trim() !== '') {
        return true;
    }
    if (serializeColumns(columns) !== serializeColumns(defaultColumns)) {
        return true;
    }

    if (sort.handle !== defaultSort.handle || sort.dir !== defaultSort.dir) {
        return true;
    }

    if (normalizedDateRange.startDate !== normalizedDefaultDateRange.startDate) {
        return true;
    }

    if (normalizedDateRange.endDate !== normalizedDefaultDateRange.endDate) {
        return true;
    }

    return false;
};

export const getViewerColumnOrderKey = (columns = []) => {
    return columns
        .map((column) => `${column.type || 'attribute'}:${column.handle}:${column.enabled ? 1 : 0}`)
        .join('|');
};

export const getViewerColumnFetchKey = (columns = []) => {
    return columns
        .filter((column) => column.enabled !== false)
        .map((column) => `${column.type || 'attribute'}:${column.handle}`)
        .sort()
        .join('|');
};

export const orderTableDataByViewerColumns = (tableData, viewerColumns = []) => {
    if (!tableData?.columns?.length || !Array.isArray(viewerColumns) || !viewerColumns.length) {
        return {
            columns: tableData?.columns || [],
            rows: tableData?.rows || [],
        };
    }

    const enabledColumns = viewerColumns.filter((column) => column.enabled);
    const serverColumnsById = new Map(
        tableData.columns.map((column) => [column.id, column]),
    );
    const serverColumnIndexById = new Map(
        tableData.columns.map((column, index) => [column.id, index]),
    );

    const columns = enabledColumns
        .map((column) => serverColumnsById.get(`${column.type || 'attribute'}:${column.handle}`))
        .filter(Boolean);

    if (columns.length !== tableData.columns.length) {
        return {
            columns: tableData.columns,
            rows: tableData.rows || [],
        };
    }

    const rows = (tableData.rows || []).map((row) => ({
        ...row,
        cells: columns.map((column) => {
            const sourceIndex = serverColumnIndexById.get(column.id);

            return row.cells[sourceIndex];
        }),
    }));

    return { columns, rows };
};

export { cloneColumns as cloneViewerColumns };
