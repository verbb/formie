import { find, mergeWith } from 'lodash-es';
import { newId } from '@utils/string';
import { clone } from '@utils/object';

// State is simply an object that contains the properties that need to be shared within the application:
// The state must return a function to make the module reusable.
// See: https://vuex.vuejs.org/en/modules.html#module-reuse
const state = [];

// Mutations are functions responsible in directly mutating store state.
// In Vuex, mutations always have access to state as the first argument.
// In addition, Actions may or may not pass in a payload as the second argument:
const mutations = {
    SET_FIELDTYPES(state, config) {
        for (const groupIndex in config) {
            if (Object.prototype.hasOwnProperty.call(config, groupIndex)) {
                const { fields } = config[groupIndex];

                for (const fieldIndex in fields) {
                    if (Object.prototype.hasOwnProperty.call(fields, fieldIndex)) {
                        const field = fields[fieldIndex];

                        state.push(field);
                    }
                }
            }
        }
    },
};

// Actions exist to call mutations. Actions are also responsible in performing any
// or all asynchronous calls prior to committing to mutations.
// Actions have access to a context object that provides access to state (with context.state),
// to getters (with context.getters), and to the commit function (with context.commit).
const actions = {
    setFieldtypes(context, config) {
        context.commit('SET_FIELDTYPES', config);
    },
};

// Getters are to a Vuex store what computed properties are to a Vue component.
// Getters are primarily used to perform some calculation/manipulation to store state
// before having that information accessible to components.
const getters = {
    fieldtype: (state) => {
        return (type) => {
            let fieldtype = find(state, { type });

            if (!fieldtype) {
                fieldtype = find(state, { type: 'verbb\\formie\\fields\\MissingField' });
            }

            return fieldtype;
        };
    },

    newField: (state, getters) => {
        return (type, settings) => {
            const fieldtype = getters.fieldtype(type);

            // The fieldtype will contain the settings for a new field
            let { newField } = clone(fieldtype);

            // Allow other settings to be overridden
            if (settings) {
                // Use `mergeWith` to handle replacing nested objects and arrays, rather than merge
                // Useful for SubFields with nested fields that aren't the default. Think Date Dropdowns.
                newField = mergeWith(newField, settings, (objValue, srcValue) => {
                    return srcValue;
                });
            }

            // Set a new client-side ID for the field
            newField.__id = newId();

            // Typecast some properties
            newField.errors = {};

            // Handle any nested fields to also generate their fields
            if (newField.settings.rows && Array.isArray(newField.settings.rows)) {
                newField.settings.rows.forEach((nestedRow) => {
                    if (nestedRow.fields && Array.isArray(nestedRow.fields)) {
                        nestedRow.fields.forEach((nestedField) => {
                            nestedField.__id = newId();
                        });
                    }
                });
            }

            return newField;
        };
    },
};

export default {
    namespaced: true,
    state,
    mutations,
    actions,
    getters,
};
