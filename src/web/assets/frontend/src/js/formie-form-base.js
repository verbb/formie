import { t } from './utils/utils';

import { FormieFormTheme } from './formie-form-theme';

// We use two event layers:

// 1. EventManager (DOM Events)
// - Manages all real DOM event listeners (submit, input, focus, blur, onFormieValidate, etc.)
// - Prevents double-binding by using the event string as a unique key.
// - Automatically removes all listeners when the form is destroyed.
// - Use when the event originates from the browser or a DOM node.

// 2. EventBus (Logical / Internal Events)
// - Internal pub/sub system with replay ("sticky") behaviour.
// - triggerEvent(name, payload) stores the last payload.
// - registerEvent(name, callback) immediately replays the last payload if fired earlier.
// - Perfect for lazy-loaded modules (validators, captchas, payment setup).
// - Use for form lifecycle events that are NOT DOM events.

// Rule of thumb:
// DOM = "something happened in the browser"
// Bus = "something happened in the application"

// This separation ensures clean re-initialisation, predictable ordering,
// lazy-loaded compatibility, and zero memory leaks.

class EventManager {
    constructor() {
        this._listeners = new Set(); // { element, type, handler, options, key }
    }

    on(element, type, handler, { key, options } = {}) {
        if (!element) { return; }

        // If a key is provided, ensure only a single listener per key.
        if (key) {
            this.offByKey(key);
        }

        const record = {
            element, type, handler, options, key,
        };

        element.addEventListener(type, handler, options);
        this._listeners.add(record);

        // Return unsubscribe function
        return () => { return this.off(element, type, handler, options); };
    }

    off(element, type, handler, options) {
        for (const record of this._listeners) {
            if (
                record.element === element &&
                record.type === type &&
                record.handler === handler &&
                record.options === options
            ) {
                record.element.removeEventListener(record.type, record.handler, record.options);
                this._listeners.delete(record);
            }
        }
    }

    offByKey(key) {
        if (!key) { return; }

        for (const record of this._listeners) {
            if (record.key === key) {
                record.element.removeEventListener(record.type, record.handler, record.options);
                this._listeners.delete(record);
            }
        }
    }

    offAll() {
        for (const record of this._listeners) {
            record.element.removeEventListener(record.type, record.handler, record.options);
        }
        this._listeners.clear();
    }
}

class EventBus {
    constructor() {
        this._listeners = new Map(); // eventName -> Set<fn>
        this._lastPayload = new Map(); // eventName -> payload
    }

    on(eventName, callback, { replay = true } = {}) {
        if (!this._listeners.has(eventName)) {
            this._listeners.set(eventName, new Set());
        }

        this._listeners.get(eventName).add(callback);

        // If this event already happened and the listener wants replay, call immediately.
        if (replay && this._lastPayload.has(eventName)) {
            callback(this._lastPayload.get(eventName));
        }

        // Return unsubscribe function for convenience (optional, but nice to have)
        return () => {
            this.off(eventName, callback);
        };
    }

    off(eventName, callback) {
        const set = this._listeners.get(eventName);
        if (!set) { return; }

        set.delete(callback);

        if (!set.size) {
            this._listeners.delete(eventName);
            this._lastPayload.delete(eventName);
        }
    }

    emit(eventName, payload, { sticky = true } = {}) {
        if (sticky) {
            this._lastPayload.set(eventName, payload);
        }

        const set = this._listeners.get(eventName);
        if (!set) { return; }

        for (const fn of set) {
            fn(payload);
        }
    }

    clear() {
        this._listeners.clear();
        this._lastPayload.clear();
    }
}

export class FormieFormBase {
    constructor($form, config = {}) {
        this.$form = $form;
        this.config = config;
        this.settings = config.settings;

        this.eventManager = new EventManager();
        this.eventBus = new EventBus();
        this.destroyed = false;

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
        });
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

    destroy() {
        if (this.destroyed) {
            return;
        }

        this.destroyed = true;

        // Notify external listeners (keeping your existing public API)
        this.formDestroy({
            form: this,
        });

        // Clean up all DOM listeners managed by this form
        if (this.eventManager) {
            this.eventManager.offAll();
        }

        if (this.eventBus) {
            this.eventBus.clear();
        }

        // Clear reference from DOM node if you want
        if (this.$form && this.$form.form === this) {
            delete this.$form.form;
        }
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

            // Trigger an (used) event for users to do any last minute things
            if (!this.validateCustom()) {
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

    validateError(data = {}) {
        this.$form.dispatchEvent(new CustomEvent('onFormieValidateError', {
            bubbles: true,
            detail: data,
        }));
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

    validateCustom() {
        // Create an event for custom actions, separate to validation
        const validateEvent = this.eventObject('onFormieCustomValidate', {
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

    addEventListener(element, event, func, options) {
        // If the form is marked as destroyed, don't add any more event listeners.
        if (this.destroyed) {
            return;
        }

        if (!element || !event || !func) {
            return;
        }

        const type = event.split('.')[0]; // DOM event name: "click", "onFormiePaymentValidate", etc.
        const hasNamespace = event.includes('.');

        // Only dedupe when we have a namespace.
        // Plain "click"/"input"/"blur" can have multiple listeners.
        const key = hasNamespace ? event : undefined;

        this.eventManager.on(element, type, func, { key, options });
    }

    removeEventListener(event) {
        this.eventManager.offByKey(event);
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
        const classes = this.getThemeConfigAttributes(key).class;

        if (Array.isArray(classes)) {
            return classes.filter((c) => { return typeof c === 'string' && c.trim(); }).join(' ').trim();
        }

        if (typeof classes === 'string') {
            return classes.trim();
        }

        return '';
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

    registerEvent(eventName, callback, options) {
        return this.eventBus.on(eventName, callback, options);
    }

    triggerEvent(eventName, payload, options) {
        this.eventBus.emit(eventName, payload, options);
    }
}
