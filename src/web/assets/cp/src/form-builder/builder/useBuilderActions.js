import { cloneDeep, get as getValue, set as setValue } from 'lodash-es';

import {
    createItem,
    normalizeCollection,
    updateItem,
    deleteItem,
    duplicateItem,
} from '@verbb/plugin-kit-react/utils';
import {
    addAt,
    insertAt,
    removeAt,
    updateAt,
    moveAt,
} from '@utils';

import { useFormBuilderForm } from '@form-builder/contexts/FormBuilderFormContext';
import { MAX_FIELDS_PER_ROW } from '@form-builder/builder/constants';
import {
    moveNestedFieldWithinParentInPages,
    moveTopLevelFieldToNestedInPages,
    moveNestedFieldToTopLevelInPages,
} from './nestedMoveUtils';
import { assignFieldReferences } from '@form-builder/utils/fieldReferences';

const useBuilderActions = () => {
    const { form, values } = useFormBuilderForm();

    const getPages = () => {
        return values?.pages || [];
    };

    const setPages = (pages) => {
        form.setFieldValue('pages', pages);
    };

    const updatePages = (newPages) => {
        setPages(newPages);
    };

    const addPage = (pageData = {}) => {
        const newPage = {
            ...createItem(pageData),
            rows: [],
        };

        setPages(addAt(getPages(), '', newPage));
    };

    const updatePage = (pageIndex, updates) => {
        setPages(updateAt(getPages(), `${pageIndex}`, updates));
    };

    const deletePage = (pageIndex) => {
        setPages(removeAt(getPages(), '', pageIndex));
    };

    const duplicatePage = (pageIndex, transformCallback = null) => {
        const pageToDuplicate = getValue(getPages(), `${pageIndex}`);
        const clonedPage = cloneDeep(pageToDuplicate);

        const newPage = {
            ...createItem(clonedPage),
            rows: clonedPage.rows.map((row) => {
                return {
                    ...createItem(row),
                    fields: row.fields.map((field) => {
                        return createItem(cloneDeep(field));
                    }),
                };
            }),
        };

        if (transformCallback) {
            transformCallback(newPage);
        }

        setPages(insertAt(getPages(), '', newPage, pageIndex + 1));
    };

    const movePage = (fromIndex, toIndex) => {
        setPages(moveAt(getPages(), '', fromIndex, toIndex));
    };

    const updateField = (pageIndex, rowIndex, fieldIndex, updates) => {
        setPages(updateAt(getPages(), `${pageIndex}.rows.${rowIndex}.fields.${fieldIndex}`, updates));
    };

    const deleteField = (pageIndex, rowIndex, fieldIndex) => {
        let pages = removeAt(getPages(), `${pageIndex}.rows.${rowIndex}.fields`, fieldIndex);

        const remainingFields = getValue(pages, `${pageIndex}.rows.${rowIndex}.fields`);
        if (remainingFields && remainingFields.length === 0) {
            pages = removeAt(pages, `${pageIndex}.rows`, rowIndex);
        }

        setPages(pages);
    };

    const duplicateField = (pageIndex, rowIndex, fieldIndex, transformCallback = null) => {
        const fieldToDuplicate = getValue(getPages(), `${pageIndex}.rows.${rowIndex}.fields.${fieldIndex}`);
        const newField = createItem(assignFieldReferences(cloneDeep(fieldToDuplicate), { forceNew: true }));

        if (transformCallback) {
            transformCallback(newField);
        }

        setPages(insertAt(getPages(), `${pageIndex}.rows.${rowIndex}.fields`, newField, fieldIndex + 1));
    };

    const addFieldToPage = (pageIndex, fieldData) => {
        const newRow = {
            ...createItem({}),
            fields: [createItem(assignFieldReferences(fieldData))],
        };

        const pages = cloneDeep(getPages());
        const rowsPath = `${pageIndex}.rows`;
        const currentRows = getValue(pages, rowsPath);

        if (!Array.isArray(currentRows)) {
            setValue(pages, rowsPath, []);
        }

        const updatedPages = addAt(pages, rowsPath, newRow);
        setPages(updatedPages);
    };

    const addFieldToLastPositionOnPage = (pageIndex, fieldData) => {
        const pages = getPages();
        const pageRows = getValue(pages, `${pageIndex}.rows`) || [];

        if (pageRows.length === 0) {
            addFieldToPage(pageIndex, fieldData);
            return;
        }

        const lastRowIndex = pageRows.length - 1;
        const lastRowFields = pageRows[lastRowIndex]?.fields || [];
        const lastFieldIndex = Math.max(-1, lastRowFields.length - 1);

        addFieldBetweenFields(pageIndex, lastRowIndex, lastFieldIndex, fieldData);
    };

    const addFieldBetweenFields = (pageIndex, rowIndex, fieldIndex, fieldData) => {
        const newField = createItem(assignFieldReferences(fieldData));
        setPages(insertAt(getPages(), `${pageIndex}.rows.${rowIndex}.fields`, newField, fieldIndex + 1));
    };

    const addFieldBetweenRows = (pageIndex, rowIndex, fieldData) => {
        const newRow = {
            ...createItem({}),
            fields: [createItem(assignFieldReferences(fieldData))],
        };

        setPages(insertAt(getPages(), `${pageIndex}.rows`, newRow, rowIndex + 1));
    };

    const moveFieldToPosition = (fromPageIndex, fromRowIndex, fromFieldIndex, toPageIndex, toRowIndex, toFieldIndex) => {
        const fieldToMove = getValue(getPages(), `${fromPageIndex}.rows.${fromRowIndex}.fields.${fromFieldIndex}`);
        if (!fieldToMove) {
            return;
        }

        let insertionIndex = toFieldIndex === -1 ? 0 : toFieldIndex + 1;

        if (fromPageIndex === toPageIndex && fromRowIndex === toRowIndex) {
            if (fromFieldIndex === insertionIndex) {
                return;
            }

            let pages = removeAt(getPages(), `${fromPageIndex}.rows.${fromRowIndex}.fields`, fromFieldIndex);
            if (fromFieldIndex < insertionIndex) {
                insertionIndex = insertionIndex - 1;
            }

            pages = insertAt(pages, `${toPageIndex}.rows.${toRowIndex}.fields`, fieldToMove, insertionIndex);
            setPages(pages);
            return;
        }

        const targetRowFields = getValue(getPages(), `${toPageIndex}.rows.${toRowIndex}.fields`) || [];
        if (targetRowFields.length >= MAX_FIELDS_PER_ROW) {
            return;
        }

        let pages = removeAt(getPages(), `${fromPageIndex}.rows.${fromRowIndex}.fields`, fromFieldIndex);
        pages = insertAt(pages, `${toPageIndex}.rows.${toRowIndex}.fields`, fieldToMove, insertionIndex);

        // Keep top-level row structure clean when moving across rows/pages.
        // Without this, source rows can become empty "dead" rows after drag/drop moves.
        const sourceRowFields = getValue(pages, `${fromPageIndex}.rows.${fromRowIndex}.fields`);
        if (sourceRowFields && sourceRowFields.length === 0) {
            pages = removeAt(pages, `${fromPageIndex}.rows`, fromRowIndex);
        }

        setPages(pages);
    };

    const moveFieldToLastPositionOnPage = (fromPageIndex, fromRowIndex, fromFieldIndex, toPageIndex) => {
        const pages = getPages();
        const targetRows = getValue(pages, `${toPageIndex}.rows`) || [];

        if (targetRows.length === 0) {
            moveFieldToNewRow(fromPageIndex, fromRowIndex, fromFieldIndex, toPageIndex, -1);
            return;
        }

        const lastRowIndex = targetRows.length - 1;
        const lastRowFields = targetRows[lastRowIndex]?.fields || [];
        const lastFieldIndex = Math.max(-1, lastRowFields.length - 1);

        moveFieldToPosition(
            fromPageIndex,
            fromRowIndex,
            fromFieldIndex,
            toPageIndex,
            lastRowIndex,
            lastFieldIndex,
        );
    };

    const moveFieldToNewRow = (fromPageIndex, fromRowIndex, fromFieldIndex, toPageIndex, toRowDropzoneIndex) => {
        const fieldToMove = getValue(getPages(), `${fromPageIndex}.rows.${fromRowIndex}.fields.${fromFieldIndex}`);
        if (!fieldToMove) {
            return;
        }

        const insertionIndex = toRowDropzoneIndex === -1 ? 0 : toRowDropzoneIndex + 1;

        const newRow = {
            ...createItem({}),
            fields: [fieldToMove],
        };

        let pages = removeAt(getPages(), `${fromPageIndex}.rows.${fromRowIndex}.fields`, fromFieldIndex);
        pages = insertAt(pages, `${toPageIndex}.rows`, newRow, insertionIndex);

        const sourceRowIndexAfterInsert = fromPageIndex === toPageIndex && insertionIndex <= fromRowIndex
            ? fromRowIndex + 1
            : fromRowIndex;
        const originalRowFields = getValue(pages, `${fromPageIndex}.rows.${sourceRowIndexAfterInsert}.fields`);
        if (originalRowFields && originalRowFields.length === 0) {
            pages = removeAt(pages, `${fromPageIndex}.rows`, sourceRowIndexAfterInsert);
        }

        setPages(pages);
    };

    const getRowFields = (pageIndex, rowIndex) => {
        return getValue(getPages(), `${pageIndex}.rows.${rowIndex}.fields`) || [];
    };

    const addFieldBetweenNestedRows = (pageIndex, rowIndex, fieldIndex, nestedRowDropIndex, fieldData) => {
        const pages = cloneDeep(getPages());
        const rowsPath = `${pageIndex}.rows.${rowIndex}.fields.${fieldIndex}.rows`;
        const existingRows = getValue(pages, rowsPath);

        if (!Array.isArray(existingRows)) {
            setValue(pages, rowsPath, []);
        }

        const rows = getValue(pages, rowsPath) || [];
        const nextRows = [...rows];
        const insertionIndex = nestedRowDropIndex === -1 ? 0 : nestedRowDropIndex + 1;

        nextRows.splice(insertionIndex, 0, {
            ...createItem({}),
            fields: [createItem(assignFieldReferences(fieldData))],
        });

        setValue(pages, rowsPath, nextRows);
        setPages(pages);
    };

    const addFieldBetweenNestedFields = (pageIndex, rowIndex, fieldIndex, nestedRowIndex, nestedFieldIndex, fieldData) => {
        const pages = cloneDeep(getPages());
        const rowFieldsPath = `${pageIndex}.rows.${rowIndex}.fields.${fieldIndex}.rows.${nestedRowIndex}.fields`;
        const existingFields = getValue(pages, rowFieldsPath);

        if (!Array.isArray(existingFields)) {
            return;
        }

        const nextFields = [...existingFields];
        const insertionIndex = nestedFieldIndex === -1 ? 0 : nestedFieldIndex + 1;

        nextFields.splice(insertionIndex, 0, createItem(assignFieldReferences(fieldData)));

        setValue(pages, rowFieldsPath, nextFields);
        setPages(pages);
    };

    const updateNestedField = (pageIndex, rowIndex, fieldIndex, nestedRowIndex, nestedFieldIndex, updates) => {
        const pages = cloneDeep(getPages());
        const nestedFieldPath = `${pageIndex}.rows.${rowIndex}.fields.${fieldIndex}.rows.${nestedRowIndex}.fields.${nestedFieldIndex}`;
        const currentField = getValue(pages, nestedFieldPath);

        if (!currentField || typeof currentField !== 'object') {
            return;
        }

        setValue(pages, nestedFieldPath, {
            ...currentField,
            ...updates,
        });
        setPages(pages);
    };

    const deleteNestedField = (pageIndex, rowIndex, fieldIndex, nestedRowIndex, nestedFieldIndex) => {
        const pages = cloneDeep(getPages());
        const nestedRowFieldsPath = `${pageIndex}.rows.${rowIndex}.fields.${fieldIndex}.rows.${nestedRowIndex}.fields`;
        const nestedRowsPath = `${pageIndex}.rows.${rowIndex}.fields.${fieldIndex}.rows`;
        const currentFields = getValue(pages, nestedRowFieldsPath);

        if (!Array.isArray(currentFields)) {
            return;
        }

        const nextFields = [...currentFields];
        nextFields.splice(nestedFieldIndex, 1);
        setValue(pages, nestedRowFieldsPath, nextFields);

        if (nextFields.length === 0) {
            const currentRows = getValue(pages, nestedRowsPath);
            if (Array.isArray(currentRows)) {
                const nextRows = [...currentRows];
                nextRows.splice(nestedRowIndex, 1);
                setValue(pages, nestedRowsPath, nextRows);
            }
        }

        setPages(pages);
    };

    const moveTopLevelFieldToNested = (
        fromPageIndex,
        fromRowIndex,
        fromFieldIndex,
        toPageIndex,
        toRowIndex,
        toFieldIndex,
        toNestedRowIndex,
        toNestedFieldIndex,
        asNewRow = false,
    ) => {
        const pages = moveTopLevelFieldToNestedInPages({
            pages: { pages: getPages() },
            fromPageIndex,
            fromRowIndex,
            fromFieldIndex,
            toPageIndex,
            toRowIndex,
            toFieldIndex,
            toNestedRowIndex,
            toNestedFieldIndex,
            asNewRow,
        });

        setPages(pages.pages || []);
    };

    const moveNestedFieldToTopLevel = (
        fromPageIndex,
        fromRowIndex,
        fromFieldIndex,
        fromNestedRowIndex,
        fromNestedFieldIndex,
        toPageIndex,
        toRowIndex,
        toFieldIndex,
        asNewRow = false,
    ) => {
        const pages = moveNestedFieldToTopLevelInPages({
            pages: { pages: getPages() },
            fromPageIndex,
            fromRowIndex,
            fromFieldIndex,
            fromNestedRowIndex,
            fromNestedFieldIndex,
            toPageIndex,
            toRowIndex,
            toFieldIndex,
            asNewRow,
        });

        setPages(pages.pages || []);
    };

    const moveNestedFieldWithinParent = (
        pageIndex,
        rowIndex,
        fieldIndex,
        fromNestedRowIndex,
        fromNestedFieldIndex,
        toNestedRowIndex,
        toNestedFieldIndex,
        asNewRow = false,
        sourceNestedFieldId = null,
    ) => {
        const pages = moveNestedFieldWithinParentInPages({
            pages: { pages: getPages() },
            pageIndex,
            rowIndex,
            fieldIndex,
            fromNestedRowIndex,
            fromNestedFieldIndex,
            toNestedRowIndex,
            toNestedFieldIndex,
            asNewRow,
            sourceNestedFieldId,
        });

        setPages(pages.pages || []);
    };

    const getNotifications = () => {
        return values?.notifications || [];
    };

    const setNotifications = (notifications) => {
        form.setFieldValue('notifications', notifications);
    };

    const initNotifications = (notifications) => {
        setNotifications(normalizeCollection(notifications));
    };

    const addNotification = (notification) => {
        setNotifications([...getNotifications(), createItem(notification)]);
    };

    const updateNotification = (notification, updates) => {
        setNotifications(updateItem(getNotifications(), notification, updates));
    };

    const deleteNotification = (notification) => {
        setNotifications(deleteItem(getNotifications(), notification));
    };

    const duplicateNotification = (notification, transformCallback = null) => {
        setNotifications(duplicateItem(getNotifications(), notification, transformCallback));
    };

    return {
        getPages,
        setPages,
        updatePages,
        addPage,
        updatePage,
        deletePage,
        duplicatePage,
        movePage,
        updateField,
        deleteField,
        duplicateField,
        addFieldToPage,
        addFieldToLastPositionOnPage,
        addFieldBetweenFields,
        addFieldBetweenRows,
        moveFieldToPosition,
        moveFieldToLastPositionOnPage,
        moveFieldToNewRow,
        getRowFields,
        addFieldBetweenNestedRows,
        addFieldBetweenNestedFields,
        updateNestedField,
        deleteNestedField,
        moveTopLevelFieldToNested,
        moveNestedFieldToTopLevel,
        moveNestedFieldWithinParent,
        getNotifications,
        initNotifications,
        addNotification,
        updateNotification,
        deleteNotification,
        duplicateNotification,
    };
};

export { useBuilderActions };
