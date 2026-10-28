export const FIELD_ORDER_LAYOUT = 'layout';
export const FIELD_ORDER_ALPHABETICAL = 'alphabetical';
export const FIELD_ORDER_CUSTOM = 'custom';

export const inferFieldOrder = (display = {}) => {
    if (
        [
            FIELD_ORDER_LAYOUT,
            FIELD_ORDER_ALPHABETICAL,
            FIELD_ORDER_CUSTOM,
        ].includes(display.fieldOrder)
    ) {
        return display.fieldOrder;
    }

    return display.fieldColumnsMode === 'selected'
        ? FIELD_ORDER_CUSTOM
        : FIELD_ORDER_LAYOUT;
};

export const getLayoutFieldColumns = (groups = []) => {
    const fields = new Map();

    [...groups]
        .sort((a, b) => {
            return Number(a.formId) - Number(b.formId);
        })
        .forEach((group) => {
            (group.columns || []).forEach((column) => {
                if (!fields.has(column.handle)) {
                    fields.set(column.handle, column);
                }
            });
        });

    return [...fields.values()];
};

export const getAutomaticFieldColumns = (columns, mode = 'combined') => {
    const normalized = ['combined', 'subfields', 'both'].includes(mode)
        ? mode
        : 'combined';
    const parents = new Set(
        columns
            .filter((column) => {
                return column.handle.includes('.');
            })
            .map((column) => {
                return column.handle.split('.')[0];
            }),
    );

    // Fields without declared report subcolumns keep their ordinary columns.
    return columns.filter((column) => {
        return column.handle.includes('.')
            ? normalized !== 'combined'
            : normalized !== 'subfields' || !parents.has(column.handle);
    });
};

export const expandAllReportFields = (
    columns,
    available,
    compoundFieldColumns = 'combined',
) => {
    const remaining = new Map(
        getAutomaticFieldColumns(available, compoundFieldColumns).map(
            (column) => {
                return [column.handle, { ...column, enabled: true }];
            },
        ),
    );
    const expanded = [];

    columns.forEach((column) => {
        if (column.type !== 'field') {
            expanded.push(column);
        } else if (remaining.has(column.handle)) {
            expanded.push({
                ...remaining.get(column.handle),
                ...column,
                enabled: true,
            });
            remaining.delete(column.handle);
        }
    });

    return [...expanded, ...remaining.values()];
};

export const orderReportFields = (columns, order, available = []) => {
    if (order === FIELD_ORDER_CUSTOM) {
        return columns;
    }

    const positions = new Map(
        available.map((column, index) => {
            return [column.handle, index];
        }),
    );
    const fields = columns
        .filter((column) => {
            return column.type === 'field';
        })
        .sort((a, b) => {
            if (order === FIELD_ORDER_ALPHABETICAL) {
                const left = String(a.label ?? a.handle).toLowerCase();
                const right = String(b.label ?? b.handle).toLowerCase();

                if (left === right) {
                    return 0;
                }

                return left < right ? -1 : 1;
            }

            return (
                (positions.get(a.handle) ?? Number.MAX_SAFE_INTEGER) -
                (positions.get(b.handle) ?? Number.MAX_SAFE_INTEGER)
            );
        });
    let index = 0;

    // Attributes keep their slots when the selected fields are sorted automatically.
    return columns.map((column) => {
        return column.type === 'field' ? fields[index++] : column;
    });
};
