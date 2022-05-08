import '@/scss/style.scss';

import { createVueApp } from './config';

import IntegrationConnect from '@components/IntegrationConnect.vue';

const app = createVueApp({
    // Set the delimeters to not mess around with Twig
    delimiters: ['${', '}'],

    components: {
        IntegrationConnect,
    },
});

app.mount('.fui-integrations-settings #details .meta');