import { t } from './utils/utils';

import { FormieFormTheme } from './formie-form-theme';

// Create an event dispatcher for registering and triggering events, no matter the `dispatchEvent` or `addEventListener` order.
// This is useful for registering validation rules, where fields that are lazy-loaded might register validators, but are
// triggered after the Form Theme's `dispatchEvent`.
class EventDispatcher {
    constructor() {
        this.listeners = new Map();
        this.dispatchedEvents = new Map();
    }

    addEventListener(eventName, callback) {
        if (!this.listeners.has(eventName)) {
            this.listeners.set(eventName, []);
        }

        this.listeners.get(eventName).push(callback);

        // If there are pending events, execute the callbacks for those events
        if (this.dispatchedEvents.has(eventName)) {
            const eventDetail = this.dispatchedEvents.get(eventName);

            callback(eventDetail);
        }
    }

    removeEventListener(eventName, callback) {
        if (!this.listeners.has(eventName)) {
            return;
        }

        const index = this.listeners.get(eventName).indexOf(callback);

        if (index !== -1) {
            this.listeners.get(eventName).splice(index, 1);
        }
    }

    dispatchEvent(eventName, eventDetail) {
        if (!this.listeners.has(eventName)) {
            // If there are no listeners, store the event for future listeners
            this.dispatchedEvents.set(eventName, eventDetail);
            return;
        }

        const callbacks = this.listeners.get(eventName);

        callbacks.forEach((callback) => {
            callback(eventDetail);
        });
    }
}

export class FormieFormBase {
    constructor($form, config = {}) {
        this.$form = $form;
        this.config = config;
        this.settings = config.settings;
        this.listeners = {};
        this.eventDispatcher = new EventDispatcher();

        if (!this.$form) {
            return;
        }

        this.$form.form = this;

        if (this.settings.outputJsTheme) {
            this.formTheme = new FormieFormTheme(this.$form, this.config);
        }

        // Add helper classes to fields when their inputs are focused, have values etc.
        this.registerFieldEvents(this.$form);

        // Emit a custom event to let scripts know the Formie class is ready
        this.$form.dispatchEvent(new CustomEvent('onFormieReady', {
            bubbles: true,
            detail: {
                form: this,
            },
        }));

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

    processSubmit() {
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

                // Special-case for adding just the attribute without "true" as the value
                if (value === true) {
                    $element.setAttribute(attribute, '');
                } else {
                    $element.setAttribute(attribute, value);
                }
            });
        }
    }

    registerEvent(eventName, callback) {
        this.eventDispatcher.addEventListener(eventName, callback);
    }

    triggerEvent(eventName, options) {
        this.eventDispatcher.dispatchEvent(eventName, options);
    }
}
