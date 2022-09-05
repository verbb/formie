const globals = require('./utils/globals');

import { FormieFormTheme } from './formie-form-theme';

export class FormieFormBase {
    constructor($form, config = {}) {
        this.$form = $form;
        this.config = config;
        this.settings = config.settings;
        this.listeners = {};

        if (!this.$form) {
            return;
        }

        this.$form.form = this;

        if (this.settings.outputJsTheme) {
            this.formTheme = new FormieFormTheme(this.$form, this.config);
        }

        // Add helper classes to fields when their inputs are focused, have values etc.
        this.registerFieldEvents(this.$form);

        // Hijack the form's submit handler, in case we need to do something
        this.addEventListener(this.$form, 'submit', (e) => {
            e.preventDefault();

            const beforeSubmitEvent = this.eventObject('onBeforeFormieSubmit', {
                submitHandler: this,
            });

            if (!this.$form.dispatchEvent(beforeSubmitEvent)) {
                return;
            }

            // Add a little delay for UX
            setTimeout(() => {
                // Call the validation hooks
                if (!this.validate() || !this.afterValidate()) {
                    return;
                }

                // Trigger Captchas
                if (!this.validateCaptchas()) {
                    return;
                }

                // Trigger Payment Integrations
                if (!this.validatePayment()) {
                    return;
                }

                // Proceed with submitting the form, which raises other validation events
                this.submitForm();
            }, 300);
        }, false);
    }

    validate() {
        // Create an event for front-end validation (our own JS)
        const validateEvent = this.eventObject('onFormieValidate', {
            submitHandler: this,
        });

        return this.$form.dispatchEvent(validateEvent);
    }

    afterValidate() {
        // Create an event for after validation. This is mostly for third-parties.
        const afterValidateEvent = this.eventObject('onAfterFormieValidate', {
            submitHandler: this,
        });

        return this.$form.dispatchEvent(afterValidateEvent);
    }

    validateCaptchas() {
        // Create an event for captchas, separate to validation
        const validateEvent = this.eventObject('onFormieCaptchaValidate', {
            submitHandler: this,
        });

        return this.$form.dispatchEvent(validateEvent);
    }

    validatePayment() {
        // Create an event for payments, separate to validation
        const validateEvent = this.eventObject('onFormiePaymentValidate', {
            submitHandler: this,
        });

        return this.$form.dispatchEvent(validateEvent);
    }

    submitForm() {
        const submitEvent = this.eventObject('onFormieSubmit', {
            submitHandler: this,
        });

        if (!this.$form.dispatchEvent(submitEvent)) {
            return;
        }

        if (this.settings.submitMethod === 'ajax') {
            this.formAfterSubmit();
        } else {
            this.$form.submit();
        }
    }

    formAfterSubmit(data = {}) {
        this.$form.dispatchEvent(new CustomEvent('onAfterFormieSubmit', {
            bubbles: true,
            detail: data,
        }));
    }

    formSubmitError(data = {}) {
        this.$form.dispatchEvent(new CustomEvent('onFormieSubmitError', {
            bubbles: true,
            detail: data,
        }));
    }

    registerFieldEvents($element) {
        const $wrappers = $element.querySelectorAll('[data-field-type]');

        $wrappers.forEach(($wrapper) => {
            const $input = $wrapper.querySelector('input, select');

            if ($input) {
                this.addEventListener($input, 'input', (event) => {
                    $wrapper.dispatchEvent(new CustomEvent('input', {
                        bubbles: false,
                        detail: {
                            input: event.target,
                        },
                    }));
                });

                this.addEventListener($input, 'focus', (event) => {
                    $wrapper.dispatchEvent(new CustomEvent('focus', {
                        bubbles: false,
                        detail: {
                            input: event.target,
                        },
                    }));
                });

                this.addEventListener($input, 'blur', (event) => {
                    $wrapper.dispatchEvent(new CustomEvent('blur', {
                        bubbles: false,
                        detail: {
                            input: event.target,
                        },
                    }));
                });

                $wrapper.dispatchEvent(new CustomEvent('init', {
                    bubbles: false,
                    detail: {
                        input: $input,
                    },
                }));
            }
        });
    }

    addEventListener(element, event, func) {
        this.listeners[event] = { element, func };

        element.addEventListener(event.split('.')[0], this.listeners[event].func);
    }

    removeEventListener(event) {
        const eventInfo = this.listeners[event] || {};

        if (eventInfo && eventInfo.element && eventInfo.func) {
            eventInfo.element.removeEventListener(event.split('.')[0], eventInfo.func);
            delete this.listeners[event];
        }
    }

    eventObject(name, detail) {
        return new CustomEvent(name, {
            bubbles: true,
            cancelable: true,
            detail,
        });
    }

    getClasses(key) {
        const classes = this.settings.classes || {};

        return classes[key];
    }
}
