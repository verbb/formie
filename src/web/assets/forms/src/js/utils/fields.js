import { cloneDeep } from 'lodash-es';

import { newId } from '@utils/string';

export const clonedFieldSettings = function(field) {
    const settings = cloneDeep(field.settings);

    // Delete any layout matter for the field
    delete settings.layoutId;
    delete settings.pageId;
    delete settings.rowId;
    delete settings.nestedLayoutId;

    // A little extra handling here for nested fields, where we don't want to include IDs
    if (settings.rows && Array.isArray(settings.rows)) {
        settings.rows.forEach((nestedRow) => {
            nestedRow.__id = newId();
            delete nestedRow.id;
            delete nestedRow.layoutId;
            delete nestedRow.pageId;

            if (nestedRow.fields && Array.isArray(nestedRow.fields)) {
                nestedRow.fields.forEach((nestedField) => {
                    nestedField.__id = newId();
                    delete nestedField.id;
                    delete nestedField.settings.layoutId;
                    delete nestedField.settings.pageId;
                    delete nestedField.settings.rowId;
                });
            }
        });
    }

    return settings;
};
