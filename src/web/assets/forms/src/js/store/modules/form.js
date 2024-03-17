import md5Hex from 'md5-hex';
import { ref } from 'vue';

// eslint-disable-next-line
import { find, flatMap, forEach, filter, omitBy, truncate } from 'lodash-es';

import { toBoolean } from '@utils/bool';
import { newId } from '@utils/string';
import { clone } from '@utils/object';

// State is simply an object that contains the properties that need to be shared within the application:
// The state must return a function to make the module reusable.
// See: https://vuex.vuejs.org/en/modules.html#module-reuse
const state = {
    pages: [],
};

const getRows = (payload) => {
    if (payload.fieldId) {
        // eslint-disable-next-line
        const field = getters.field(state)(payload.fieldId);

        if (typeof field.settings.rows === 'undefined') {
            field.settings.rows = [];
        }

        return ref(field.settings.rows);
    }

    if (typeof state.pages[payload.pageIndex].rows === 'undefined') {
        state.pages[payload.pageIndex].rows = [];
    }

    return ref(state.pages[payload.pageIndex].rows);
};

// Mutations are functions responsible in directly mutating store state.
// In Vuex, mutations always have access to state as the first argument.
// In addition, Actions may or may not pass in a payload as the second argument:
const mutations = {
    SET_FORM_CONFIG(state, config) {
        // Ensure all fields have a `__id` set, which we use in Vue to uniquely identify fields
        // As we want to keep `id` reserved for server-side, saved field models. This helps
        // separate Vue instances of fields, while not setting the IDs to `new-2546` which doesn't
        // play well server-side (Postgres). More to the point, when validation fails server-side, the IDs
        // will have been stripped out from the field model so they correctly save server side, but
        // this wreaks havoc in Vue, as fields have lost their IDs.
        const normalizeObjects = (obj, apply = true) => {
            if (apply && !obj.__id) {
                obj.__id = newId();
            }

            if (apply && obj.errors && Array.isArray(obj.errors)) {
                obj.errors = {};
            }

            if (obj.rows && Array.isArray(obj.rows)) {
                obj.rows.forEach((row) => {
                    normalizeObjects(row);

                    if (row.fields && Array.isArray(row.fields)) {
                        row.fields.forEach((field) => {
                            normalizeObjects(field);

                            if (field.settings && field.settings.rows && Array.isArray(field.settings.rows)) {
                                normalizeObjects(field.settings, false);
                            }
                        });
                    }
                });
            }
        };

        if (config.pages && Array.isArray(config.pages)) {
            config.pages.forEach((page) => {
                normalizeObjects(page);
            });
        }

        for (const prop in config) {
            if (Object.hasOwnProperty.call(config, prop)) {
                state[prop] = config[prop];
            }
        }
    },

    ADD_PAGE(state, payload) {
        const { data } = payload;

        state.pages.push(data);
    },

    UPDATE_PAGE(state, payload) {
        const { pageIndex, data } = payload;

        for (const prop in data) {
            if (Object.hasOwnProperty.call(data, prop)) {
                state.pages[pageIndex][prop] = data[prop];
            }
        }
    },

    DELETE_PAGE(state, payload) {
        const { pageIndex } = payload;

        state.pages.splice(pageIndex, 1);
    },

    ADD_PAGE_SETTINGS(state, payload) {
        const { pageIndex, data } = payload;

        state.pages[pageIndex].settings = data;
    },

    APPEND_ROW(state, payload) {
        const { rowIndex, data } = payload;
        const rows = getRows(payload).value;

        if (rowIndex) {
            rows.splice(rowIndex, 0, data);
        } else {
            rows.push(data);
        }
    },

    APPEND_ROW_TO_PAGE(state, payload) {
        // eslint-disable-next-line
        const { sourcePageIndex, sourceRowIndex, sourceColumnIndex, pageIndex, data } = payload;

        if (state.pages[pageIndex].rows === undefined) {
            state.pages[pageIndex].rows = [];
        }

        // Remove the old column - but also insert it to our new field/row data
        // Remember using splice with `1` will remove 1 element from the array
        data.fields = state.pages[sourcePageIndex].rows[sourceRowIndex].fields.splice(sourceColumnIndex, 1);

        // Check to see if there are no more fields - delete the row too
        if (state.pages[sourcePageIndex].rows[sourceRowIndex].fields.length === 0) {
            state.pages[sourcePageIndex].rows.splice(sourceRowIndex, 1);
        }

        // Add the new row
        state.pages[pageIndex].rows.push(data);
    },

    ADD_ROW(state, payload) {
        const { rowIndex, data } = payload;
        const rows = getRows(payload).value;

        rows.splice(rowIndex, 0, data);
    },

    MOVE_ROW(state, payload) {
        // eslint-disable-next-line
        let { sourceRowIndex, sourceColumnIndex, rowIndex, data } = payload;

        const rows = getRows(payload).value;

        // Just guard against not actually moving rows - but only if its a full-width field
        if (sourceRowIndex === rowIndex || sourceRowIndex === (rowIndex - 1)) {
            // We need to factor in moving a column from columns to a single row
            // even if that's directly above it. You want to break it out into its own row
            if (rows[sourceRowIndex].fields.length === 1) {
                return;
            }
        }

        // Remove the old column - but also insert it to our new field/row data
        // Remember using splice with `1` will remove 1 element from the array
        data.fields = rows[sourceRowIndex].fields.splice(sourceColumnIndex, 1);

        // Check to see if there are no more fields - delete the row too
        if (rows[sourceRowIndex].fields.length === 0) {
            rows.splice(sourceRowIndex, 1);

            // If we've completely removed the row, and we're moving down the list
            // be sure to account for the now incorrect array size
            if (sourceRowIndex < rowIndex) {
                rowIndex = rowIndex - 1;
            }
        }

        // Add the new row
        rows.splice(rowIndex, 0, data);
    },

    ADD_COLUMN(state, payload) {
        const { rowIndex, columnIndex, data } = payload;
        const rows = getRows(payload).value;

        rows[rowIndex].fields.splice(columnIndex, 0, data);
    },

    MOVE_COLUMN(state, payload) {
        // eslint-disable-next-line
        let { sourceRowIndex, sourceColumnIndex, rowIndex, columnIndex } = payload;

        // Just guard against not actually moving columns
        if (sourceRowIndex === rowIndex && sourceColumnIndex === columnIndex) {
            return;
        }

        // Just guard against not actually moving columns
        if (sourceRowIndex === rowIndex && sourceColumnIndex === (columnIndex - 1)) {
            return;
        }

        const rows = getRows(payload).value;

        // noinspection EqualityComparisonWithCoercionJS
        if (sourceRowIndex == rowIndex && rows[sourceRowIndex].fields.length === 1) {
            // Not moving the field anywhere.
            return;
        }

        // Remove the old column
        const [fieldData] = rows[sourceRowIndex].fields.splice(sourceColumnIndex, 1);

        // Check to see if there are no more fields - delete the row too
        if (rows[sourceRowIndex].fields.length === 0) {
            rows.splice(sourceRowIndex, 1);

            // If we've completely removed the row, and we're moving down the list
            // be sure to account for the now incorrect array size
            if (sourceRowIndex < rowIndex) {
                rowIndex = rowIndex - 1;
            }
        }

        // Add the new row
        rows[rowIndex].fields.splice(columnIndex, 0, fieldData);
    },

    DELETE_FIELD(state, payload) {
        const { id } = payload;

        forEach(state.pages, (page) => {
            forEach(page.rows, (row) => {
                forEach(row.fields, (field, key) => {
                    if (field && field.__id == id) {
                        row.fields.splice(key, 1);
                        return false;
                    }

                    if (field.hasNestedFields) {
                        forEach(field.settings.rows, (repeaterRow) => {
                            forEach(repeaterRow.fields, (repeaterField, repeaterKey) => {
                                if (repeaterField && repeaterField.__id == id) {
                                    repeaterRow.fields.splice(repeaterKey, 1);
                                    return false;
                                }
                            });
                        });
                    }
                });
            });
        });

        // Check for cleanup of rows
        forEach(state.pages, (page) => {
            forEach(page.rows, (row, key) => {
                if (row && row.fields.length === 0) {
                    page.rows.splice(key, 1);
                    return false;
                }

                forEach(row.fields, (field) => {
                    if (field.hasNestedFields) {
                        forEach(field.settings.rows, (repeaterRow, repeaterKey) => {
                            if (repeaterRow && repeaterRow.fields.length === 0) {
                                field.settings.rows.splice(repeaterKey, 1);
                                return false;
                            }
                        });
                    }
                });
            });
        });
    },

    UPDATE_FIELD_SETTINGS(state, payload) {
        // eslint-disable-next-line
        const { rowIndex, columnIndex, prop, value } = payload;
        const rows = getRows(payload).value;

        // Make sure to use Vue.set - the prop might not exist
        rows[rowIndex].fields[columnIndex].settings[prop] = value;
    },

    SET_FIELD_PROP(state, payload) {
        // eslint-disable-next-line
        const { rowIndex, columnIndex, prop, value } = payload;
        const rows = getRows(payload).value;

        rows[rowIndex].fields[columnIndex][prop] = value;
    },

    SET_VARIABLES(state, config) {
        state.variables = config;
    },
};

