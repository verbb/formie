export const parseViewerDate = (value) => {
    if (!value) {
        return undefined;
    }

    const dateOnlyMatch = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);

    if (dateOnlyMatch) {
        const [, year, month, day] = dateOnlyMatch;
        return new Date(Number(year), Number(month) - 1, Number(day));
    }

    const parsed = new Date(String(value));

    if (Number.isNaN(parsed.getTime())) {
        return undefined;
    }

    return parsed;
};

export const formatViewerStartDate = (date) => {
    if (!date) {
        return null;
    }

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day} 00:00:00`;
};

export const formatViewerEndDate = (date) => {
    if (!date) {
        return null;
    }

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day} 23:59:59`;
};

export const normalizeDateRange = (dateRange = {}) => ({
    startDate: dateRange.startDate || null,
    endDate: dateRange.endDate || null,
});

export const dateRangesMatch = (left, right) => {
    const a = normalizeDateRange(left);
    const b = normalizeDateRange(right);

    return a.startDate === b.startDate && a.endDate === b.endDate;
};

export const getDateRangeKey = (dateRange = {}) => {
    const normalized = normalizeDateRange(dateRange);

    return `${normalized.startDate || ''}|${normalized.endDate || ''}`;
};

export const appendViewerDateParams = (body, dateRange) => {
    body.append('startDate', dateRange.startDate || '');
    body.append('endDate', dateRange.endDate || '');
};
