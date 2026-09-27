// Keep preceding layout content, including preceding siblings inside the edited parent.
export function precedingConditionValues(values, currentId) {
    if (!currentId) return values;
    let found = false;
    const rowsBefore = (rows = []) => rows.map((row) => ({ ...row, fields: (row.fields || []).flatMap((field) => {
        if (found) return [];
        if ([field._id, field.id, field.uid].includes(currentId)) { found = true; return []; }
        const childRows = field.rows || field.settings?.rows;
        if (!Array.isArray(childRows)) return [field];
        const rows = rowsBefore(childRows);
        return [{ ...field, rows, settings: field.settings ? { ...field.settings, rows } : field.settings }];
    }) }));
    const pages = (values.pages || []).map((page) => ({ ...page, rows: rowsBefore(page.rows) }));
    return found ? { ...values, pages } : { ...values, pages: [] };
}
