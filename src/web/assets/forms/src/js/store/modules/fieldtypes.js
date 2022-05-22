import { find } from 'lodash-es';
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
                fieldtype = find(state, { type: 'verbb\\formie\\fields\\formfields\\MissingField' });
            }

            return fieldtype;
        };
    },

    newField: (state) => {
        return (type, settings) => {
            let field = find(state, { type });

            // Just in case
            if (!field) {
                field = find(state, { type: 'verbb\\formie\\fields\\formfields\\MissingField' });
            }

            const newField = {
                vid: newId(),
                type,
                columnWidth: 12,
                hasLabel: field.hasLabel,
                settings: { ...clone(field.defaults) },
            };

            if (field.supportsNested) {
                newField.rows = clone(field.rows);
                newField.supportsNested = true;
            }

            // Allow other settings to be overridden
            if (settings) {
                Object.assign(newField, settings);
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
