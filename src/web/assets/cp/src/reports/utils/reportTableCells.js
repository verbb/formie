const parseReportDate = (value) => {
    if (!value) {
        return null;
    }

    const date = new Date(String(value));

    if (Number.isNaN(date.getTime())) {
        return null;
    }

    return date;
};

export const formatReportDateTime = (value) => {
    const date = parseReportDate(value);

    if (!date) {
        return null;
    }

    if (typeof Craft !== 'undefined' && typeof Craft.formatDate === 'function') {
        const datePart = Craft.formatDate(date);
        const timePart = date.toLocaleTimeString(Craft.locale || undefined, {
            hour: 'numeric',
            minute: '2-digit',
        });

        return `${datePart}, ${timePart}`;
    }

    return date.toLocaleString(Craft?.locale || undefined);
};

export const formatReportTextCell = (value) => {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (typeof value === 'boolean') {
        return value ? Craft.t('app', 'Yes') : Craft.t('app', 'No');
    }

    return String(value);
};
