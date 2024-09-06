import { t } from './utils/utils';

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

            this.initSubmit();
        }, false);
    }

    initSubmit() {
        const beforeSubmitEvent = this.eventObject('onBeforeFormieSubmit', {
            submitHandler: this,
        });

        if (!this.$form.dispatchEvent(beforeSubmitEvent)) {
            return;
        }

        this.processSubmit();
    }

    processSubmit(skip = []) {
        // Add a little delay for UX
        setTimeout(() => {
            // Call the validation hooks
            if (!this.validate() || !this.afterValidate()) {
                return;
            }

            // Trigger Captchas
            if (!skip.includes('captcha') && !this.validateCaptchas()) {
                return;
            }

            // Trigger Payment Integrations
            if (!skip.includes('payment') && !this.validatePayment()) {
                return;
            }

            // Proceed with submitting the form, which raises other validation events
            this.submitForm();
        }, 300);
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
        // Add redirect behaviour for iframes to control the target
        data.redirectTarget = data.redirectTarget || window;

        this.$form.dispatchEvent(new CustomEvent('onAfterFormieSubmit', {
            bubbles: true,
            detail: data,
        }));

        // Ensure that once completed, we re-fetch the captcha value, which will have expired
        if (!data.nextPageId) {
            // Use `this.config.Formie` just in case we're not loading thie script in the global window
            // (i.e. when users import this script in their own).
            this.config.Formie.refreshFormTokens(this);
        }
    }

    formSubmitError(data = {}) {
        this.$form.dispatchEvent(new CustomEvent('onFormieSubmitError', {
            bubbles: true,
            detail: data,
        }));
    }

    formDestroy(data = {}) {
        this.$form.dispatchEvent(new CustomEvent('onFormieDestroy', {
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
        // If the form is marked as destroyed, don't add any more event listeners.
        // This can often happen with captchas or payment integrations which are done as they appear on page.
        if (!this.destroyed) {
            this.listeners[event] = { element, func };
            const eventName = event.split('.')[0];

            element.addEventListener(eventName, this.listeners[event].func);
        }
    }

    removeEventListener(event) {
        const eventInfo = this.listeners[event] || {};

        if (eventInfo && eventInfo.element && eventInfo.func) {
            const eventName = event.split('.')[0];

            eventInfo.element.removeEventListener(eventName, eventInfo.func);
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

    getThemeConfigAttributes(key) {
        const attributes = this.settings.themeConfig || {};

        return attributes[key] || {};
    }

    getClasses(key) {
        return this.getThemeConfigAttributes(key).class || [];
    }

    applyThemeConfig($element, key, applyClass = true) {
        const attributes = this.getThemeConfigAttributes(key);

        if (attributes) {
            Object.entries(attributes).forEach(([attribute, value]) => {
                if (attribute === 'class' && !applyClass) {
                    return;
                }

                $element.setAttribute(attribute, value);
            });
        }
    }
}
