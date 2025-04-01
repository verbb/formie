// import Vue from 'vue';
import { findIndex, flatMap, omitBy } from 'lodash-es';

import { generateHandle, getNextAvailableHandle, newId } from '@utils/string';
import { clone } from '@utils/object';

// State is simply an object that contains the properties that need to be shared within the application:
// The state must return a function to make the module reusable.
// See: https://vuex.vuejs.org/en/modules.html#module-reuse
const state = [];

// Mutations are functions responsible in directly mutating store state.
// In Vuex, mutations always have access to state as the first argument.
// In addition, Actions may or may not pass in a payload as the second argument:
const mutations = {
    SET_NOTIFICATIONS(state, config) {
        for (const prop in config) {
            state[prop] = config[prop];

            // Typecast properly
            if (state[prop].customSettings && Array.isArray(state[prop].customSettings)) {
                state[prop].customSettings = {};
            }

            // Add a private ID to keep track of things in Vue
            state[prop].__id = newId();
        }
    },

    ADD_NOTIFICATION(state, payload) {
        const { data } = payload;

        state.push(data);
    },

    DELETE_NOTIFICATION(state, payload) {
        const { id } = payload;
        const index = findIndex(state, { __id: id });

        if (index > -1) {
            state.splice(index, 1);
        }
    },

    SET_PROP(state, payload) {
        const { id, prop, value } = payload;
        const index = findIndex(state, { __id: id });

        if (index > -1) {
            state[index][prop] = value;
        }
    },
};

// Actions exist to call mutations. Actions are also responsible in performing any
// or all asynchronous calls prior to committing to mutations.
// Actions have access to a context object that provides access to state (with context.state),
// to getters (with context.getters), and to the commit function (with context.commit).
const actions = {
    setNotifications(context, config) {
        context.commit('SET_NOTIFICATIONS', config);
    },

    addNotification(context, config) {
        context.commit('ADD_NOTIFICATION', config);
    },

    deleteNotification(context, config) {
        context.commit('DELETE_NOTIFICATION', config);
    },

    setProp(context, payload) {
        context.commit('SET_PROP', payload);
    },
};

// Getters are to a Vuex store what computed properties are to a Vue component.
// Getters are primarily used to perform some calculation/manipulation to store state
// before having that information accessible to components.
const getters = {
    serializedPayload: (state) => {
        // Filter the model to send back to the server
        const notifications = clone(state);

        // Filter out some unwanted data
        notifications.forEach((notification) => {
            delete notification.__id;
            delete notification.errors;
            delete notification.attachAssetsOptions;
            delete notification.attachAssetsHtml;
        });

        return notifications;
    },

    notificationIds: (state) => {
        return flatMap(state, 'id');
    },

    notificationHandles: (state) => {
        return flatMap(state, 'handle');
    },

    cloneNotification: (state, getters, rootState, rootGetters) => {
        return (notification) => {
            const newNotification = clone(notification);
            newNotification.id = newId();

            delete newNotification.errors;
            delete newNotification.hasError;
            delete newNotification.uid;
            delete newNotification.formId;

            // Generate a unique handle
            const generatedHandle = generateHandle(newNotification.name);
            const handles = getters.notificationHandles;
            newNotification.handle = getNextAvailableHandle(handles, generatedHandle, 0);

            return newNotification;
        };
    },

    notificationHandlesExcluding: (state, getters, rootState, rootGetters) => {
        return (id) => {
            const allNotifications = omitBy(state, { __id: id });
            let notificationHandles = flatMap(allNotifications, 'handle');

            // Fetch all reserved handles
            const reservedHandles = rootGetters['formie/reservedHandles']();
            notificationHandles = notificationHandles.concat(reservedHandles);

            return notificationHandles;
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
