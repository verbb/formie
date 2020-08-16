import Vue from 'vue';
import config from '../../../forms/src/js/config.js';

import get from 'lodash/get';
import isEmpty from 'lodash/isEmpty';

// Apply our config settings, which do most of the grunt work
Vue.use(config);

import IntegrationCheck from './components/IntegrationCheck.vue';

new Vue({
    el: '#fui-integrations-settings',
    delimiters: ['${', '}'],

    components: {
        IntegrationCheck,
    },

    data() {
        return {
            form: {},
        };
    },

    mounted() {
        // Init the sidebar tabs again, after Vue is loaded and replace jQuery
        if (Verbb.UI) {
            new Verbb.UI();
        }
    },

    methods: {
        get(object, key) {
            return get(object, key);
        },

        isEmpty(object) {
            return isEmpty(object);
        },
    },
});

