import { columnKey } from '@reports/utils/reportColumnKey';
import { formatViewerEndDate, formatViewerStartDate } from '@reports/utils/reportViewerDates';

const STORAGE_PREFIX = 'formie.submissionExport';

// Browser storage can be unavailable or full. Preferences are a convenience, so carry on without them.
const readStorage = (key) => {
    try {
        return JSON.parse(window.localStorage.getItem(key));
    } catch {
        return null;
    }
};

const writeStorage = (key, value) => {
    try {
        if (value === null) {
            window.localStorage.removeItem(key);
        } else {
            window.localStorage.setItem(key, JSON.stringify(value));
        }
    } catch {
        // See readStorage().
    }
};

const getIncludeStates = (isIncomplete, isSpam) => {
    if (isIncomplete === false && isSpam === false) {
        return { includeComplete: true, includeIncomplete: false, includeSpam: false };
    }

    if (isIncomplete === true) {
        return { includeComplete: false, includeIncomplete: true, includeSpam: false };
    }

    if (isSpam === true) {
        return { includeComplete: false, includeIncomplete: false, includeSpam: true };
    }

    return { includeComplete: true, includeIncomplete: true, includeSpam: true };
};

/**
 * Translates what the Submissions index is showing into the dialog's starting values.
 */
export const getSubmissionExportDefaults = ({
    sourceKey,
    isIncomplete = null,
    isSpam = null,
    startDate = null,
    endDate = null,
    search = null,
    status = null,
    siteId = null,
    sortAttribute = null,
    sortDirection = null,
    hasActiveFilter = false,
}) => {
    const formMatch = /^form:(\d+)$/.exec(sourceKey || '');

    return {
        formIds: formMatch ? [parseInt(formMatch[1], 10)] : '*',
        ...getIncludeStates(isIncomplete, isSpam),
        dateRange: {
            startDate: formatViewerStartDate(startDate),
            endDate: formatViewerEndDate(endDate),
        },
        search: search || '',
        status: status || null,
        siteId: siteId ? String(siteId) : '',
        sort: {
            handle: String(sortAttribute || 'dateCreated').replace(/^elements\./, ''),
            dir: sortDirection === 'asc' ? 'asc' : 'desc',
        },
        // Custom sources and condition filters have no report equivalent.
        hasUncarriedFilters: Boolean(hasActiveFilter) || (!formMatch && sourceKey !== '*'),
    };
};

export const getSubmissionTypes = ({ includeComplete, includeIncomplete, includeSpam }) => {
    return [
        includeComplete ? 'complete' : null,
        includeIncomplete ? 'incomplete' : null,
        includeSpam ? 'spam' : null,
    ].filter(Boolean);
};

export const resolveStatusIds = (statusHandle, statuses = []) => {
    const match = statuses.find((status) => {return status.handle === statusHandle});

    return match ? [match.value] : [];
};

export const getSubmissionExportParams = (values) => {return {
    formIds: values.formIds,
    includeComplete: values.submissionTypes.includes('complete'),
    includeIncomplete: values.submissionTypes.includes('incomplete'),
    includeSpam: values.submissionTypes.includes('spam'),
    statusIds: values.statusIds,
    siteId: values.siteId,
}};

export const getFormSelectionKey = (formIds) => {
    return Array.isArray(formIds) ? [...formIds].sort((a, b) => {return a - b}).join(',') : String(formIds);
};

/**
 * Keeps someone's order and choices for columns that still exist, and adds new ones
 * with the server's default so a field added to a form is never silently left out.
 */
export const mergeExportColumns = (currentColumns = [], nextColumns = []) => {
    const nextByKey = new Map(nextColumns.map((column) => {return [columnKey(column), column]}));
    const merged = [];

    currentColumns.forEach((column) => {
        const next = nextByKey.get(columnKey(column));

        if (next) {
            merged.push({ ...next, enabled: Boolean(column.enabled) });
            nextByKey.delete(columnKey(column));
        }
    });

    return [...merged, ...nextByKey.values()];
};

/**
 * Splits columns into submission details and one section per form, keeping their current
 * order. Forms sharing a field handle share one column, so it appears in each of them.
 */
export const groupExportColumns = (columns = [], formGroups = []) => {
    const sections = [{
        key: 'attributes',
        label: null,
        columns: columns.filter((column) => {return column.type !== 'field'}),
    }];
    const placedHandles = new Set();

    formGroups.forEach((group) => {
        const handles = new Set(group.handles);
        const formColumns = columns.filter((column) => {return column.type === 'field' && handles.has(column.handle)});

        formColumns.forEach((column) => {return placedHandles.add(column.handle)});

        if (formColumns.length) {
            sections.push({ key: `form:${group.formId}`, label: group.formTitle, columns: formColumns });
        }
    });

    const otherColumns = columns.filter((column) => {return column.type === 'field' && !placedHandles.has(column.handle)});

    if (otherColumns.length) {
        sections.push({ key: 'other', label: null, columns: otherColumns });
    }

    return sections.filter((section) => {return section.columns.length});
};

/**
 * Applies a section's edits to the full list. Its columns keep their positions among the
 * others, so reordering one section can't move columns from another.
 */
export const applySectionColumns = (columns, sectionColumns, nextSectionColumns) => {
    const sectionKeys = new Set(sectionColumns.map(columnKey));
    const next = [...nextSectionColumns];
    const enabledByKey = new Map(nextSectionColumns.map((column) => {return [columnKey(column), column.enabled]}));

    return columns.map((column) => {return (sectionKeys.has(columnKey(column)) ? next.shift() : column)})
        .map((column) => {return (enabledByKey.has(columnKey(column)) ? { ...column, enabled: enabledByKey.get(columnKey(column)) } : column)});
};

export const setColumnsEnabled = (columns, targetColumns, enabled) => {
    const targetKeys = new Set(targetColumns.map(columnKey));

    return columns.map((column) => {return (targetKeys.has(columnKey(column)) ? { ...column, enabled } : column)});
};

export const loadStoredExportFormat = (formats) => {
    const stored = readStorage(`${STORAGE_PREFIX}.format`);

    return formats.some((format) => {return format.value === stored}) ? stored : formats[0].value;
};

export const persistExportFormat = (format) => {
    writeStorage(`${STORAGE_PREFIX}.format`, format);
};

export const loadStoredExportColumns = (formIds) => {
    const stored = readStorage(`${STORAGE_PREFIX}.columns.${getFormSelectionKey(formIds)}`);

    return Array.isArray(stored) ? stored : [];
};

export const persistExportColumns = (formIds, columns) => {
    writeStorage(`${STORAGE_PREFIX}.columns.${getFormSelectionKey(formIds)}`, columns?.map((column) => {return {
        type: column.type,
        handle: column.handle,
        enabled: Boolean(column.enabled),
    }}) ?? null);
};
