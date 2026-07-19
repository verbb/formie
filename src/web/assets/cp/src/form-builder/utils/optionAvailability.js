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

/**
 * Descriptor row-menu items for Visible / Hidden / Disabled (kit EditableTable).
 * Wire with `onRowMenuSelect` → `applyOptionAvailabilityMenuSelect`.
 */
export function getOptionAvailabilityRowMenuItems(row, t) {
    if (row?.optgroup) {
        return null;
    }

    const currentValue = resolveOptionAvailabilityValue(row);

    return [
        {
            type: 'radio',
            radioGroup: 'availability',
            value: 'visible',
            label: t('Visible'),
            checked: currentValue === 'visible',
            action: 'availability',
        },
        {
            type: 'radio',
            radioGroup: 'availability',
            value: 'hidden',
            label: t('Hidden'),
            checked: currentValue === 'hidden',
            action: 'availability',
        },
        {
            type: 'radio',
            radioGroup: 'availability',
            value: 'disabled',
            label: t('Disabled'),
            checked: currentValue === 'disabled',
            action: 'availability',
        },
    ];
}

export function applyOptionAvailabilityMenuSelect(detail, updateRowAvailability) {
    if (detail?.action !== 'availability') {
        return;
    }

    updateRowAvailability(detail.rowIndex, detail.value === 'visible' ? null : detail.value);
}

export function getOptionAvailabilityRowModifier(row, t) {
    if (row?.optgroup) {
        return null;
    }

    const availability = resolveOptionAvailabilityValue(row);

    if (availability === 'hidden') {
        return {
            tone: 'warning',
            title: t('Hidden from the front-end form'),
        };
    }

    if (availability === 'disabled') {
        return {
            tone: 'muted',
            title: t('Disabled on the front-end form'),
        };
    }

    return null;
}

/** Preserve kit `_id` while updating availability (needed for custom-cell / dnd identity). */
export function patchRowAvailability(row, availability) {
    const next = { ...(row && typeof row === 'object' ? row : {}) };
    delete next.disabled;

    if (availability) {
        next.availability = availability;
    } else {
        delete next.availability;
    }

    return next;
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
