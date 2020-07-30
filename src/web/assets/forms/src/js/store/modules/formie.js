import Vue from 'vue';
import findIndex from 'lodash/findIndex';

// State is simply an object that contains the properties that need to be shared within the application:
// The state must return a function to make the module reusable.
// See: https://vuex.vuejs.org/en/modules.html#module-reuse
const state = {
    reservedHandles: [],
    emailTemplates: [],
    existingFields: [],
    existingNotifications: [],
};

// Mutations are functions responsible in directly mutating store state.
// In Vuex, mutations always have access to state as the first argument.
// In addition, Actions may or may not pass in a payload as the second argument:
const mutations = {
    SET_RESERVED_HANDLES(state, config) {
        for (const prop in config) {
            if (Object.hasOwnProperty.call(config, prop)) {
                Vue.set(state.reservedHandles, prop, config[prop]);
            }
        }
    },

    SET_EMAIL_TEMPLATES(state, config) {
        for (const prop in config) {
            if (Object.hasOwnProperty.call(config, prop)) {
                Vue.set(state.emailTemplates, prop, config[prop]);
            }
        }
    },

    SET_EXISTING_FIELDS(state, config) {
        for (const prop in config) {
            if (Object.hasOwnProperty.call(config, prop)) {
                Vue.set(state.existingFields, prop, config[prop]);
            }
        }
    },

    SET_EXISTING_NOTIFICATIONS(state, config) {
        for (const prop in config) {
            if (Object.hasOwnProperty.call(config, prop)) {
                Vue.set(state.existingNotifications, prop, config[prop]);
            }
        }
    },
};

// Actions exist to call mutations. Actions are also responsible in performing any
// or all asynchronous calls prior to committing to mutations.
// Actions have access to a context object that provides access to state (with context.state),
// to getters (with context.getters), and to the commit function (with context.commit).
const actions = {
    setReservedHandles(context, config) {
        context.commit('SET_RESERVED_HANDLES', config);
    },

    setEmailTemplates(context, config) {
        context.commit('SET_EMAIL_TEMPLATES', config);
    },

    setExistingFields(context, config) {
        context.commit('SET_EXISTING_FIELDS', config);
    },

    setExistingNotifications(context, config) {
        context.commit('SET_EXISTING_NOTIFICATIONS', config);
    },
};

// Getters are to a Vuex store what computed properties are to a Vue component.
// Getters are primarily used to perform some calculation/manipulation to store state
// before having that information accessible to components.
const getters = {
    reservedHandles: (state) => () => {
        return state.reservedHandles;
    },

    emailTemplates: (state) => () => {
        return state.emailTemplates;
    },

    existingFields: (state) => () => {
        return state.existingFields;
    },

    existingNotifications: (state) => () => {
        return state.existingNotifications;
    },
};

export default {
    namespaced: true,
    state,
    mutations,
    actions,
    getters,
};
