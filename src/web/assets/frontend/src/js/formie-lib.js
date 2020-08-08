const globals = require('./utils/globals');

import { FormieFormBase } from './formie-form-base';

export class Formie {
    constructor() {
        this.forms = [];
    }

    initForms() {
        this.$forms = document.querySelectorAll('form[id^="formie-form-"]') || [];

        this.$forms.forEach($form => {
            this.initForm($form);
        });

        // Emit a custom event to let scripts know the Formie class is ready
        document.dispatchEvent(new CustomEvent('onFormieInit', {
            bubbles: true,
            detail: {
                formie: this,
            },
        }));
    }

    initForm($form) {
        var formConfig = JSON.parse($form.getAttribute('data-config'));

        this.forms.push(new FormieFormBase(formConfig));
    }

    getForm($form) {
        return this.forms.find((form) => {
            return form.$form == $form;
        });
    }

    getFormById(id) {
        return this.forms.find((form) => {
            return form.config.formId == id;
        });
    }

    getFormByHandle(handle) {
        return this.forms.find((form) => {
            return form.config.formHandle == handle;
        });
    }
}
