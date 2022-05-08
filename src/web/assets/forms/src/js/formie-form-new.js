import '@/scss/style.scss';

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
            },
        });

        app.mount('#fui-new-form');
    },
});
