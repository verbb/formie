import Vue from 'vue';
import Axios from 'axios';
import { stringify } from 'qs';

import * as Plugins from './plugins';
import * as Filters from './filters';

const globals = require('./utils/globals');
const sanitizeHtml = require('sanitize-html');

//
// Create a config object to pass back to Vue.js when setting up for the first time
//

const Config = {
    install(Vue) {
        // Used to keep track of a currently edited field' component.
        Vue.prototype.$editingField = null;

        // Used to keep track of a currently edited notification' component.
        Vue.prototype.$editingNotification = null;

        // Global events can be accessed via `this.$events`
        Vue.prototype.$events = new Vue();

        // Provide sanitization for HTML
        Vue.prototype.$sanitize = sanitizeHtml;

        //
        // Setup Globals
        //

        // Attach Axios instance to Vue, so we can use `this.$axios.get('/')`
        Vue.prototype.$axios = Axios.create({
            transformRequest: [
                function(data, headers) {
                    const craftHeaders = Craft._actionHeaders();
                    headers['X-Requested-With'] = 'XMLHttpRequest';
                    for (const k in craftHeaders) {
                        if (Object.prototype.hasOwnProperty.call(craftHeaders, k)) {
                            headers[k] = craftHeaders[k];
                        }
                    }

                    // If this is FormData, no need to serialize
                    if (data instanceof FormData) {
                        return data;
                    }

                    return stringify(data);
                },
            ],
        });

        //
        // Setup Plugins
        //

        Object.values(Plugins).forEach((Plugin) => {
            Vue.use(Plugin);
        });

        //
        // Setup Filters
        //

        Object.values(Filters).forEach((Filter) => {
            Vue.use(Filter);
        });
    },
};

export default Config;
