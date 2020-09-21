const globals = require('./utils/globals');
import { isEmpty } from './utils/utils';

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
        // Initialize the form class with the `data-config` param on the form
        var formConfig = JSON.parse($form.getAttribute('data-config'));

        if (!formConfig) {
            console.error('Unable to parse `data-config` form attribute for config. Ensure this attribute exists on your form and contains valid JSON.');

            return;
        }

        // See if we need to init additional, conditional JS (field, captchas, etc)
        var registeredJs = formConfig.registeredJs || [];

        // Add an instance to this factory to the form config
        formConfig.Formie = this;

        // Create the form class, save it to our collection
        var form = new FormieFormBase(formConfig);

        this.forms.push(form);

        // Is there any additional JS config registered for this form?
        if (registeredJs.length) {
            // Create a container to add these items to, so we can destroy them later
            form.$registeredJs = document.createElement('div');
            form.$registeredJs.setAttribute('data-fui-scripts', formConfig.formId);
            document.body.appendChild(form.$registeredJs);

            // Create a `<script>` for each registered JS
            registeredJs.forEach((config) => {
                var $script = document.createElement('script');

                // Check if we've provided an external script to load. Ensure they're deferred so they don't block
                // and use the onload call to trigger any actual scripts once its been loaded.
                if (config.src) {
                    $script.src = config.src;
                    $script.defer = true;

                    // Parse any JS onload code we have. Yes, I'm aware of `eval()` but its pretty safe as it's
                    // only provided from the field or captcha class - no user data.
                    $script.onload = function() {
                        if (config.onload) {
                            eval(config.onload);
                        }
                    };
                }

                form.$registeredJs.appendChild($script);
            });
        }
    }

    getForm($form) {
        return this.forms.find((form) => {
            return form.$form == $form;
        });
    }

    getFormById(id) {
        return this.forms.find((form) => {
            if (form.config) {
                return form.config.formId == id;
            }
        });
    }

    getFormByHandle(handle) {
        return this.forms.find((form) => {
            if (form.config) {
                return form.config.formHandle == handle;
            }
        });
    }

    destroyForm($form) {
        var form = this.getForm($form);

        if (!form) {
            return;
        }

        var index = this.forms.indexOf(form);

        if (index === -1) {
            return;
        }

        // Delete any additional scripts for the form - if any
        if (form.$registeredJs && form.$registeredJs.parentNode) {
            form.$registeredJs.parentNode.removeChild(form.$registeredJs);
        }

        // Remove all event listeners attached to this form
        if (!isEmpty(form.listeners)) {
            Object.keys(form.listeners).forEach(eventKey => {
                form.removeEventListener(eventKey);
            });
        }

        // Destroy Bouncer events
        if (form.formTheme && form.formTheme.validator) {
            form.formTheme.validator.destroy();
        }

        // Delete it from the factory
        delete this.forms[index];
    }
}

window.Formie = Formie;