// Actions exist to call mutations. Actions are also responsible in performing any
// or all asynchronous calls prior to committing to mutations.
// Actions have access to a context object that provides access to state (with context.state),
// to getters (with context.getters), and to the commit function (with context.commit).
const actions = {
    setFormConfig(context, config) {
        context.commit('SET_FORM_CONFIG', config);
    },

    addPage(context, payload) {
        context.commit('ADD_PAGE', payload);
    },

    updatePage(context, payload) {
        context.commit('UPDATE_PAGE', payload);
    },

    deletePage(context, payload) {
        context.commit('DELETE_PAGE', payload);
    },

    addPageSettings(context, payload) {
        context.commit('ADD_PAGE_SETTINGS', payload);
    },

    appendRow(context, payload) {
        context.commit('APPEND_ROW', payload);
    },

    appendRowToPage(context, payload) {
        context.commit('APPEND_ROW_TO_PAGE', payload);
    },

    addRow(context, payload) {
        context.commit('ADD_ROW', payload);
    },

    moveRow(context, payload) {
        context.commit('MOVE_ROW', payload);
    },

    addColumn(context, payload) {
        context.commit('ADD_COLUMN', payload);
    },

    moveColumn(context, payload) {
        context.commit('MOVE_COLUMN', payload);
    },

    deleteField(context, payload) {
        context.commit('DELETE_FIELD', payload);
    },

    updateFieldSettings(context, payload) {
        context.commit('UPDATE_FIELD_SETTINGS', payload);
    },

    setFieldProp(context, payload) {
        context.commit('SET_FIELD_PROP', payload);
    },

    setVariables(context, config) {
        context.commit('SET_VARIABLES', config);
    },
};

