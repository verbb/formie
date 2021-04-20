function canDrag(sourcePageIndex, sourceField, dragData) {
    // Nesting repeater fields is not allowed!
    if (sourceField && dragData.supportsNested) {
        return false;
    }

    // All other fields are okay.
    if (dragData.trigger === 'pill') {
        return true;
    }

    // Disallow moving a field from the main form into a repeater.
    if (sourceField && dragData.pageIndex >= 0) {
        return false;
    }

    // Disallow moving a field from a repeater into the main form.
    if (sourcePageIndex >= 0 && dragData.pageIndex < 0) {
        return false;
    }

    // Disallow moving a field from inside one repeater to another repeater.
    if (dragData.fieldId && sourceField && sourceField.supportsNested) {
        if (dragData.fieldId != sourceField.vid) {
            return false;
        }
    }

    return true;
}

export {
    canDrag,
};
