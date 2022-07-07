// CSS needs to be imported here as it's treated as a module
import '@/scss/style.scss';

// Accept HMR as per: https://vitejs.dev/guide/api-hmr.html
if (import.meta.hot) {
    import.meta.hot.accept();
}

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

// Re-broadcast the custom `vite-script-loaded` event so that we know that this module has loaded
// Needed because when <script> tags are appended to the DOM, the `onload` handlers
// are not executed, which happens in the field Settings page, and in slideouts
// Do this after the document is ready to ensure proper execution order
$(document).ready(() => {
    document.dispatchEvent(new CustomEvent('vite-script-loaded', { detail: { path: 'src/js/formie-integration-settings.js' } }));
});