// Getters are to a Vuex store what computed properties are to a Vue component.
// Getters are primarily used to perform some calculation/manipulation to store state
// before having that information accessible to components.
const getters = {
    config: (state) => {
        return state;
    },

    formHash: (state, getters, store) => {
        // Generate a hash of Vue data, used for unload warnings
        return md5Hex(JSON.stringify(state.pages) + JSON.stringify(store.notifications));
    },

    serializedPayload: (state) => {
    // Function to remove unwanted properties from a given object
        const removeUnwantedProperties = (obj) => {
            delete obj.__id;
            delete obj.errors;
        };

        // Recursive function to filter out unwanted data from fields
        const filterFields = (fields) => {
            fields.forEach((field, fieldKey) => {
            // Modify the fields to only return what we need
                fields[fieldKey] = {
                    id: field.id,
                    type: field.type,
                    settings: field.settings,
                };

                // Handle any nested fields, recursively
                if (field.settings && field.settings.rows) {
                    field.settings.rows.forEach((row) => {
                        removeUnwantedProperties(row);

                        filterFields(row.fields);
                    });
                }
            });
        };

        // Clone the pages to avoid modifying the original state
        const pages = clone(state.pages);

        // Iterate through pages and filter out unwanted data from fields
        pages.forEach((page) => {
            removeUnwantedProperties(page);

            page.rows.forEach((row) => {
                removeUnwantedProperties(row);

                filterFields(row.fields);
            });
        });

        return pages;
    },

    pageSettings: (state) => {
        return (pageId) => {
            const page = find(state.pages, { id: pageId });

            if (page) {
                return page.settings;
            }

            return {};
        };
    },

    field: (state) => {
        return (id) => {
            const allFields = getters.fields(state)(true);

            return find(allFields, { __id: id });
        };
    },

    fields: (state) => {
        return (includeNested = false) => {
            const allRows = flatMap(state.pages, 'rows');
            let allFields = flatMap(allRows, 'fields');

            if (includeNested) {
                const nestedFields = filter(allFields, (field) => { return !!field.settings.rows; });
                const nestedRows = flatMap(nestedFields, 'settings.rows');

                allFields = [
                    ...allFields,
                    ...flatMap(nestedRows, 'fields'),
                ];
            }

            // Return all non-empty fields
            return allFields.filter(Boolean);
        };
    },

    generalFields: (state) => {
        let fields = [];

        for (const key in state.variables) {
            if (Object.hasOwnProperty.call(state.variables, key)) {
                fields = [
                    ...fields,
                    ...state.variables[key],
                ];
            }
        }

        return fields;
    },

    userFields: (state) => {
        return state.variables.users;
    },

    emailFields: (state, getters) => {
        return (includeGeneral = false) => {
            // TODO refactor this, probably server-side
            const includedTypes = [
                'verbb\\formie\\fields\\Email',
                'verbb\\formie\\fields\\Hidden',
                'verbb\\formie\\fields\\Recipients',
            ];

            let fields = [
                { label: Craft.t('formie', 'Fields'), heading: true },
                ...getters.getFieldSelectOptions({ includedTypes }),
            ];

            // Check if there's only a heading
            if (fields.length === 1) {
                fields = [];
            }

            if (includeGeneral) {
                fields = fields.concat(getters.generalFields);
            } else {
                fields = fields.concat(state.variables.email);
            }

            return fields;
        };
    },

    numberFields: (state, getters) => {
        return () => {
            // TODO refactor this, probably server-side
            const includedTypes = [
                'verbb\\formie\\fields\\Number',
                'verbb\\formie\\fields\\Hidden',
            ];

            let fields = [
                { label: Craft.t('formie', 'Fields'), heading: true },
                ...getters.getFieldSelectOptions({ includedTypes }),
            ];

            // Check if there's only a heading
            if (fields.length === 1) {
                fields = [];
            }

            return fields;
        };
    },

    plainTextFields: (state, getters) => {
        return (includeGeneral = false, extra = []) => {
            // TODO refactor this, probably server-side
            const includedTypes = [
                'verbb\\formie\\fields\\Date',
                'verbb\\formie\\fields\\Dropdown',
                'verbb\\formie\\fields\\Email',
                'verbb\\formie\\fields\\Hidden',
                'verbb\\formie\\fields\\Html',
                'verbb\\formie\\fields\\Number',
                'verbb\\formie\\fields\\Phone',
                'verbb\\formie\\fields\\Radio',
                'verbb\\formie\\fields\\SingleLineText',

                // Some fields that's values have __toString implemented.
                'verbb\\formie\\fields\\Name',

                ...extra,
            ];

            let fields = [
                { label: Craft.t('formie', 'Fields'), heading: true },
                ...getters.getFieldSelectOptions({ includedTypes }),
            ];

            // Check if there's only a heading
            if (fields.length === 1) {
                fields = [];
            }

            if (includeGeneral) {
                fields = fields.concat(getters.generalFields);
            }

            return fields;
        };
    },

    allFieldOptions: (state, getters) => {
        return (options = {}) => {
            let fields = [
                { label: Craft.t('formie', 'Fields'), heading: true },
                ...getters.getFieldSelectOptions(),
            ];

            // Check if there's only a heading
            if (fields.length === 1) {
                fields = [];
            }

            if (options.includeGeneral) {
                fields = fields.concat(getters.generalFields);
            }

            return fields;
        };
    },

    getFieldSelectOptions: (state, getters) => {
        return (options = {}) => {
            let fieldOptions = [];

            getters.fields().forEach((field) => {
                getters.getFieldSelectOption(fieldOptions, field);
            });

            if (options.includedTypes && options.includedTypes.length) {
                fieldOptions = fieldOptions.filter((fieldOption) => {
                    return options.includedTypes.includes(fieldOption.type);
                });
            }

            if (options.excludedFields && options.excludedFields.length) {
                fieldOptions = fieldOptions.filter((fieldOption) => {
                    return !options.excludedFields.includes(fieldOption.__id);
                });
            }

            return fieldOptions;
        };
    },

    getFieldSelectOption: (state, getters, rootState, rootGetters) => {
        return (fieldOptions, field, labelPrefix = '', handlePrefix = '') => {
            if (field.isCosmetic) {
                return;
            }

            if (field.type === 'verbb\\formie\\fields\\Name' && !field.settings.useMultipleFields) {
                fieldOptions.push({
                    ...field,
                    label: labelPrefix + truncate(field.settings.label, { length: 60 }),
                    value: `{field:${handlePrefix}${field.settings.handle}}`,
                });
            } else if (field.settings.rows) {
                // Handle Group fields (single-nesting field types) and Sub-Fields
                field.settings.rows.forEach((row) => {
                    row.fields.forEach((nestedField) => {
                        getters.getConditionsFieldOption(fieldOptions, nestedField, `${labelPrefix}${truncate(field.settings.label, { length: 60 })}: `, `${handlePrefix}${field.settings.handle}.`);
                    });
                });
            } else {
                fieldOptions.push({
                    ...field,
                    label: labelPrefix + truncate(field.settings.label, { length: 60 }),
                    value: `{field:${handlePrefix}${field.settings.handle}}`,
                });

                const fieldType = rootGetters['fieldtypes/fieldtype'](field.type);

                if (fieldType && fieldType.fieldSelectOptions && Array.isArray(fieldType.fieldSelectOptions)) {
                    fieldType.fieldSelectOptions.forEach((fieldSelectOption) => {
                        fieldOptions.push({
                            ...field,
                            label: `${labelPrefix + truncate(field.settings.label, { length: 60 })}: ${truncate(fieldSelectOption.label, { length: 60 })}`,
                            value: `{field:${handlePrefix}${field.settings.handle}.${fieldSelectOption.handle}}`,
                        });
                    });
                }
            }
        };
    },

    getIntegrationFieldSelectOptions: (state, getters) => {
        return (options = {}) => {
            let fieldOptions = [];

            getters.fields().forEach((field) => {
                getters.getIntegrationFieldSelectOption(fieldOptions, field);
            });

            if (options.includedTypes && options.includedTypes.length) {
                fieldOptions = fieldOptions.filter((fieldOption) => {
                    return options.includedTypes.includes(fieldOption.type);
                });
            }

            if (options.excludedFields && options.excludedFields.length) {
                fieldOptions = fieldOptions.filter((fieldOption) => {
                    return !options.excludedFields.includes(fieldOption.__id);
                });
            }

            return fieldOptions;
        };
    },

    getIntegrationFieldSelectOption: (state, getters, rootState, rootGetters) => {
        return (fieldOptions, field, labelPrefix = '', handlePrefix = '') => {
            if (field.isCosmetic) {
                return;
            }

            if (field.type === 'verbb\\formie\\fields\\Name' && !field.settings.useMultipleFields) {
                fieldOptions.push({
                    ...field,
                    label: labelPrefix + truncate(field.settings.label, { length: 60 }),
                    value: `{field:${handlePrefix}${field.settings.handle}}`,
                });
            } else if (field.settings.rows) {
                // Handle Group fields (single-nesting field types) and Sub-Fields
                fieldOptions.push({
                    ...field,
                    label: labelPrefix + truncate(field.settings.label, { length: 60 }),
                    value: `{field:${handlePrefix}${field.settings.handle}}`,
                });

                field.settings.rows.forEach((row) => {
                    row.fields.forEach((subField) => {
                        getters.getIntegrationFieldSelectOption(fieldOptions, subField, `${labelPrefix}${truncate(field.settings.label, { length: 60 })}: `, `${handlePrefix}${field.settings.handle}.`);
                    });
                });
            } else {
                fieldOptions.push({
                    ...field,
                    label: labelPrefix + truncate(field.settings.label, { length: 60 }),
                    value: `{field:${handlePrefix}${field.settings.handle}}`,
                });

                const fieldType = rootGetters['fieldtypes/fieldtype'](field.type);

                if (fieldType && fieldType.fieldSelectOptions && Array.isArray(fieldType.fieldSelectOptions)) {
                    fieldType.fieldSelectOptions.forEach((fieldSelectOption) => {
                        fieldOptions.push({
                            ...field,
                            label: `${labelPrefix + truncate(field.settings.label, { length: 60 })}: ${truncate(fieldSelectOption.label, { length: 60 })}`,
                            value: `{field:${handlePrefix}${field.settings.handle}.${fieldSelectOption.handle}}`,
                        });
                    });
                }
            }
        };
    },

    getConditionsFieldOptions: (state, getters) => {
        return (options = {}) => {
            let fieldOptions = [];

            getters.fields().forEach((field) => {
                getters.getConditionsFieldOption(fieldOptions, field);
            });

            if (options.includedTypes && options.includedTypes.length) {
                fieldOptions = fieldOptions.filter((fieldOption) => {
                    return options.includedTypes.includes(fieldOption.type);
                });
            }

            if (options.excludedFields && options.excludedFields.length) {
                fieldOptions = fieldOptions.filter((fieldOption) => {
                    return !options.excludedFields.includes(fieldOption.__id);
                });
            }

            return fieldOptions;
        };
    },

    getConditionsFieldOption: (state, getters, rootState, rootGetters) => {
        return (fieldOptions, field, labelPrefix = '', handlePrefix = '') => {
            if (field.isCosmetic) {
                return;
            }

            if (field.type === 'verbb\\formie\\fields\\Name' && !field.settings.useMultipleFields) {
                fieldOptions.push({
                    ...field,
                    label: labelPrefix + truncate(field.settings.label, { length: 60 }),
                    value: `{field:${handlePrefix}${field.settings.handle}}`,
                });
            } else if (field.settings.rows) {
                if (field.isMultiNested) {
                    const contextField = rootState.formie.editingField;

                    // Repeaters only allow selecting their sibling fields
                    if (contextField && contextField.parentFieldId === field.__id) {
                        field.settings.rows.forEach((row) => {
                            row.fields.forEach((nestedField) => {
                                getters.getConditionsFieldOption(fieldOptions, nestedField, `${labelPrefix}${truncate(field.settings.label, { length: 60 })}: `, `__ROW__.${handlePrefix}${field.settings.handle}.`);
                            });
                        });
                    }
                } else {
                    // Handle Group fields (single-nesting field types) and Sub-Fields
                    field.settings.rows.forEach((row) => {
                        row.fields.forEach((nestedField) => {
                            getters.getConditionsFieldOption(fieldOptions, nestedField, `${labelPrefix}${truncate(field.settings.label, { length: 60 })}: `, `${handlePrefix}${field.settings.handle}.`);
                        });
                    });
                }
            } else {
                fieldOptions.push({
                    ...field,
                    label: labelPrefix + truncate(field.settings.label, { length: 60 }),
                    value: `{field:${handlePrefix}${field.settings.handle}}`,
                });

                const fieldType = rootGetters['fieldtypes/fieldtype'](field.type);

                if (fieldType && fieldType.fieldSelectOptions && Array.isArray(fieldType.fieldSelectOptions)) {
                    fieldType.fieldSelectOptions.forEach((fieldSelectOption) => {
                        fieldOptions.push({
                            ...field,
                            label: `${labelPrefix + truncate(field.settings.label, { length: 60 })}: ${truncate(fieldSelectOption.label, { length: 60 })}`,
                            value: `{field:${handlePrefix}${field.settings.handle}.${fieldSelectOption.handle}}`,
                        });
                    });
                }
            }
        };
    },

    fieldsForType: (state, getters) => {
        return (type) => {
            let fields = [];

            fields = fields.concat(getters.fields().filter((field) => {
                return field.type === type;
            }).map((field) => {
                return { label: field.settings.label, value: `{${field.settings.handle}}` };
            }));

            return fields;
        };
    },

    fieldsForPage: (state) => {
        return (pageIndex) => {
            return flatMap(state.pages[pageIndex].rows, 'fields');
        };
    },

    fieldHandles: (state) => {
        const allRows = flatMap(state.pages, 'rows');
        const allFields = flatMap(allRows, 'fields');
        return flatMap(allFields, 'settings.handle');
    },

    fieldHandlesForField: (state, getters, rootState, rootGetters) => {
        return (id) => {
            const field = getters.fields().find((field) => {
                return field.__id === id;
            });

            if (field) {
                const allFields = flatMap(field.settings.rows, 'fields');

                let fieldHandles = flatMap(allFields, 'settings.handle');

                // Fetch all reserved handles
                const reservedHandles = rootGetters['formie/reservedHandles']();
                fieldHandles = fieldHandles.concat(reservedHandles);

                return fieldHandles;
            }

            return [];
        };
    },

    fieldHandlesExcluding: (state, getters, rootState, rootGetters) => {
        return (id, parentId) => {
            const allRows = flatMap(state.pages, 'rows');
            let allFields = flatMap(allRows, 'fields');

            // When supplying a parent field, limit handles to children of the parent
            if (parentId) {
                const field = getters.fields().find((field) => {
                    return field.__id === parentId;
                });

                if (field) {
                    allFields = flatMap(field.settings.rows, 'fields');
                }
            }

            allFields = omitBy(allFields, { __id: id });

            let fieldHandles = flatMap(allFields, 'settings.handle');

            // Fetch all reserved handles
            const reservedHandles = rootGetters['formie/reservedHandles']();
            fieldHandles = fieldHandles.concat(reservedHandles);

            return fieldHandles;
        };
    },

    notificationIds: (state) => {
        return flatMap(state.notifications, 'id');
    },
};

export default {
    namespaced: true,
    state,
    mutations,
    actions,
    getters,
};
