import { cloneDeep, get as getValue, set as setValue } from 'lodash-es';

import { MAX_FIELDS_PER_ROW } from '@form-builder/builder/constants';

const createRow = (fields) => {
    return {
        _id: Math.random().toString(36).slice(2),
        fields,
    };
};

const getActiveFieldType = (activeData) => {
    return activeData?.field?.type || activeData?.fieldType?.type || null;
};

export const isTypeAllowedInNestedContainer = ({ activeData, allowedFieldTypes = [] }) => {
    if (!activeData) {
        return true;
    }

    if (!Array.isArray(allowedFieldTypes) || allowedFieldTypes.length === 0) {
        return true;
    }

    const activeType = getActiveFieldType(activeData);
    if (!activeType) {
        return false;
    }

    return allowedFieldTypes.includes(activeType);
};

export const canDropInNestedContainer = ({
    activeData,
    isRepeater,
    pageIndex,
    rowIndex,
    fieldIndex,
    allowedFieldTypes = [],
}) => {
    if (!activeData) {
        return true;
    }

    if (!isTypeAllowedInNestedContainer({ activeData, allowedFieldTypes })) {
        return false;
    }

    if (activeData.isNew) {
        return true;
    }

    const isSameParent = Boolean(
        activeData.pageIndex === pageIndex
        && activeData.rowIndex === rowIndex
        && activeData.fieldIndex === fieldIndex,
    );

    if (activeData.source === 'top-level') {
        return !isRepeater;
    }

    if (activeData.source === 'nested' && activeData.isRepeatableParentField) {
        return isRepeater && isSameParent;
    }

    if (activeData.source === 'nested') {
        return !isRepeater;
    }

    return true;
};

export const canDropToTopLevel = ({ activeData }) => {
    return !(activeData?.source === 'nested' && activeData?.isRepeatableParentField);
};

export const isAllowedNestedTargetDrop = ({
    fieldData,
    targetIsRepeater,
    isSameNestedParent,
    allowedFieldTypes = [],
}) => {
    if (!isTypeAllowedInNestedContainer({ activeData: fieldData, allowedFieldTypes })) {
        return false;
    }

    const sourceIsNested = fieldData?.source === 'nested';
    const sourceIsRepeaterNested = sourceIsNested && Boolean(fieldData?.isRepeatableParentField);

    if (targetIsRepeater && !fieldData?.isNew) {
        return sourceIsRepeaterNested && isSameNestedParent;
    }

    if (sourceIsRepeaterNested && !targetIsRepeater) {
        return false;
    }

    return true;
};

