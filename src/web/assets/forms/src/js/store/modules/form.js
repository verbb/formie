import Vue from 'vue';
import find from 'lodash/find';
import flatMap from 'lodash/flatMap';
import forEach from 'lodash/forEach';
import filter from 'lodash/filter';
import omitBy from 'lodash/omitBy';
import md5Hex from 'md5-hex';

// State is simply an object that contains the properties that need to be shared within the application:
// The state must return a function to make the module reusable.
// See: https://vuex.vuejs.org/en/modules.html#module-reuse
const state = {
    pages: [],
};

const getRows = payload => {
    if (payload.fieldId) {
        const field = getters.field(state)(payload.fieldId);

        if (typeof field.rows === 'undefined') {
            Vue.set(field, 'rows', []);
        }

        return field.rows;
    } else {
        if (typeof state.pages[payload.pageIndex].rows === 'undefined') {
            Vue.set(state.pages[payload.pageIndex], 'rows', []);
        }

        return state.pages[payload.pageIndex].rows;
    }
};

// Mutations are functions responsible in directly mutating store state.
// In Vuex, mutations always have access to state as the first argument.
// In addition, Actions may or may not pass in a payload as the second argument:
const mutations = {
    SET_FORM_CONFIG(state, config) {
        for (const prop in config) {
            if (Object.hasOwnProperty.call(config, prop)) {
                Vue.set(state, prop, config[prop]);
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
                Vue.set(state.pages[pageIndex], prop, data[prop]);
            }
        }
    },

    DELETE_PAGE(state, payload) {
        const { pageIndex } = payload;

        state.pages.splice(pageIndex, 1);
    },

    ADD_PAGE_SETTINGS(state, payload) {
        const { pageIndex, data } = payload;

        Vue.set(state.pages[pageIndex], 'settings', data);
    },

    APPEND_ROW(state, payload) {
        const { rowIndex, data } = payload;
        const rows = getRows(payload);

        if (rowIndex) {
            rows.splice(rowIndex, 0, data);
        } else {
            rows.push(data);
        }
    },

    APPEND_ROW_TO_PAGE(state, payload) {
        const { sourcePageIndex, sourceRowIndex, sourceColumnIndex, pageIndex, data } = payload;

        if (state.pages[pageIndex].rows === undefined) {
            Vue.set(state.pages[pageIndex], 'rows', []);
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
        const rows = getRows(payload);

        rows.splice(rowIndex, 0, data);
    },

    MOVE_ROW(state, payload) {
        let { sourceRowIndex, sourceColumnIndex, rowIndex, data } = payload;
        const rows = getRows(payload);

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
        const rows = getRows(payload);

        rows[rowIndex].fields.splice(columnIndex, 0, data);
    },

    MOVE_COLUMN(state, payload) {
        let { sourceRowIndex, sourceColumnIndex, rowIndex, columnIndex } = payload;

        // Just guard against not actually moving columns
        if (sourceRowIndex === rowIndex && sourceColumnIndex === columnIndex) {
            return;
        }

        // Just guard against not actually moving columns
        if (sourceRowIndex === rowIndex && sourceColumnIndex === (columnIndex - 1)) {
            return;
        }

        const rows = getRows(payload);

        // noinspection EqualityComparisonWithCoercionJS
        if (sourceRowIndex == rowIndex && rows[sourceRowIndex].fields.length === 1) {
            // Not moving the field anywhere.
            return;
        }

        // Remove the old column
        const [ fieldData ] = rows[sourceRowIndex].fields.splice(sourceColumnIndex, 1);

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
                    // noinspection EqualityComparisonWithCoercionJS
                    if (field && field.id == id) {
                        row.fields.splice(key, 1);
                        return false;
                    }

                    if (field.supportsNested) {
                        forEach(field.rows, (repeaterRow) => {
                            forEach(repeaterRow.fields, (repeaterField, repeaterKey) => {
                                // noinspection EqualityComparisonWithCoercionJS
                                if (repeaterField && repeaterField.id == id) {
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
                    if (field.supportsNested) {
                        forEach(field.rows, (repeaterRow, repeaterKey) => {
                            if (repeaterRow && repeaterRow.fields.length === 0) {
                                field.rows.splice(repeaterKey, 1);
                                return false;
                            }
                        });
                    }
                });
            });
        });
    },

    UPDATE_FIELD_SETTINGS(state, payload) {
        const { rowIndex, columnIndex, prop, value } = payload;
        const rows = getRows(payload);

        // Make sure to use Vue.set - the prop might not exist
        Vue.set(rows[rowIndex].fields[columnIndex].settings, prop, value);
    },

    SET_FIELD_PROP(state, payload) {
        const { rowIndex, columnIndex, prop, value } = payload;
        const rows = getRows(payload);

        Vue.set(rows[rowIndex].fields[columnIndex], prop, value);
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
};

// Getters are to a Vuex store what computed properties are to a Vue component.
// Getters are primarily used to perform some calculation/manipulation to store state
// before having that information accessible to components.
const getters = {
    formHash: (state) => {
        return md5Hex(JSON.stringify(state.pages));
    },

    pageSettings: (state) => (pageId) => {
        const page = find(state.pages, { id: pageId });

        if (page) {
            return page.settings;
        }

        return {};
    },

    field: (state) => (id) => {
        const allFields = getters.fields(state);

        return find(allFields, { id });
    },

    fields: (state) => {
        const allRows = flatMap(state.pages, 'rows');
        let allFields = flatMap(allRows, 'fields');

        const repeaterFields = filter(allFields, field => !!field.rows);
        const repeaterRows = flatMap(repeaterFields, 'rows');

        allFields = [
            ...allFields,
            ...flatMap(repeaterRows, 'fields'),
        ];

        // Return all non-empty fields
        return allFields.filter(Boolean);
    },

    generalFields: state => {
        return [
            { label: Craft.t('formie', 'Form'), heading: true },
            { label: Craft.t('formie', 'All Form Fields'), value: '{allFields}' },
            { label: Craft.t('formie', 'All Non Empty Fields'), value: '{allContentFields}' },
            { label: Craft.t('formie', 'Form Name'), value: '{formName}' },
            { label: Craft.t('formie', 'General'), heading: true },
            { label: Craft.t('formie', 'Site Name'), value: '{siteName}' },
            { label: Craft.t('formie', 'System Email'), value: '{systemEmail}' },
            { label: Craft.t('formie', 'System Reply-To'), value: '{systemReplyTo}' },
            { label: Craft.t('formie', 'System Sender Name'), value: '{systemName}' },
            { label: Craft.t('formie', 'Date/Time'), heading: true },
            { label: Craft.t('formie', 'Timestamp (yyyy-mm-dd hh:mm:ss)'), value: '{timestamp}' },
            { label: Craft.t('formie', 'Date (mm/dd/yyyy)'), value: '{dateUs}' },
            { label: Craft.t('formie', 'Date (dd/mm/yyyy)'), value: '{dateInt}' },
            { label: Craft.t('formie', 'Time (12h)'), value: '{time12}' },
            { label: Craft.t('formie', 'Time (24h)'), value: '{time24}' },
            { label: Craft.t('formie', 'Users'), heading: true },
            { label: Craft.t('formie', 'User IP Address'), value: '{userIp}' },
            { label: Craft.t('formie', 'User ID'), value: '{userId}' },
            { label: Craft.t('formie', 'User Email'), value: '{userEmail}' },
            { label: Craft.t('formie', 'Username'), value: '{username}' },
            { label: Craft.t('formie', 'User Full Name'), value: '{userFullName}' },
        ];
    },

    emailFields: (state, getters) => (includeGeneral = false) => {
        // TODO refactor this, probably server-side
        const allowedTypes = [
            'verbb\\formie\\fields\\formfields\\Email',
        ];

        let fields = [
            { label: Craft.t('formie', 'Fields'), heading: true },
        ];

        fields = fields.concat(getters.fields.filter(field => {
            return allowedTypes.includes(field.type);
        }).map(field => {
            return { label: field.label, value: '{' + field.handle + '}' };
        }));

        // Check if there's only a heading
        if (fields.length === 1) {
            fields = [];
        }

        if (includeGeneral) {
            fields = fields.concat(getters.generalFields);
        } else {
            fields = fields.concat([
                { label: Craft.t('formie', 'General'), heading: true },
                { label: Craft.t('formie', 'System Email'), value: '{systemEmail}' },
                { label: Craft.t('formie', 'System Reply-To'), value: '{systemReplyTo}' },
            ]);
        }

        return fields;
    },

    plainTextFields: (state, getters) => (includeGeneral = false) => {
        // TODO refactor this, probably server-side
        const allowedTypes = [
            'verbb\\formie\\fields\\formfields\\Date',
            'verbb\\formie\\fields\\formfields\\Dropdown',
            'verbb\\formie\\fields\\formfields\\Email',
            'verbb\\formie\\fields\\formfields\\Hidden',
            'verbb\\formie\\fields\\formfields\\Html',
            'verbb\\formie\\fields\\formfields\\Number',
            'verbb\\formie\\fields\\formfields\\Phone',
            'verbb\\formie\\fields\\formfields\\Radio',
            'verbb\\formie\\fields\\formfields\\SingleLineText',

            // Some fields that's values have __toString implemented.
            'verbb\\formie\\fields\\formfields\\Name',
        ];

        let fields = [
            { label: Craft.t('formie', 'Fields'), heading: true },
        ];

        fields = fields.concat(getters.fields.filter(field => {
            return allowedTypes.includes(field.type);
        }).map(field => {
            return { label: field.label, value: '{' + field.handle + '}' };
        }));

        // Check if there's only a heading
        if (fields.length === 1) {
            fields = [];
        }

        if (includeGeneral) {
            fields = fields.concat(getters.generalFields);
        }

        return fields;
    },

    fieldsForPage: (state) => (pageIndex) => {
        return flatMap(state.pages[pageIndex].rows, 'fields');
    },

    fieldHandles: (state) => {
        const allRows = flatMap(state.pages, 'rows');
        const allFields = flatMap(allRows, 'fields');
        return flatMap(allFields, 'handle');
    },

    fieldHandlesExcluding: (state, getters, rootState, rootGetters) => (id) => {
        const allRows = flatMap(state.pages, 'rows');
        let allFields = flatMap(allRows, 'fields');

        allFields = omitBy(allFields, { id });

        let fieldHandles = flatMap(allFields, 'handle');

        // Fetch all reserved handles
        const reservedHandles = rootGetters['formie/reservedHandles']();
        fieldHandles = fieldHandles.concat(reservedHandles);

        return fieldHandles;
    },
};

export default {
    namespaced: true,
    state,
    mutations,
    actions,
    getters,
};
