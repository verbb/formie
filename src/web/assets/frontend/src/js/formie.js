const globals = require('./utils/globals');

import { FormieBaseForm } from './formie-base-form';

class Formie {
    constructor() {
        const self = this;
        this.forms = {};

        // Just in case...
        window.FormieForms = window.FormieForms || [];

        // Initialize any configs available to us now
        window.FormieForms.forEach(formConfig => {
            this.initForm(formConfig);
        });

        // Listen to changes on the array, and trigger initializing. This is handy when
        // forms are lazy-loaded, and can be auto-initialized. But also deals with the scenario
        // of this script being run first, before configs are parsed.
        window.FormieForms.push = function() {
            Array.prototype.push.apply(this, arguments);

            if (arguments && arguments[0]) {
                self.initForm(arguments[0]);
            }
        };
    }

    initForm(formConfig) {
        // Don't init a form until the document is ready
        document.addEventListener('DOMContentLoaded', function(event) {
            this.forms[formConfig.formHandle] = new FormieBaseForm(formConfig);
        });
    }

    getFormByHandle(handle) {
        return this.forms[handle];
    }
}

// Self-initialize
window.Formie = new Formie();
