import { t, isEmpty, waitForElement } from './utils/utils';

import { FormieFormBase } from './formie-form-base';

export class Formie {
    constructor() {
        this.forms = [];
    }

    initForms(useObserver = true) {
        this.$forms = document.querySelectorAll('form[data-fui-form]') || [];

        // We use this in the CP, where it's a bit tricky to add a form ID. So check just in case.
        // Might also be handy for front-end too!
        if (!this.$forms.length) {
            this.$forms = document.querySelectorAll('div[data-fui-form]') || [];
        }

        this.$forms.forEach(($form) => {
            // Check if we want to use an `IntersectionObserver` to only initialize the form when visible
            if (useObserver) {
                const observer = new IntersectionObserver((entries) => {
                    if (entries[0].intersectionRatio !== 0) {
                        this.initForm($form);

                        // Stop listening to prevent multiple init - just in case
                        observer.disconnect();
                    }
                });

                observer.observe($form);
            } else {
                this.initForm($form);
            }
        });

        // Emit a custom event to let scripts know the Formie class is ready
        document.dispatchEvent(new CustomEvent('onFormieInitForms', {
            bubbles: true,
            detail: {
                formie: this,
            },
        }));
    }

    async initForm($form, formConfig = {}) {
        if (isEmpty(formConfig)) {
            // Initialize the form class with the `data-fui-form` param on the form
            formConfig = JSON.parse($form.getAttribute('data-fui-form'));
        }

        if (isEmpty(formConfig)) {
            console.error('Unable to parse `data-fui-form` form attribute for config. Ensure this attribute exists on your form and contains valid JSON.');

            return;
        }

        // Check if we are initializing a form multiple times
        const initializeForm = this.getFormByHashId(formConfig.formHashId);

        if (initializeForm) {
            // Wait until the form is destroyed first before initializing again
            await this.destroyForm(initializeForm);
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
            // Check if we've already loaded scripts for this form
            if (document.querySelector(`[data-fui-scripts="${formConfig.formHashId}"]`)) {
                console.warn(`Formie scripts already loaded for form #${formConfig.formHashId}.`);

                return;
            }

            // Create a container to add these items to, so we can destroy them later
            form.$registeredJs = document.createElement('div');
            form.$registeredJs.setAttribute('data-fui-scripts', formConfig.formHashId);
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

    getFormByHashId(hashId) {
        // eslint-disable-next-line array-callback-return
        return this.forms.find((form) => {
            if (form.config) {
                return form.config.formHashId == hashId;
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

    async destroyForm(form) {
        let $form;

        // Allow passing in a DOM element, or a FormieBaseForm object
        if (form instanceof FormieFormBase) {
            $form = form.$form;
        } else {
            $form = form;
            form = this.getForm($form);
        }

        if (!form || !$form) {
            return;
        }

        const index = this.forms.indexOf(form);

        if (index === -1) {
            return;
        }

        // Mark the form as being destroyed, so no more events get added while we try and remove them
        form.destroyed = true;

        // Delete any additional scripts for the form - if any
        if (form.$registeredJs && form.$registeredJs.parentNode) {
            form.$registeredJs.parentNode.removeChild(form.$registeredJs);
        }

        // Trigger an event (before events are removed)
        form.formDestroy({
            form,
        });

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
        this.forms.splice(index, 1);
    }

    refreshForCache(formHashId, callback) {
        const form = this.getFormByHashId(formHashId);

        if (!form) {
            console.error(`Unable to find form "${formHashId}".`);
            return;
        }

        this.refreshFormTokens(form, callback);
    }

    refreshFormTokens(form, callback) {
        const { formHashId, formHandle } = form.config;

        fetch(`/actions/formie/forms/refresh-tokens?form=${formHandle}`)
            .then((result) => { return result.json(); })
            .then((result) => {
                // Fetch the form we want to deal with
                const { $form } = form;

                // Update the CSRF input
                if (result.csrf.param) {
                    const $csrfInput = $form.querySelector(`input[name="${result.csrf.param}"]`);

                    if ($csrfInput) {
                        $csrfInput.value = result.csrf.token;

                        console.log(`${formHashId}: Refreshed CSRF input %o.`, result.csrf);
                    } else {
                        console.error(`${formHashId}: Unable to locate CSRF input for "${result.csrf.param}".`);
                    }
                } else {
                    console.error(`${formHashId}: Missing CSRF token information in cache-refresh response.`);
                }

                // Update any captchas
                if (result.captchas) {
                    Object.entries(result.captchas).forEach(([key, value]) => {
                        // In some cases, the captcha input might not have loaded yet, as some are dynamically created
                        // (see Duplicate and JS captchas). So wait for the element to exist first
                        waitForElement(`input[name="${value.sessionKey}"]`, $form).then(($captchaInput) => {
                            if (value.value) {
                                $captchaInput.value = value.value;

                                console.log(`${formHashId}: Refreshed "${key}" captcha input %o.`, value);
                            }
                        });

                        // Add a timeout purely for logging, in case the element doesn't resolve in a reasonable time
                        setTimeout(() => {
                            if (!$form.querySelector(`input[name="${value.sessionKey}"]`)) {
                                console.error(`${formHashId}: Unable to locate captcha input for "${key}".`);
                            }
                        }, 10000);
                    });
                }

                // Update the form's hash (if using Formie's themed JS)
                if (form.formTheme) {
                    form.formTheme.updateFormHash();
                }

                // Fire a callback for users to do other bits
                if (callback) {
                    callback(result);
                }
            });
    }
}

window.Formie = Formie;