export const moveNestedFieldWithinParentInPages = ({
    pages,
    pageIndex,
    rowIndex,
    fieldIndex,
    fromNestedRowIndex,
    fromNestedFieldIndex,
    toNestedRowIndex,
    toNestedFieldIndex,
    sourceNestedFieldId = null,
    asNewRow = false,
}) => {
    const nextPages = cloneDeep(pages);
    let resolvedFromNestedRowIndex = fromNestedRowIndex;
    let resolvedFromNestedFieldIndex = fromNestedFieldIndex;

    if (sourceNestedFieldId) {
        const nestedRowsPath = `pages.${pageIndex}.rows.${rowIndex}.fields.${fieldIndex}.rows`;
        const nestedRows = getValue(nextPages, nestedRowsPath);

        if (Array.isArray(nestedRows)) {
            nestedRows.some((nestedRow, nestedRowIdx) => {
                const nestedFields = Array.isArray(nestedRow?.fields) ? nestedRow.fields : [];
                const nestedFieldIdx = nestedFields.findIndex((candidate) => {
                    return candidate?._id === sourceNestedFieldId;
                });

                if (nestedFieldIdx === -1) {
                    return false;
                }

                resolvedFromNestedRowIndex = nestedRowIdx;
                resolvedFromNestedFieldIndex = nestedFieldIdx;
                return true;
            });
        }
    }

    const sourceFieldPath = `pages.${pageIndex}.rows.${rowIndex}.fields.${fieldIndex}.rows.${resolvedFromNestedRowIndex}.fields.${resolvedFromNestedFieldIndex}`;
    const fieldToMove = getValue(nextPages, sourceFieldPath);
    let targetNestedRowIndex = toNestedRowIndex;
    const isOriginalSameRowMove = resolvedFromNestedRowIndex === toNestedRowIndex;

    if (!fieldToMove) {
        return nextPages;
    }

    const sourceFieldsPath = `pages.${pageIndex}.rows.${rowIndex}.fields.${fieldIndex}.rows.${resolvedFromNestedRowIndex}.fields`;
    const sourceFields = getValue(nextPages, sourceFieldsPath);
    if (!Array.isArray(sourceFields)) {
        return nextPages;
    }

    // Lateral same-row moves with a single field are effectively a no-op.
    // If we remove first, the source row disappears and reinsertion cannot resolve.
    if (!asNewRow && resolvedFromNestedRowIndex === toNestedRowIndex && sourceFields.length === 1) {
        return nextPages;
    }

    const originalTargetFields = getValue(nextPages, `pages.${pageIndex}.rows.${rowIndex}.fields.${fieldIndex}.rows.${toNestedRowIndex}.fields`);
    if (!asNewRow && !isOriginalSameRowMove && Array.isArray(originalTargetFields) && originalTargetFields.length >= MAX_FIELDS_PER_ROW) {
        return nextPages;
    }

    const sourceRowWillBeRemoved = sourceFields.length === 1;
    const nextSourceFields = [...sourceFields];
    nextSourceFields.splice(resolvedFromNestedFieldIndex, 1);
    setValue(nextPages, sourceFieldsPath, nextSourceFields);

    if (nextSourceFields.length === 0) {
        const rowsPath = `pages.${pageIndex}.rows.${rowIndex}.fields.${fieldIndex}.rows`;
        const rows = getValue(nextPages, rowsPath);

        if (Array.isArray(rows)) {
            const nextRows = [...rows];
            nextRows.splice(resolvedFromNestedRowIndex, 1);
            setValue(nextPages, rowsPath, nextRows);

            if (sourceRowWillBeRemoved && resolvedFromNestedRowIndex <= targetNestedRowIndex) {
                // Allow -1 sentinel so "drop to immediate bottom of itself" remains a no-op
                // after removing the source row.
                targetNestedRowIndex = targetNestedRowIndex - 1;
            }
        }
    }

    const targetRowsPath = `pages.${pageIndex}.rows.${rowIndex}.fields.${fieldIndex}.rows`;
    const targetRows = getValue(nextPages, targetRowsPath);
    const nextTargetRows = Array.isArray(targetRows) ? [...targetRows] : [];

    if (asNewRow) {
        const rowInsertIndex = targetNestedRowIndex === -1 ? 0 : targetNestedRowIndex + 1;
        nextTargetRows.splice(rowInsertIndex, 0, {
            ...createRow([fieldToMove]),
        });
        setValue(nextPages, targetRowsPath, nextTargetRows);
        return nextPages;
    }

    const targetFieldsPath = `${targetRowsPath}.${targetNestedRowIndex}.fields`;
    const targetFields = getValue(nextPages, targetFieldsPath);
    if (!Array.isArray(targetFields)) {
        return nextPages;
    }

    const nextTargetFields = [...targetFields];
    const insertIndex = toNestedFieldIndex === -1 ? 0 : toNestedFieldIndex + 1;

    if (isOriginalSameRowMove && resolvedFromNestedFieldIndex <= toNestedFieldIndex) {
        nextTargetFields.splice(Math.max(0, insertIndex - 1), 0, fieldToMove);
        setValue(nextPages, targetFieldsPath, nextTargetFields);
        return nextPages;
    }

    nextTargetFields.splice(insertIndex, 0, fieldToMove);
    setValue(nextPages, targetFieldsPath, nextTargetFields);
    return nextPages;
};

