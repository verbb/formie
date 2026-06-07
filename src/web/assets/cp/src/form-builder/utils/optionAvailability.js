export function resolveOptionAvailability(row) {
    const availability = row?.availability;

    if (availability === 'hidden' || availability === 'disabled') {
        return availability;
    }

    if (row?.disabled === true) {
        return 'hidden';
    }

    return null;
}

export function resolveOptionAvailabilityValue(row) {
    return resolveOptionAvailability(row) || 'visible';
}

export function isOptionHidden(row) {
    return resolveOptionAvailability(row) === 'hidden';
}

export function isOptionFrontEndDisabled(row) {
    return resolveOptionAvailability(row) === 'disabled';
}

export function filterFrontEndOptions(options) {
    if (!Array.isArray(options)) {
        return [];
    }

    return options.filter((option) => {
        if (option?.optgroup) {
            return true;
        }

        return !isOptionHidden(option);
    });
}

export function applyOptionAvailabilityToPreviewOptions(options) {
    return filterFrontEndOptions(options).map((option) => {
        if (option?.optgroup) {
            return option;
        }

        return {
            ...option,
            disabled: isOptionFrontEndDisabled(option),
        };
    });
}
