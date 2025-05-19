// eslint-disable-next-line
import { t, addClasses, removeClasses, eventKey, currencyToFloat } from '../utils/utils';
import { getFieldValue, getFieldLabel } from '../utils/fields';

export class FormiePaymentProvider {
    constructor(settings = {}) {
        this.initialized = false;
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.successClass = this.form.getClasses('success');
        this.successMessageClass = this.form.getClasses('successMessage');
        this.errorClass = this.form.getClasses('error');
        this.errorMessageClass = this.form.getClasses('errorMessage');
        this.isVisible = false;

        // Only initialize the field if it's visible. Use `IntersectionObserver` to check when visible
        // and also when hidden (navigating to other pages) to destroy it.
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].intersectionRatio == 0) {
                this.isVisible = false;

                // Only call the events if ready
                if (this.initialized) {
                    this.onHide();
                }
            } else {
                this.isVisible = true;

                // Only call the events if ready
                if (this.initialized) {
                    this.onShow();
                }
            }
        }, {
            root: this.$form,

            // Include a large margin to cater for when other elements might cover the field
            // as `IntersectionObserver` won't deem the field in view if under something else
            rootMargin: '50px',
        });

        // Watch for when the input is visible/hidden, in the context of the form. But wait a little to start watching
        // to prevent double binding when still loading the form, or hidden behind conditions.
        setTimeout(() => {
            observer.observe(this.$field);
        }, 500);
    }

    eventKey(eventName, namespace) {
        // Create event keys specific to this payment field, so multiple payment fields in conditions are unbound correctly
        return eventKey(eventName, `${namespace}-${this.$field.getAttribute('data-field-handle')}`);
    }

    removeSuccess() {
        removeClasses(this.$field, this.successClass);

        const $success = this.$field.querySelector('[data-payment-success]');

        if ($success) {
            $success.remove();
        }
    }

    addSuccess(message) {
        addClasses(this.$field, this.successClass);

        const $fieldContainer = this.$field.querySelector('[data-field-type] > div');

        if (!$fieldContainer) {
            return console.error('Unable to find `[data-field-type] > div` to add success message.');
        }

        const $success = document.createElement('div');
        $success.textContent = message;
        $success.setAttribute('data-payment-success', '');

        addClasses($success, this.successMessageClass);

        $fieldContainer.appendChild($success);
    }

    removeError() {
        removeClasses(this.$field, this.errorClass);

        const $error = this.$field.querySelector('[data-payment-error]');

        if ($error) {
            $error.remove();
        }
    }

    addError(message) {
        addClasses(this.$field, this.errorClass);

        const $fieldContainer = this.$field.querySelector('[data-field-type] > div');

        if (!$fieldContainer) {
            return console.error('Unable to find `[data-field-type] > div` to add error message.');
        }

        const $error = document.createElement('div');
        $error.textContent = message;
        $error.setAttribute('data-payment-error', '');

        addClasses($error, this.errorMessageClass);

        $fieldContainer.appendChild($error);

        if (this.submitHandler) {
            this.submitHandler.formSubmitError();
        }
    }

    updateInputs(name, value) {
        const $input = this.$field.querySelector(`[name*="${name}"]`);

        if ($input) {
            $input.value = value;
        }
    }

    getBillingData() {
        if (!this.billingDetails) {
            return {};
        }

        const billing = {};

        if (this.billingDetails.billingName) {
            const billingName = this.getFieldValue(this.billingDetails.billingName);

            if (billingName) {
                billing.name = billingName;
            }
        }

        if (this.billingDetails.billingEmail) {
            const billingEmail = this.getFieldValue(this.billingDetails.billingEmail);

            if (billingEmail) {
                billing.email = billingEmail;
            }
        }

        if (this.billingDetails.billingAddress) {
            billing.address = {};

            const address1 = this.getFieldValue(`${this.billingDetails.billingAddress}[address1]`);
            const address2 = this.getFieldValue(`${this.billingDetails.billingAddress}[address2]`);
            const address3 = this.getFieldValue(`${this.billingDetails.billingAddress}[address3]`);
            const city = this.getFieldValue(`${this.billingDetails.billingAddress}[city]`);
            const zip = this.getFieldValue(`${this.billingDetails.billingAddress}[zip]`);
            const state = this.getFieldValue(`${this.billingDetails.billingAddress}[state]`);
            const country = this.getFieldValue(`${this.billingDetails.billingAddress}[country]`);

            /* eslint-disable camelcase */
            if (address1) {
                billing.address.line1 = address1;
            }

            if (address2) {
                billing.address.line2 = address2;
            }

            if (address3) {
                billing.address.line3 = address3;
            }

            if (city) {
                billing.address.city = city;
            }

            if (zip) {
                billing.address.postal_code = zip;
            }

            if (state) {
                billing.address.state = state;
            }

            if (country) {
                billing.address.country = country;
            }
            /* eslint-enable camelcase */
        }

        // Emit an "modifyBillingDetails" event. This can directly modify the `billing` param
        const modifyBillingDetailsEvent = new CustomEvent('modifyBillingDetails', {
            bubbles: true,
            detail: {
                provider: this,
                billing,
            },
        });

        // eslint-disable-next-line camelcase
        return { billing_details: billing };
    }

    getFieldValue(handle, type = 'string') {
        const value = getFieldValue(this.$form, handle);

        // We should do some extra processing depending on the type
        if (type === 'float' || type === 'int' || type === 'number') {
            return currencyToFloat(value);
        }

        return value;
    }

    getFieldLabel(handle) {
        return getFieldLabel(this.$form, handle);
    }

    onShow() {

    }

    onHide() {

    }

    processResubmit() {
        // Refresh captcha/CSRF tokens, as we'll be submitting again.
        this.form.config.Formie.refreshFormTokens(this.form);

        // Resubmit the form, but skip payment handling, as we're done.
        // This ensures captchas and other validation runs again in case something changed.
        if (this.submitHandler) {
            this.submitHandler.processSubmit(['payment']);
        } else if (this.form) {
            // Just in case `submitHandler` is undefined here...
            this.form.processSubmit(['payment']);
        }
    }
}

window.FormiePaymentProvider = FormiePaymentProvider;