export const moveTopLevelFieldToNestedInPages = ({
    pages,
    fromPageIndex,
    fromRowIndex,
    fromFieldIndex,
    toPageIndex,
    toRowIndex,
    toFieldIndex,
    toNestedRowIndex,
    toNestedFieldIndex,
    asNewRow = false,
}) => {
    const nextPages = cloneDeep(pages);
    const fieldToMove = getValue(nextPages, `pages.${fromPageIndex}.rows.${fromRowIndex}.fields.${fromFieldIndex}`);
    let targetFieldIndex = toFieldIndex;
    let targetRowIndex = toRowIndex;

    if (!fieldToMove) {
        return nextPages;
    }

    if (fromPageIndex === toPageIndex && fromRowIndex === toRowIndex && fromFieldIndex < toFieldIndex) {
        targetFieldIndex = Math.max(0, toFieldIndex - 1);
    }

    const sourceRowFieldsPath = `pages.${fromPageIndex}.rows.${fromRowIndex}.fields`;
    const sourceRowFields = getValue(nextPages, sourceRowFieldsPath);
    const nextSourceRowFields = Array.isArray(sourceRowFields) ? [...sourceRowFields] : [];
    nextSourceRowFields.splice(fromFieldIndex, 1);
    setValue(nextPages, sourceRowFieldsPath, nextSourceRowFields);

    if (nextSourceRowFields.length === 0) {
        const sourceRowsPath = `pages.${fromPageIndex}.rows`;
        const sourceRows = getValue(nextPages, sourceRowsPath);
        if (Array.isArray(sourceRows)) {
            const nextRows = [...sourceRows];
            nextRows.splice(fromRowIndex, 1);
            setValue(nextPages, sourceRowsPath, nextRows);

            if (fromPageIndex === toPageIndex && fromRowIndex < targetRowIndex) {
                targetRowIndex = Math.max(0, targetRowIndex - 1);
            }
        }
    }

    const targetRowsPath = `pages.${toPageIndex}.rows.${targetRowIndex}.fields.${targetFieldIndex}.rows`;
    const targetRows = getValue(nextPages, targetRowsPath);
    const nextTargetRows = Array.isArray(targetRows) ? [...targetRows] : [];

    if (asNewRow) {
        const rowInsertIndex = toNestedRowIndex === -1 ? 0 : toNestedRowIndex + 1;
        nextTargetRows.splice(rowInsertIndex, 0, createRow([fieldToMove]));
        setValue(nextPages, targetRowsPath, nextTargetRows);
        return nextPages;
    }

    const targetFieldsPath = `${targetRowsPath}.${toNestedRowIndex}.fields`;
    const targetFields = getValue(nextPages, targetFieldsPath);
    if (!Array.isArray(targetFields)) {
        return nextPages;
    }

    const nextTargetFields = [...targetFields];
    const insertIndex = toNestedFieldIndex === -1 ? 0 : toNestedFieldIndex + 1;
    nextTargetFields.splice(insertIndex, 0, fieldToMove);
    setValue(nextPages, targetFieldsPath, nextTargetFields);
    return nextPages;
};

export const moveNestedFieldToTopLevelInPages = ({
    pages,
    fromPageIndex,
    fromRowIndex,
    fromFieldIndex,
    fromNestedRowIndex,
    fromNestedFieldIndex,
    toPageIndex,
    toRowIndex,
    toFieldIndex,
    asNewRow = false,
}) => {
    const nextPages = cloneDeep(pages);
    const nestedFieldPath = `pages.${fromPageIndex}.rows.${fromRowIndex}.fields.${fromFieldIndex}.rows.${fromNestedRowIndex}.fields.${fromNestedFieldIndex}`;
    const fieldToMove = getValue(nextPages, nestedFieldPath);

    if (!fieldToMove) {
        return nextPages;
    }

    const sourceNestedFieldsPath = `pages.${fromPageIndex}.rows.${fromRowIndex}.fields.${fromFieldIndex}.rows.${fromNestedRowIndex}.fields`;
    const sourceNestedFields = getValue(nextPages, sourceNestedFieldsPath);

    if (Array.isArray(sourceNestedFields)) {
        const nextNestedFields = [...sourceNestedFields];
        nextNestedFields.splice(fromNestedFieldIndex, 1);
        setValue(nextPages, sourceNestedFieldsPath, nextNestedFields);

        if (nextNestedFields.length === 0) {
            const sourceNestedRowsPath = `pages.${fromPageIndex}.rows.${fromRowIndex}.fields.${fromFieldIndex}.rows`;
            const sourceNestedRows = getValue(nextPages, sourceNestedRowsPath);
            if (Array.isArray(sourceNestedRows)) {
                const nextNestedRows = [...sourceNestedRows];
                nextNestedRows.splice(fromNestedRowIndex, 1);
                setValue(nextPages, sourceNestedRowsPath, nextNestedRows);
            }
        }
    }

    if (asNewRow) {
        const rowsPath = `pages.${toPageIndex}.rows`;
        const rows = getValue(nextPages, rowsPath);
        if (!Array.isArray(rows)) {
            return nextPages;
        }

        const insertRowIndex = toRowIndex === -1 ? 0 : toRowIndex + 1;
        rows.splice(insertRowIndex, 0, createRow([fieldToMove]));
        setValue(nextPages, rowsPath, rows);
        return nextPages;
    }

    const targetFieldsPath = `pages.${toPageIndex}.rows.${toRowIndex}.fields`;
    const targetFields = getValue(nextPages, targetFieldsPath);
    if (!Array.isArray(targetFields)) {
        return nextPages;
    }

    const nextTargetFields = [...targetFields];
    const insertIndex = toFieldIndex === -1 ? 0 : toFieldIndex + 1;
    nextTargetFields.splice(insertIndex, 0, fieldToMove);
    setValue(nextPages, targetFieldsPath, nextTargetFields);
    return nextPages;
};
