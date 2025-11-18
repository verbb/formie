// eslint-disable-next-line
import { t, clone, ensureVariable, debounce } from '../utils/utils';
import { getFieldName } from '../utils/fields';
import { FormiePaymentProvider } from './payment-provider';

export class FormieStripe extends FormiePaymentProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.$input = this.$field.querySelector('[data-fui-stripe-elements]');
        this.$placeholder = this.$field.querySelector('[data-fui-stripe-elements-placeholder]');

        if (!this.$input) {
            console.error('Unable to find Stripe Elements placeholder for [data-fui-stripe-elements]');

            return;
        }

        this.boundEvents = false;
        this.publishableKey = settings.publishableKey;
        this.paymentType = settings.paymentType;
        this.billingDetails = settings.billingDetails || {};
        this.hidePostalCode = settings.hidePostalCode || false;
        this.hideIcon = settings.hideIcon || false;
        this.initialPaymentInformation = settings.initialPaymentInformation || {};
        this.paymentInformation = clone(this.initialPaymentInformation);
        this.stripeScriptId = 'FORMIE_STRIPE_SCRIPT';

        if (!this.publishableKey) {
            console.error('Missing publishable key for Stripe.');

            return;
        }

        // We can start listening for the field to become visible to initialize it
        this.initialized = true;
    }

    onShow() {
        // Initialize the field only when it's visible
        this.loadStripe();
    }

    onHide() {
        // Field is hidden, so reset everything
        if (this.paymentElement) {
            // Kill off Stripe items
            this.paymentElement.destroy();
            this.paymentElement = null;
            this.stripe = null;
            this.boundEvents = false;

            // Remove unique event listeners
            this.form.removeEventListener(this.eventKey('onFormiePaymentValidate', 'stripe'));
            this.form.removeEventListener(this.eventKey('onAfterFormieSubmit', 'stripe'));
            this.form.removeEventListener(this.eventKey('FormiePaymentStripeConfirm', 'stripe'));
        }
    }

    loadStripe() {
        try {
            // Fetch and attach the script only once - this is in case there are multiple forms on the page.
            // They all go to a single callback which resolves its loaded state
            if (!document.getElementById(this.stripeScriptId)) {
                const $script = document.createElement('script');
                $script.id = this.stripeScriptId;
                $script.src = 'https://js.stripe.com/v3';
                $script.async = true;
                $script.defer = true;

                // Wait until Stripe.js has loaded, then initialize
                $script.onload = () => {
                    this.initStripe();
                };

                document.body.appendChild($script);
            } else {
                // Ensure that Stripe has been loaded and ready to use
                ensureVariable('Stripe').then(() => {
                    this.initStripe();
                });
            }

            // Attach custom event listeners on the form
            // Prevent binding multiple times. This can cause multiple payments!
            if (!this.boundEvents) {
                this.form.addEventListener(this.$form, this.eventKey('onFormiePaymentValidate', 'stripe'), this.onValidate.bind(this));
                this.form.addEventListener(this.$form, this.eventKey('onAfterFormieSubmit', 'stripe'), this.onAfterSubmit.bind(this));
                this.form.addEventListener(this.$form, this.eventKey('FormiePaymentStripeConfirm', 'stripe'), this.onValidateConfirm.bind(this));

                this.boundEvents = true;
            }
        } catch (error) {
            console.error(error);

            this.showPlaceholder(error, 'error');
        }
    }

    initStripe() {
        // Prevent against mounting the card on a destroyed form (race condition with conditions and multi-init)
        // Probably should refactor this to handle registering observers (IntersectionObserver)
        if (this.form.destroyed) {
            return;
        }

        try {
            this.stripe = Stripe(this.publishableKey);

            // Don't proceed to render if there are dynamic values
            if (this.handleDynamicValues()) {
                this.renderStripe();
            }
        } catch (error) {
            console.error(error);

            this.showPlaceholder(error, 'error');
        }
    }

    renderStripe() {
        try {
            const mode = this.isSubscription() ? 'subscription' : 'payment';

            const paymentOptions = {};

            const elementOptions = {
                ...this.paymentInformation,

                // eslint-disable-next-line
                capture_method: 'automatic',
                mode,
                appearance: {},
            };

            // Emit an "beforeInit" event. This can directly modify the `options` param
            const beforeInitEvent = new CustomEvent('beforeInit', {
                bubbles: true,
                detail: {
                    stripe: this,
                    elementOptions,
                    paymentOptions,
                },
            });

            this.$field.dispatchEvent(beforeInitEvent);

            if (!elementOptions.amount) {
                this.showPlaceholder(t('Invalid amount.'), 'error');

                return;
            }

            if (!elementOptions.currency) {
                this.showPlaceholder(t('Invalid currency.'), 'error');

                return;
            }

            this.elements = this.stripe.elements(elementOptions);
            this.paymentElement = this.elements.create('payment', paymentOptions);
            this.paymentElement.mount(this.$input);

            this.hidePlaceholder();
        } catch (error) {
            console.error(error);

            this.showPlaceholder(error, 'error');
        }
    }

    onValidate(e) {
        // Don't validate if we're not submitting (going back, saving)
        // Check if the form has an invalid flag set, don't bother going further
        if (this.form.submitAction !== 'submit' || e.detail.invalid) {
            return;
        }

        // Handle when trying to submit the form without Stripe.js being ready
        if (!this.elements) {
            return;
        }

        e.preventDefault();

        // Save for later to trigger real submit
        this.submitHandler = e.detail.submitHandler;

        this.removeError();

        this.elements.submit().then((result) => {
            if (result.error) {
                return this.addError(result.error.message);
            }

            this.submitHandler.submitForm();
        });
    }

    showPlaceholder(message, type) {
        this.$placeholder.innerHTML = message;
        this.$placeholder.classList.remove('fui-hidden');

        if (type === 'error') {
            this.$placeholder.classList.add('fui-error-message');
        }
    }

    hidePlaceholder() {
        this.$placeholder.classList.add('fui-hidden');
    }

    onValidateConfirm(e) {
        const { data } = e.detail;

        // Keep the spinner going for 3DS
        this.addLoading();

        // Set the origin to redirect to (after the callback) client-side to have it resolve properly for headless
        const returnUrl = new URL(data.returnUrl);
        returnUrl.searchParams.append('origin', window.location.href);

        // Update the form hash, so we don't get change warnings
        if (this.form.formTheme) {
            this.form.formTheme.updateFormHash();
        }

        const confirmIntent = data.type === 'setup' ? this.stripe.confirmSetup : this.stripe.confirmPayment;

        confirmIntent({
            elements: this.elements,
            clientSecret: data.clientSecret,
            redirect: 'if_required',
            confirmParams: {
                // eslint-disable-next-line
                return_url: returnUrl.toString(),
            },
        }).then((result) => {
            this.removeError();

            if (result.error) {
                this.removeLoading();

                return this.addError(result.error.message);
            }

            // Append an input so it's not namespaced with Twig
            this.updateInputs('stripeSubscriptionId', data.subscriptionId);
            this.updateInputs('stripePaymentIntentId', result.paymentIntent.id);

            // Handle resubmitting the form properly
            this.processResubmit();
        });
    }

    hasDynamicValue(key) {
        const value = this.initialPaymentInformation[key] ?? null;

        return value.toString().includes('{field:') ?? false;
    }

    hasDynamicValues() {
        return this.hasDynamicValue('amount') || this.hasDynamicValue('currency');
    }

    checkDynamicValues() {
        const amount = this.initialPaymentInformation?.amount ?? null;
        const currency = this.initialPaymentInformation?.currency ?? null;

        const amountValue = this.getFieldValue(amount, 'number');
        const currencyValue = this.getFieldValue(currency);

        const amountLabel = this.getFieldLabel(amount);
        const currencyLabel = this.getFieldLabel(currency);

        if (this.hasDynamicValue('amount')) {
            if (!amountValue) {
                this.showPlaceholder(t('Provide a value for “{label}” to proceed.', { label: amountLabel }), 'error');

                return false;
            }

            this.paymentInformation.amount = amountValue * 100;
        }

        if (this.hasDynamicValue('currency')) {
            if (!currencyValue) {
                this.showPlaceholder(t('Provide a value for “{label}” to proceed.', { label: currencyLabel }), 'error');

                return false;
            }

            this.paymentInformation.currency = currencyValue.toLowerCase();
        }

        // We're okay to render the Stripe form
        return true;
    }

    handleDynamicValues() {
        if (!this.hasDynamicValues()) {
            return true;
        }

        // Listen to (debounced) whenever something in the form changes and matches our mapped-to fields
        this.form.addEventListener(this.$form, this.eventKey('input', 'stripe'), debounce((event) => {
            const fieldName = event.target.name;

            const amount = this.initialPaymentInformation?.amount ?? null;
            const currency = this.initialPaymentInformation?.currency ?? null;

            // Only check updates on the field we care about
            if (fieldName === getFieldName(amount) || fieldName === getFieldName(currency)) {
                // Do we have all the data required to render Stripe?
                if (this.checkDynamicValues()) {
                    this.hidePlaceholder();

                    // Update the elements form if it exists already, or render Stripe
                    if (this.elements) {
                        this.elements.update(this.paymentInformation);
                    } else {
                        this.renderStripe();
                    }
                }
            }
        }, 600));

        // Check for dynamic values immediately
        return this.checkDynamicValues();
    }

    isSubscription() {
        return this.paymentType === 'subscription';
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

    onAfterSubmit(e) {
        // Clear the Stripe form
        if (this.paymentElement) {
            this.paymentElement.clear();
        }

        // Reset all hidden inputs
        this.updateInputs('stripePaymentIntentId', '');
        this.updateInputs('stripeSubscriptionId', '');
    }
}

window.FormieStripe = FormieStripe;
