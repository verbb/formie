import md5Hex from 'md5-hex';
import { ref } from 'vue';

// eslint-disable-next-line
import { get, set, unset, find, isObject, flatMap, forEach, filter, omitBy, truncate } from 'lodash-es';

import { toBoolean } from '@utils/bool';
import { newId } from '@utils/string';
import { clone } from '@utils/object';

// State is simply an object that contains the properties that need to be shared within the application:
// The state must return a function to make the module reusable.
// See: https://vuex.vuejs.org/en/modules.html#module-reuse
const state = {
    pages: [],

    // Cached field select options for various things
    integrationFieldSelectOptions: [],
    conditionsFieldOptions: [],
    fieldSelectOptions: [],

    // Keep track of deleted things separately for server-side convenience
    deleted: {
        fields: [],
        rows: [],
        pages: [],
    },
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

const cleanupEmptyRows = (data, state) => {
    if (Array.isArray(data)) {
        data.forEach((item) => {
            cleanupEmptyRows(item, state);
        });
    }

    if (isObject(data)) {
        for (const key in data) {
            if (key === 'rows') {
                if (Array.isArray(data[key])) {
                    data[key].forEach((row, rowIndex) => {
                        if (Array.isArray(row.fields) && row.fields.length === 0) {
                            const deletedRows = data[key].splice(rowIndex, 1);

                            // Mark it as deleted server-side too
                            state.deleted.rows.push(...deletedRows);
                        }
                    });
                }
            }

            if (Array.isArray(data[key]) || isObject(data[key])) {
                cleanupEmptyRows(data[key], state);
            }
        }
    }
};

const getKeyPath = (obj, id, path = []) => {
    for (const key in obj) {
        if (key === '__id' && obj[key] === id) {
            return path;
        }

        if (typeof obj[key] === 'object' && obj[key] !== null) {
            const result = getKeyPath(obj[key], id, [...path, key]);

            if (result !== null) {
                return result;
            }
        }
    }

    return null;
};

const removeAtKeyPath = (state, path) => {
    // In order to remove an item at the provided path, we need to bubble up to the parent
    // and then splice it from there. So remove the last index item, then get the parent
    const index = parseInt(path.pop());
    const parent = get(state, path.join('.'));

    return parent.splice(index, 1);
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

        // Cleanup any empty rows on populate, in case something was gone awry
        cleanupEmptyRows(state.pages, state);
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

        const deletedPages = state.pages.splice(pageIndex, 1);

        state.deleted.pages.push(...deletedPages);
    },

    ADD_PAGE_SETTINGS(state, payload) {
        const { pageIndex, data } = payload;

        state.pages[pageIndex].settings = data;
    },

    ADD_FIELD(state, { destinationPath, value }) {
        // Get the index where we want to insert the content into
        const fieldIndex = parseInt(destinationPath.pop());

        // Get the parent `fields` so that we can insert it at the index
        const fields = get(state, destinationPath.join('.'));

        if (!fields) {
            // If the key path doesn't exist yet, create it and set the value
            set(state, `${destinationPath.join('.')}.${fieldIndex}`, value);
        } else {
            // Insert the new field in-place at the index
            fields.splice(fieldIndex, 0, value);
        }
    },

    MOVE_FIELD(state, { sourcePath, destinationPath, value }) {
        // Remove the source item from its position
        removeAtKeyPath(state, sourcePath);

        // Add the remove at the index (like we're adding a new one)
        this.dispatch('form/addField', {
            destinationPath,
            value,
        });

        // Cleanup any empty rows
        cleanupEmptyRows(state.pages, state);
    },

    DELETE_FIELD(state, { id }) {
        // Find the field in the entire layout and remove it
        const keyPath = this.getters['form/keyPath'](id);

        // Get the parent so that we can remove it
        const deletedFields = removeAtKeyPath(state, keyPath);

        state.deleted.fields.push(...deletedFields);

        // Cleanup any empty rows
        cleanupEmptyRows(state.pages, state);
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

    addField(context, payload) {
        context.commit('ADD_FIELD', payload);
    },

    moveField(context, payload) {
        context.commit('MOVE_FIELD', payload);
    },

    deleteField(context, payload) {
        context.commit('DELETE_FIELD', payload);
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

    keyPath: (state) => {
        return (id, extra = []) => {
            // Get the path to _this_ row, which is close to where we want to insert the new row
            return getKeyPath({ pages: state.pages }, id);
        };
    },

    parentKeyPath: (state, getters) => {
        return (id, extra = []) => {
            const keyPath = getters.keyPath(id);
            keyPath.pop();

            return keyPath.concat(extra);
        };
    },

    valueByKeyPath: (state) => {
        return (path) => {
            return get(state, path.join('.'));
        };
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

    serializedDeleted: (state) => {
        const filterNonNullIds = (array) => {
            return array.map((item) => {
                return item.id;
            }).filter((id) => {
                return id !== null && id !== undefined;
            });
        };

        // Only return items that have been saved in the database with IDs
        return {
            pages: filterNonNullIds(state.deleted.pages),
            rows: filterNonNullIds(state.deleted.rows),
            fields: filterNonNullIds(state.deleted.fields),
        };
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
        return (options = {}) => {
            // TODO refactor this, probably server-side
            options.includedTypes = [
                'verbb\\formie\\fields\\Email',
                'verbb\\formie\\fields\\Hidden',
                'verbb\\formie\\fields\\Recipients',

                ...options.includedTypes ?? [],
            ];

            let fields = [
                { label: Craft.t('formie', 'Fields'), heading: true },
                ...getters.getFieldSelectOptions(options),
            ];

            // Check if there's only a heading
            if (fields.length === 1) {
                fields = [];
            }

            if (options.includeGeneral) {
                fields = fields.concat(getters.generalFields);
            } else {
                fields = fields.concat(state.variables.email);
            }

            return fields;
        };
    },

    numberFields: (state, getters) => {
        return (options = {}) => {
            // TODO refactor this, probably server-side
            options.includedTypes = [
                'verbb\\formie\\fields\\Number',
                'verbb\\formie\\fields\\Hidden',

                ...options.includedTypes ?? [],
            ];

            let fields = [
                { label: Craft.t('formie', 'Fields'), heading: true },
                ...getters.getFieldSelectOptions(options),
            ];

            // Check if there's only a heading
            if (fields.length === 1) {
                fields = [];
            }

            if (options.includeGeneral) {
                fields = fields.concat(getters.generalFields);
            } else {
                fields = fields.concat(state.variables.email);
            }

            return fields;
        };
    },

    plainTextFields: (state, getters) => {
        return (options = {}) => {
            // TODO refactor this, probably server-side
            options.includedTypes = [
                'verbb\\formie\\fields\\Date',
                'verbb\\formie\\fields\\Dropdown',
                'verbb\\formie\\fields\\Email',
                'verbb\\formie\\fields\\Hidden',
                'verbb\\formie\\fields\\Html',
                'verbb\\formie\\fields\\Number',
                'verbb\\formie\\fields\\Phone',
                'verbb\\formie\\fields\\Radio',
                'verbb\\formie\\fields\\SingleLineText',

                // Some fields have __toString implemented.
                'verbb\\formie\\fields\\Name',
                'verbb\\formie\\fields\\subfields\\NameFirst',
                'verbb\\formie\\fields\\subfields\\NameLast',
                'verbb\\formie\\fields\\subfields\\NameMiddle',
                'verbb\\formie\\fields\\subfields\\NamePrefix',

                // Some fields have __toString implemented.
                'verbb\\formie\\fields\\Address',
                'verbb\\formie\\fields\\subfields\\Address1',
                'verbb\\formie\\fields\\subfields\\Address2',
                'verbb\\formie\\fields\\subfields\\Address3',
                'verbb\\formie\\fields\\subfields\\AddressCity',
                'verbb\\formie\\fields\\subfields\\AddressZip',
                'verbb\\formie\\fields\\subfields\\AddressState',
                'verbb\\formie\\fields\\subfields\\AddressCountry',

                ...options.includedTypes ?? [],
            ];

            let fields = [
                { label: Craft.t('formie', 'Fields'), heading: true },
                ...getters.getFieldSelectOptions(options),
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

    allFieldOptions: (state, getters) => {
        return (options = {}) => {
            let fields = [
                { label: Craft.t('formie', 'Fields'), heading: true },
                ...getters.getFieldSelectOptions(options),
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
                getters.getFieldSelectOption(fieldOptions, field, options);
            });

            if (options.includedTypes && options.includedTypes.length) {
                fieldOptions = fieldOptions.filter((fieldOption) => {
                    return options.includedTypes.includes(fieldOption.type);
                });
            }

            if (options.excludedTypes && options.excludedTypes.length) {
                fieldOptions = fieldOptions.filter((fieldOption) => {
                    return !options.excludedTypes.includes(fieldOption.type);
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
        return (fieldOptions, field, options, labelPrefix = '', handlePrefix = '') => {
            if (field.isCosmetic) {
                return;
            }

            if (options.excludedTypes && options.excludedTypes.length) {
                if (options.excludedTypes.includes(field.type)) {
                    return;
                }
            }

            if (field.type === 'verbb\\formie\\fields\\Name' && !field.settings.useMultipleFields) {
                fieldOptions.push({
                    ...field,
                    label: labelPrefix + truncate(field.settings.label, { length: 60 }),
                    value: `{field:${handlePrefix}${field.settings.handle}}`,
                });
            } else if (field.settings.rows && !field.isMultiNested) {
                // Include the string representation of fields, if Sub-Fields
                if (field.hasSubFields) {
                    fieldOptions.push({
                        ...field,
                        label: labelPrefix + truncate(field.settings.label, { length: 60 }),
                        value: `{field:${handlePrefix}${field.settings.handle}.__toString}`,
                    });
                }

                // Unable to select inner Repeater fields, until we design a better UI to handle repeatable values.
                // Handle Group fields (single-nesting field types) and Sub-Fields
                field.settings.rows.forEach((row) => {
                    row.fields.forEach((nestedField) => {
                        getters.getFieldSelectOption(fieldOptions, nestedField, options, `${labelPrefix}${truncate(field.settings.label, { length: 60 })}: `, `${handlePrefix}${field.settings.handle}.`);
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
                getters.getIntegrationFieldSelectOption(fieldOptions, field, options);
            });

            if (options.includedTypes && options.includedTypes.length) {
                fieldOptions = fieldOptions.filter((fieldOption) => {
                    return options.includedTypes.includes(fieldOption.type);
                });
            }

            if (options.excludedTypes && options.excludedTypes.length) {
                fieldOptions = fieldOptions.filter((fieldOption) => {
                    return !options.excludedTypes.includes(fieldOption.type);
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
        return (fieldOptions, field, options, labelPrefix = '', handlePrefix = '') => {
            if (field.isCosmetic) {
                return;
            }

            if (options.excludedTypes && options.excludedTypes.length) {
                if (options.excludedTypes.includes(field.type)) {
                    return;
                }
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
                        getters.getIntegrationFieldSelectOption(fieldOptions, subField, options, `${labelPrefix}${truncate(field.settings.label, { length: 60 })}: `, `${handlePrefix}${field.settings.handle}.`);
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
                getters.getConditionsFieldOption(fieldOptions, field, options);
            });

            if (options.includedTypes && options.includedTypes.length) {
                fieldOptions = fieldOptions.filter((fieldOption) => {
                    return options.includedTypes.includes(fieldOption.type);
                });
            }

            if (options.excludedTypes && options.excludedTypes.length) {
                fieldOptions = fieldOptions.filter((fieldOption) => {
                    return !options.excludedTypes.includes(fieldOption.type);
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
        return (fieldOptions, field, options, labelPrefix = '', handlePrefix = '') => {
            if (field.isCosmetic) {
                return;
            }

            if (options.excludedTypes && options.excludedTypes.length) {
                if (options.excludedTypes.includes(field.type)) {
                    return;
                }
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
                                getters.getConditionsFieldOption(fieldOptions, nestedField, options, `${labelPrefix}${truncate(field.settings.label, { length: 60 })}: `, `${handlePrefix}${field.settings.handle}.__ROW__.`);
                            });
                        });
                    }
                } else {
                    // Include the string representation of fields, if Sub-Fields
                    if (field.hasSubFields) {
                        fieldOptions.push({
                            ...field,
                            label: labelPrefix + truncate(field.settings.label, { length: 60 }),
                            value: `{field:${handlePrefix}${field.settings.handle}.__toString}`,
                        });
                    }

                    // Handle Group fields (single-nesting field types) and Sub-Fields
                    field.settings.rows.forEach((row) => {
                        row.fields.forEach((nestedField) => {
                            getters.getConditionsFieldOption(fieldOptions, nestedField, options, `${labelPrefix}${truncate(field.settings.label, { length: 60 })}: `, `${handlePrefix}${field.settings.handle}.`);
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

    deletedFields: (state, getters, rootState, rootGetters) => {
        return state.deleted.fields;
    },
};

export default {
    namespaced: true,
    state,
    mutations,
    actions,
    getters,
};
