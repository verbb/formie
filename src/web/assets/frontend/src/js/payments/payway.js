import { eventKey } from '../utils/utils';
import { FormiePaymentProvider } from './payment-provider';

export class FormiePayWay extends FormiePaymentProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.$input = this.$field.querySelector('[data-fui-payway-button]');

        if (!this.$input) {
            console.error('Unable to find PayPWay placeholder for [data-fui-payway-button]');

            return;
        }

        this.publishableKey = settings.publishableKey;
        this.currency = settings.currency;
        this.amountType = settings.amountType;
        this.amountFixed = settings.amountFixed;
        this.amountVariable = settings.amountVariable;
        this.paywayScriptId = 'FORMIE_PAYWAY_SCRIPT';

        if (!this.publishableKey) {
            console.error('Missing publishableKey for PayWay.');

            return;
        }

        this.initField();
    }

    initField() {
        // Fetch and attach the script only once - this is in case there are multiple forms on the page.
        // They all go to a single callback which resolves its loaded state
        if (!document.getElementById(this.paypalScriptId)) {
            const $script = document.createElement('script');
            $script.id = this.paypalScriptId;
            $script.src = 'https://api.payway.com.au/rest/v1/payway.js';

            $script.async = true;
            $script.defer = true;

            // Wait until PayWay.js has loaded, then initialize
            $script.onload = () => {
                this.mountCard();
            };

            document.body.appendChild($script);
        } else {
            this.mountCard();
        }

        // Attach custom event listeners on the form
        this.form.addEventListener(this.$form, eventKey('onFormiePaymentValidate', 'payway'), this.onValidate.bind(this));
        this.form.addEventListener(this.$form, eventKey('onAfterFormieSubmit', 'payway'), this.onAfterSubmit.bind(this));
    }

    mountCard() {
        payway.createCreditCardFrame({
            layout: 'wide',
            publishableApiKey: this.publishableKey,
            tokenMode: 'callback',
        }, (err, frame) => {
            if (err) {
                console.error(`Error creating frame: ${err.message}`);
            } else {
                // Save the created frame for when we get the token
                this.creditCardFrame = frame;
            }
        });
    }

    onValidate(e) {
        // Don't validate if we're not submitting (going back, saving)
        // Check if the form has an invalid flag set, don't bother going further
        if (this.form.submitAction !== 'submit' || e.detail.invalid) {
            return;
        }

        e.preventDefault();

        // Save for later to trigger real submit
        this.submitHandler = e.detail.submitHandler;

        this.removeError();

        if (this.creditCardFrame) {
            this.creditCardFrame.getToken((err, data) => {
                if (err) {
                    console.error(`Error getting token: ${err.message}`);

                    this.addError(err.message);
                } else {
                    // Append an input so it's not namespaced with Twig
                    this.updateInputs('paywayTokenId', data.singleUseTokenId);

                    this.submitHandler.submitForm();
                }
            });
        } else {
            console.error('Credit Card Frame is invalid.');
        }
    }

    onAfterSubmit(e) {
        // Clear the form
        if (this.creditCardFrame) {
            this.creditCardFrame.destroy();
            this.creditCardFrame = null;
        }

        // Reset all hidden inputs
        this.updateInputs('paywayTokenId', '');
    }
}

window.FormiePayWay = FormiePayWay;
