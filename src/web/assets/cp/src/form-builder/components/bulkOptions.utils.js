export const buildBulkPreview = (items = [], labelOption, valueOption) => {
    return items.map((item) => {
        const labelValue = labelOption && item && typeof item === 'object'
            ? String(item[labelOption] ?? '')
            : String(item ?? '');

        const valueValue = valueOption && item && typeof item === 'object'
            ? String(item[valueOption] ?? '')
            : String(item ?? '');

        if (labelOption === valueOption) {
            return labelValue;
        }

        return `${labelValue}|${valueValue}`;
    }).join('\n');
};

export const parseBulkPreviewRows = (preview = '') => {
    return preview
        .split('\n')
        .map((line) => { return line.trim(); })
        .filter((line) => { return line !== ''; })
        .map((line) => {
            const [labelRaw, ...valueParts] = line.split('|');
            const label = String(labelRaw ?? '').trim();
            const value = valueParts.length > 0
                ? valueParts.join('|').trim()
                : label;

            return { label, value };
        });
};
