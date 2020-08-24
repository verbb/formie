import Vue from 'vue';
import config from '../../../forms/src/js/config.js';

// Apply our config settings, which do most of the grunt work
Vue.use(config);

import IntegrationConnect from './components/IntegrationConnect.vue';

new Vue({
    el: '.fui-integrations-settings #details .meta',
    delimiters: ['${', '}'],

    components: {
        IntegrationConnect,
    },
});

