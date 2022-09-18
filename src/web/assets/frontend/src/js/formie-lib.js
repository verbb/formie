const globals = require('./utils/globals');
import { isEmpty } from './utils/utils';

import { FormieFormBase } from './formie-form-base';

export class Formie {
    constructor() {
        this.forms = [];
    }

    initForms() {
        this.$forms = document.querySelectorAll('form[data-fui-form]') || [];

        // We use this in the CP, where it's a bit tricky to add a form ID. So check just in case.
        // Might also be handy for front-end too!
        if (!this.$forms.length) {
            this.$forms = document.querySelectorAll('div[data-fui-form]') || [];
        }

        this.$forms.forEach(($form) => {
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

    initForm($form, formConfig = {}) {
        if (isEmpty(formConfig)) {
            // Initialize the form class with the `data-fui-form` param on the form
            formConfig = JSON.parse($form.getAttribute('data-fui-form'));
        }

        if (isEmpty(formConfig)) {
            console.error('Unable to parse `data-fui-form` form attribute for config. Ensure this attribute exists on your form and contains valid JSON.');

            return;
        }

        // See if we need to init additional, conditional JS (field, captchas, etc)
        const registeredJs = formConfig.registeredJs || [];

        // Add an instance to this factory to the form config
        formConfig.Formie = this;

        // Create the form class, save it to our collection
        const form = new FormieFormBase($form, formConfig);

        this.forms.push(form);

        // Find all `data-field-config` attributes for the current page and form
        // and build an object of them to initialize when loaded.
        form.fieldConfigs = this.parseFieldConfig($form, $form);

        // Is there any additional JS config registered for this form?
        if (registeredJs.length) {
            // Create a container to add these items to, so we can destroy them later
            form.$registeredJs = document.createElement('div');
            form.$registeredJs.setAttribute('data-fui-scripts', formConfig.formId);
            document.body.appendChild(form.$registeredJs);

            // Create a `<script>` for each registered JS
            registeredJs.forEach((config) => {
                const $script = document.createElement('script');

                // Check if we've provided an external script to load. Ensure they're deferred so they don't block
                // and use the onload call to trigger any actual scripts once its been loaded.
                if (config.src) {
                    $script.src = config.src;
                    $script.defer = true;

                    // Initialize all matching fields - their config is already rendered in templates
                    $script.onload = () => {
                        if (config.module) {
                            const fieldConfigs = form.fieldConfigs[config.module];

                            // Handle multiple fields on a page, creating a new JS class instance for each
                            if (fieldConfigs && Array.isArray(fieldConfigs) && fieldConfigs.length) {
                                fieldConfigs.forEach((fieldConfig) => {
                                    this.initJsClass(config.module, fieldConfig);
                                });
                            }

                            // Handle integrations that have global settings, instead of per-field
                            if (config.settings) {
                                this.initJsClass(config.module, {
                                    $form,
                                    ...config.settings,
                                });
                            }

                            // Special handling for some JS modules
                            if (config.module === 'FormieConditions') {
                                this.initJsClass(config.module, { $form });
                            }
                        }
                    };
                }

                form.$registeredJs.appendChild($script);
            });
        }
    }

    initJsClass(className, params) {
        const moduleClass = window[className];

        if (moduleClass) {
            new moduleClass(params);
        }
    }

    // Note the use of $form and $element to handle Repeater
    parseFieldConfig($element, $form) {
        const config = {};

        $element.querySelectorAll('[data-field-config]').forEach(($field) => {
            let fieldConfig = JSON.parse($field.getAttribute('data-field-config'));

            // Some fields supply multiple modules, so normalise for ease-of-processing
            if (!Array.isArray(fieldConfig)) {
                fieldConfig = [fieldConfig];
            }

            fieldConfig.forEach((nestedFieldConfig) => {
                if (!config[nestedFieldConfig.module]) {
                    config[nestedFieldConfig.module] = [];
                }

                // Provide field classes with the data they need
                config[nestedFieldConfig.module].push({
                    $form,
                    $field,
                    ...nestedFieldConfig,
                });

            });
        });

        return config;
    }

    getForm($form) {
        return this.forms.find((form) => {
            return form.$form == $form;
        });
    }

    getFormById(id) {
        // eslint-disable-next-line array-callback-return
        return this.forms.find((form) => {
            if (form.config) {
                return form.config.formId == id;
            }
        });
    }

    getFormByHandle(handle) {
        // eslint-disable-next-line array-callback-return
        return this.forms.find((form) => {
            if (form.config) {
                return form.config.formHandle == handle;
            }
        });
    }

    destroyForm($form) {
        const form = this.getForm($form);

        if (!form) {
            return;
        }

        const index = this.forms.indexOf(form);

        if (index === -1) {
            return;
        }

        // Delete any additional scripts for the form - if any
        if (form.$registeredJs && form.$registeredJs.parentNode) {
            form.$registeredJs.parentNode.removeChild(form.$registeredJs);
        }

        // Remove all event listeners attached to this form
        if (!isEmpty(form.listeners)) {
            Object.keys(form.listeners).forEach((eventKey) => {
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
