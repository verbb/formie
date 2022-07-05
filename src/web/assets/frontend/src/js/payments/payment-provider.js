import { eventKey } from '../utils/utils';

export class FormiePaymentProvider {
    constructor(settings = {}) {
        this.successClass = 'fui-success';
        this.successMessageClass = 'fui-success-message';
        this.errorClass = 'fui-error';
        this.errorMessageClass = 'fui-error-message';
    }

    removeSuccess() {
        this.$field.classList.remove(this.successClass);

        var $success = this.$field.querySelector('.' + this.successMessageClass);

        if ($success) {
            $success.remove();
        }
    }

    addSuccess(message) {
        this.$field.classList.add(this.successClass);

        var $fieldContainer = this.$field.querySelector('[data-field-type] > div');

        if (!$fieldContainer) {
            return console.error('Unable to find `[data-field-type] > div` to add success message.');
        }

        var $success = document.createElement('div');
        $success.className = this.successMessageClass;
        $success.textContent = message;

        $fieldContainer.appendChild($success);
    }

    removeError() {
        this.$field.classList.remove(this.errorClass);

        var $error = this.$field.querySelector('.' + this.errorMessageClass);

        if ($error) {
            $error.remove();
        }
    }

    addError(message) {
        this.$field.classList.add(this.errorClass);

        var $fieldContainer = this.$field.querySelector('[data-field-type] > div');

        if (!$fieldContainer) {
            return console.error('Unable to find `[data-field-type] > div` to add error message.');
        }

        var $error = document.createElement('div');
        $error.className = this.errorMessageClass;
        $error.textContent = message;

        $fieldContainer.appendChild($error);

        if (this.submitHandler) {
            this.submitHandler.formSubmitError();
        }
    }

    updateInputs(name, value) {
        var $input = this.$field.querySelector('[name*="' + name + '"]');

        if ($input) {
            $input.value = value;
        }
    }

    getBillingData() {
        if (!this.billingDetails) {
            return {};
        }

        var billing = {};

        if (this.billingDetails.billingName) {
            var value = this.getFieldValue(this.billingDetails.billingName);

            if (value) {
                billing.name = value;
            }
        }

        if (this.billingDetails.billingEmail) {
            var value = this.getFieldValue(this.billingDetails.billingEmail);
            
            if (value) {
                billing.email = value;
            }
        }

        if (this.billingDetails.billingAddress) {
            billing.address = {};

            var address1 = this.getFieldValue(this.billingDetails.billingAddress + '[address1]');
            var address2 = this.getFieldValue(this.billingDetails.billingAddress + '[address2]');
            var address3 = this.getFieldValue(this.billingDetails.billingAddress + '[address3]');
            var city = this.getFieldValue(this.billingDetails.billingAddress + '[city]');
            var zip = this.getFieldValue(this.billingDetails.billingAddress + '[zip]');
            var state = this.getFieldValue(this.billingDetails.billingAddress + '[state]');
            var country = this.getFieldValue(this.billingDetails.billingAddress + '[country]');
            
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
        }

        // Emit an "modifyBillingDetails" event. This can directly modify the `billing` param
        const modifyBillingDetailsEvent = new CustomEvent('modifyBillingDetails', {
            bubbles: true,
            detail: {
                stripe: this,
                billing,
            },
        });

        return { billing_details: billing };
    }

    getFieldValue(handle) {
        var value = '';

        handle = this.getFieldName(handle);

        // We'll always get back multiple inputs to normalise checkbox/radios
        var $fields = this.getFormField(handle);

        if ($fields) {
            $fields.forEach($field => {
                if ($field.type === 'checkbox' || $field.type === 'radio') {
                    if ($field.checked) {
                        return value = $field.value;
                    }
                } else {
                    return value = $field.value;
                }
            });
        }

        return value;
    }

    getFormField(handle) {
        // Get the field(s) we're targeting to watch for changes. Note we need to handle multiple fields (checkboxes)
        let $fields = this.$form.querySelectorAll(`[name="${handle}"]`);

        // Check if we're dealing with multiple fields, like checkboxes. This overrides the above
        const $multiFields = this.$form.querySelectorAll(`[name="${handle}[]"]`);

        if ($multiFields.length) {
            $fields = $multiFields;
        }

        return $fields;
    }

    getFieldName(handle) {
        // Normalise the handle first
        handle = handle.replace('{', '').replace('}', '').replace(']', '').split('[').join('][');

        return 'fields[' + handle + ']';
    }
}

window.FormiePaymentProvider = FormiePaymentProvider;
