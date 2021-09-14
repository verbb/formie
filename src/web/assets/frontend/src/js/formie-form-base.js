const globals = require('./utils/globals');

import { FormieFormTheme } from './formie-form-theme';

export class FormieFormBase {
    constructor(config = {}) {
        this.formId = `#${config.formHashId}`;
        this.$form = document.querySelector(this.formId);
        this.config = config;
        this.settings = config.settings;
        this.listeners = {};

        if (!this.$form) {
            return;
        }

        this.$form.form = this;

        if (this.settings.outputJsTheme) {
            this.formTheme = new FormieFormTheme(this.config);
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
                // Create an event for before validation. This is mostly for captchas
                const captchaValidateEvent = this.eventObject('onFormieCaptchaValidate', {
                    submitHandler: this,
                });

                if (!this.$form.dispatchEvent(captchaValidateEvent)) {
                    return;
                }

                // Call the validation hooks
                if (!this.validate() || !this.afterValidate()) {
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

    submitForm() {
        // Check if we're going back, and attach an input to tell formie not to validate
        if (this.$form.goBack) {
            const $backButtonInput = document.createElement('input');
            $backButtonInput.setAttribute('type', 'hidden');
            $backButtonInput.setAttribute('name', 'goingBack');
            $backButtonInput.setAttribute('value', 'true');
            this.$form.appendChild($backButtonInput);
        }

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
        const $wrappers = $element.querySelectorAll('.fui-field');

        $wrappers.forEach($wrapper => {
            const $input = $wrapper.querySelector('.fui-input, .fui-select');

            if ($input) {
                this.addEventListener($input, 'input', event => {
                    $wrapper.dispatchEvent(new CustomEvent('input', {
                        bubbles: false,
                        detail: {
                            input: event.target,
                        },
                    }));
                });

                this.addEventListener($input, 'focus', event => {
                    $wrapper.dispatchEvent(new CustomEvent('focus', {
                        bubbles: false,
                        detail: {
                            input: event.target,
                        },
                    }));
                });

                this.addEventListener($input, 'blur', event => {
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
        let eventInfo = this.listeners[event] || {};

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
}
