import { createApp } from 'vue';
import mitt from 'mitt';

// Vue plugins
import VTooltip from 'floating-vue';
import { vfmPlugin } from 'vue-final-modal';
import VueUniqueId from '@/js/vendor/vue-unique-id';

import store from '@/js/store';

// Allows us to create a Vue app with global properties and loading plugins
export const createVueApp = (props) => {
    const app = createApp({
        // Set the delimeters to not mess around with Twig
        delimiters: ['${', '}'],

        // Add in any props defined for _this_ instance of the app, like components
        // data, methods, etc.
        ...props,
    });

    // Fix Vue warnings
    app.config.unwrapInjectedRef = true;

    //
    // Plugins
    // Include any globally-available plugins for the app.
    // Be careful about adding too much here. You can always include them per-app.
    //

    // Vue Final Modal
    // https://v3.vue-final-modal.org/
    app.use(vfmPlugin);

    // Vue Unique ID
    // Custom - waiting for https://github.com/berniegp/vue-unique-id
    app.use(VueUniqueId);

    // Vue Tooltips
    // https://github.com/Akryum/floating-vue
    app.use(VTooltip, {
        themes: {
            'fui-tooltip': {
                $extend: 'tooltip',
                delay: {
                    show: 0,
                    hide: 0,
                },
            },
        },
    });

    // Vuex for state management
    // https://vuex.vuejs.org
    app.use(store);

    //
    // Global properties
    // Create global properties here, shared across multiple Vue apps.
    //

    // Provide `t()` for Craft's translations in SFCs.
    app.config.globalProperties.t = Craft.t;

    // Global function to easily clone an object
    app.config.globalProperties.clone = function(value) {
        if (value === undefined) {
            return undefined;
        }

        return JSON.parse(JSON.stringify(value));
    },

    // Global events. Accessible via `this.$events` in SFCs.
    app.config.globalProperties.$events = mitt();

    // TODO: Try and figure out .env variables that aren't compiled
    app.config.globalProperties.$isDebug = !process.env.NODE_ENV || process.env.NODE_ENV === 'development';

    return app;
};
