import { eventKey } from '../utils/utils';

export class FormieStripe {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.$input = this.$field.querySelector('[data-fui-stripe-elements]');

        if (!this.$input) {
            console.error('Unable to find Stripe Elements placeholder for #' + this.formId + ' [data-fui-stripe-elements]');

            return;
        }

        this.publishableKey = settings.publishableKey;
        this.billingDetails = settings.billingDetails || {};
        this.hidePostalCode = settings.hidePostalCode || false;
        this.hideIcon = settings.hideIcon || false;
        this.stripeScriptId = 'FORMIE_STRIPE_SCRIPT';

        if (!this.publishableKey) {
            console.error('Missing publishable key for Stripe.');

            return;
        }

        // Only initialize the field if it's visible. Use `IntersectionObserver` to check when visible
        // and also when hidden (navigating to other pages) to destroy it. Otherwise, Stripe elements
        // will listen to page submit events and validate, preventing from going back a page.
        var observer = new IntersectionObserver((entries) => {
            if (entries[0]['intersectionRatio'] == 0) {
                // Field is hidden, do reset everything
                if (this.cardElement) {
                    // Kill off Stripe items
                    this.cardElement.destroy();
                    this.cardElement = null;
                    this.stripe = null;

                    // Remove unique event listeners
                    this.form.removeEventListener(eventKey('onFormiePaymentValidate', 'stripe'));
                    this.form.removeEventListener(eventKey('onAfterFormieSubmit', 'stripe'));
                    this.form.removeEventListener(eventKey('FormiePaymentStripe3DS', 'stripe'));
                }
            } else {
                this.initCardField();
            }
        }, { root: this.$form });

        // Watch for when the input is visible/hidden, in the context of the form
        observer.observe(this.$input);
    }

    initCardField() {
        // Fetch and attach the script only once - this is in case there are multiple forms on the page.
        // They all go to a single callback which resolves its loaded state
        if (!document.getElementById(this.stripeScriptId)) {
            var $script = document.createElement('script');
            $script.id = this.stripeScriptId;
            $script.src = 'https://js.stripe.com/v3';
            $script.async = true;
            $script.defer = true;

            // Wait until Stripe.js has loaded, then initialize
            $script.onload = () => {
                this.mountCard()
            };

            document.body.appendChild($script);
        } else {
            this.mountCard()
        }

        // Attach custom event listeners on the form
        this.form.addEventListener(this.$form, eventKey('onFormiePaymentValidate', 'stripe'), this.onValidate.bind(this));
        this.form.addEventListener(this.$form, eventKey('onAfterFormieSubmit', 'stripe'), this.onAfterSubmit.bind(this));
        this.form.addEventListener(this.$form, eventKey('FormiePaymentStripe3DS', 'stripe'), this.onValidate3DS.bind(this));
    }

    mountCard() {
        this.stripe = Stripe(this.publishableKey);

        var elements = this.stripe.elements();

        var options = {
            classes: {
                focus: 'StripeElement--focus fui-focus',
                invalid: 'StripeElement--invalid fui-error',
            },
            hidePostalCode: this.hidePostalCode,
            iconStyle: 'default',
            hideIcon: this.hideIcon,
        };

        // Emit an "beforeInit" event. This can directly modify the `options` param
        const beforeInitEvent = new CustomEvent('beforeInit', {
            bubbles: true,
            detail: {
                stripe: this,
                options,
            },
        });

        this.$field.dispatchEvent(beforeInitEvent);

        this.cardElement = elements.create('card', options);

        this.cardElement.mount(this.$input);
    }

    onValidate(e) {
        // Don't validate if we're going back in the form
        // Check if the form has an invalid flag set, don't bother going further
        if (this.$form.goBack || e.detail.invalid) {
            return;
        }
        
        e.preventDefault();

        // Save for later to trigger real submit
        this.submitHandler = e.detail.submitHandler;

        this.removeError();

        this.stripe.createPaymentMethod('card', this.cardElement, this.getBillingData()).then((result) => {
            if (result.error) {
                return this.addError(result.error.message);
            }

            // Append an input so it's not namespaced with Twig
            this.updateInputs('stripePaymentId', result.paymentMethod.id);

            this.submitHandler.submitForm();
        });
    }

    addLoading() {
        if (this.form.formTheme) {
            this.form.formTheme.addLoading();
        }
    }

    removeLoading() {
        if (this.form.formTheme) {
            this.form.formTheme.removeLoading();
        }
    }

    onValidate3DS(e) {
        const data = e.detail.data;

        // Keep the spinner going for 3DS
        this.addLoading();

        if (data.subscription_id) {
            this.stripe.handleCardPayment(data.client_secret).then((result) => {
                this.removeError();

                if (result.error) {
                    this.removeLoading();

                    return this.addError(result.error.message);
                }

                // Append an input so it's not namespaced with Twig
                this.updateInputs('stripeSubscriptionId', data.subscription_id);

                this.submitHandler.submitForm();
            });
        } else {
            this.stripe.handleCardAction(data.client_secret).then((result) => {
                this.removeError();
                
                if (result.error) {
                    this.removeLoading();
                    
                    return this.addError(result.error.message);
                }

                // Append an input so it's not namespaced with Twig
                this.updateInputs('stripePaymentIntentId', result.paymentIntent.id);

                this.submitHandler.submitForm();
            });
        }
    }

    onAfterSubmit(e) {
        // Clear the Stripe form
        this.cardElement.clear();

        // Reset all hidden inputs
        this.updateInputs('stripePaymentId', '');
        this.updateInputs('stripePaymentIntentId', '');
        this.updateInputs('stripeSubscriptionId', '');
    }

    removeError() {
        this.$field.classList.remove('fui-error');

        var $error = this.$field.querySelector('.fui-error-message');

        if ($error) {
            $error.remove();
        }
    }

    addError(message) {
        this.$field.classList.add('fui-error');

        var $fieldContainer = this.$field.querySelector('.fui-field-container');

        if (!$fieldContainer) {
            return console.error('Unable to find `.fui-field-container` to add error message.');
        }

        var $error = document.createElement('div');
        $error.className = 'fui-error-message';
        $error.textContent = message;

        $fieldContainer.appendChild($error);

        this.submitHandler.formSubmitError();
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

window.FormieStripe = FormieStripe;
