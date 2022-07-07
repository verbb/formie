// CSS needs to be imported here as it's treated as a module
import '@/scss/style.scss';

// Accept HMR as per: https://vitejs.dev/guide/api-hmr.html
if (import.meta.hot) {
    import.meta.hot.accept();
}

import { createVueApp } from './config.js';
import { generateHandle, getNextAvailableHandle } from '@utils/string';

//
// Start Vue Apps
//

if (typeof Craft.Formie === typeof undefined) {
    Craft.Formie = {};
}

Craft.Formie.NewForm = Garnish.Base.extend({
    init(settings) {
        const app = createVueApp({
            data() {
                return {
                    name: settings.name,
                    handle: settings.handle,
                    handles: [],
                };
            },

            watch: {
                name(val) {
                    const maxHandleLength = settings.maxFormHandleLength;

                    // Let's get smart about generating a handle. Check if its unique - if it isn't, make it unique
                    // Be sure to restrict handles well below their limit
                    this.handle = getNextAvailableHandle(this.handles, generateHandle(this.name), 0).substr(0, maxHandleLength);
                },
            },

            created() {
                this.handles = settings.formHandles.concat(settings.reservedHandles);
            },

            mounted() {
                this.$el.querySelector('[name="title"]').focus();

                this.$nextTick().then(() => {
                    Craft.initUiElements();
                });
            },
        });

        app.mount('#fui-new-form');
    },
});


// Re-broadcast the custom `vite-script-loaded` event so that we know that this module has loaded
// Needed because when <script> tags are appended to the DOM, the `onload` handlers
// are not executed, which happens in the field Settings page, and in slideouts
// Do this after the document is ready to ensure proper execution order
$(document).ready(() => {
    document.dispatchEvent(new CustomEvent('vite-script-loaded', { detail: { path: 'src/js/formie-form-new.js' } }));
});
