/**
 * Editable table cell edits are flushed asynchronously via requestAnimationFrame
 * (EditableTable -> onCellChange -> schema form setFieldValue). Submitting the
 * schema form in the same tick can capture stale table values.
 */
export const submitSchemaFormAfterPendingTableUpdates = (form) => {
    if (!form?.handleSubmit) {
        return;
    }

    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            form.handleSubmit();
        });
    });
};
