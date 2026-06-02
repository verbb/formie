export const normalizeOptions = (options) => {
    if (!Array.isArray(options)) {
        return [];
    }

    return options.map((option) => {
        if (!option || typeof option !== 'object') {
            return {
                label: String(option ?? ''),
                value: String(option ?? ''),
            };
        }

        return {
            ...option,
            label: option.label ?? String(option.value ?? ''),
            value: option.value ?? '',
        };
    });
};

export const normalizeSelectedValues = (value, options = [], useOptionDefaults = true) => {
    if (Array.isArray(value)) {
        return value.map((entry) => {
            if (entry && typeof entry === 'object' && 'value' in entry) {
                return String(entry.value);
            }

            return String(entry);
        });
    }

    if (value && typeof value === 'object' && 'value' in value) {
        return [String(value.value)];
    }

    if (value === null || value === undefined || value === '') {
        if (!useOptionDefaults) {
            return [];
        }

        return options
            .filter((option) => { return Boolean(option?.isDefault); })
            .map((option) => { return String(option.value ?? ''); });
    }

    return [String(value)];
};

export const extractTableCellValue = (rawValue) => {
    if (rawValue && typeof rawValue === 'object' && !Array.isArray(rawValue) && ('value' in rawValue)) {
        return rawValue.value;
    }

    return rawValue;
};

export const toTableDisplayValue = (rawValue) => {
    const value = extractTableCellValue(rawValue);

    if (value === null || value === undefined) {
        return '';
    }

    if (typeof value === 'boolean') {
        return value ? '1' : '';
    }

    return String(value);
};
