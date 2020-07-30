const globals = require('./utils/globals');

class FormieBaseForm {
    constructor(settings = {}) {
        this.formId = `#formie-form-${settings.formId}`;
        this.$form = document.querySelector(this.formId);
        this.settings = settings;

        if (!this.$form) {
            return;
        }

        this.$form._baseForm = this;

        // Add helper classes to fields when their inputs are focused, have values etc.
        this.registerFieldEvents(this.$form);

        // Hijack the form's submit handler, in case we need to do something
        this.$form.addEventListener('submit', (e) => {
            e.preventDefault();

            const beforeSubmitEvent = new CustomEvent('onBeforeFormieSubmit', {
                bubbles: true,
                cancelable: true,
                detail: {
                    submitHandler: this,
                },
            });

            if (!this.$form.dispatchEvent(beforeSubmitEvent)) {
                return;
            }

            // Add a little delay for UX
            setTimeout(() => {
                const validateEvent = new CustomEvent('onFormieValidate', {
                    bubbles: true,
                    cancelable: true,
                    detail: {
                        submitHandler: this,
                    },
                });

                if (!this.$form.dispatchEvent(validateEvent)) {
                    return;
                }

                this.submitForm();
            }, 300);
        }, false);
    }

    submitForm() {
        // Check if we're going back, and attach an input to tell formie not to validate
        if (this.$form.goToPage) {
            const $backButtonInput = document.createElement('input');
            $backButtonInput.setAttribute('type', 'hidden');
            $backButtonInput.setAttribute('name', 'goToPageId');
            $backButtonInput.setAttribute('value', this.$form.goToPage);
            this.$form.appendChild($backButtonInput);
        }

        const submitEvent = new CustomEvent('onFormieSubmit', {
            bubbles: true,
            cancelable: true,
            detail: {
                submitHandler: this,
            },
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

    formAfterSubmit() {
        this.$form.dispatchEvent(new CustomEvent('onAfterFormieSubmit', {
            bubbles: true,
        }));
    }

    formSubmitError() {
        this.$form.dispatchEvent(new CustomEvent('onFormieSubmitError', {
            bubbles: true,
        }));
    }

    registerFieldEvents($element) {
        const $wrappers = $element.querySelectorAll('.fui-field');

        $wrappers.forEach($wrapper => {
            const $input = $wrapper.querySelector('.fui-input, .fui-select');

            if ($input) {
                $input.addEventListener('input', event => {
                    $wrapper.dispatchEvent(new CustomEvent('input', {
                        bubbles: false,
                        detail: {
                            input: event.target,
                        },
                    }));
                });

                $input.addEventListener('focus', event => {
                    $wrapper.dispatchEvent(new CustomEvent('focus', {
                        bubbles: false,
                        detail: {
                            input: event.target,
                        },
                    }));
                });

                $input.addEventListener('blur', event => {
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
}

window.FormieBaseForm = FormieBaseForm;

