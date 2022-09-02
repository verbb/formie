import { eventKey } from '../utils/utils';
import { FormiePaymentProvider } from './payment-provider';

export class FormiePayPal extends FormiePaymentProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.$input = this.$field.querySelector('[data-fui-paypal-button]');

        if (!this.$input) {
            console.error('Unable to find PayPal placeholder for [data-fui-paypal-button]');

            return;
        }

        this.clientId = settings.clientId;
        this.useSandbox = settings.useSandbox;
        this.currency = settings.currency;
        this.amountType = settings.amountType;
        this.amountFixed = settings.amountFixed;
        this.amountVariable = settings.amountVariable;
        this.buttonLayout = settings.buttonLayout;
        this.buttonColor = settings.buttonColor;
        this.buttonShape = settings.buttonShape;
        this.buttonLabel = settings.buttonLabel;
        this.buttonTagline = settings.buttonTagline;
        this.buttonWidth = settings.buttonWidth;
        this.buttonHeight = settings.buttonHeight;

        this.paypalScriptId = 'FORMIE_PAYPAL_SCRIPT';

        if (!this.clientId) {
            console.error('Missing clientId for PayPal.');

            return;
        }

        this.initField();
    }

    getScriptUrl() {
        const url = 'https://www.paypal.com/sdk/js';
        const params = ['intent=authorize'];

        params.push(`currency=${this.currency}`);
        params.push(`client-id=${this.clientId}`);

        return `${url}?${params.join('&')}`;
    }

    initField() {
        // Fetch and attach the script only once - this is in case there are multiple forms on the page.
        // They all go to a single callback which resolves its loaded state
        if (!document.getElementById(this.paypalScriptId)) {
            const $script = document.createElement('script');
            $script.id = this.paypalScriptId;
            $script.src = this.getScriptUrl();

            $script.async = true;
            $script.defer = true;

            // Wait until PayPal.js has loaded, then initialize
            $script.onload = () => {
                this.renderButton();
            };

            document.body.appendChild($script);
        } else {
            this.renderButton();
        }

        // Attach custom event listeners on the form
        this.form.addEventListener(this.$form, eventKey('onAfterFormieSubmit', 'paypal'), this.onAfterSubmit.bind(this));
    }

    getStyleSettings() {
        const settings = {
            layout: this.buttonLayout,
            color: this.buttonColor,
            shape: this.buttonShape,
            label: this.buttonLabel,
            width: this.buttonWidth,
            height: this.buttonHeight,
        };

        if (this.buttonLayout === 'horizontal') {
            settings.tagline = this.buttonTagline;
        }

        return settings;
    }

    renderButton() {
        paypal.Buttons({
            env: this.useSandbox ? 'sandbox' : 'production',
            style: this.getStyleSettings(),
            createOrder: (data, actions) => {
                this.removeError();

                let amount = 0;

                if (this.amountType === 'fixed') {
                    amount = this.amountFixed;
                } else if (this.amountType === 'dynamic') {
                    amount = this.getFieldValue(this.amountVariable);
                }

                /* eslint-disable camelcase */
                return actions.order.create({
                    intent: 'AUTHORIZE',
                    application_context: {
                        user_action: 'CONTINUE',
                    },
                    purchase_units: [{
                        amount: {
                            currency_code: this.currency,
                            value: amount,
                        },
                    }],
                });
                /* eslint-enable camelcase */
            },

            onCancel: (data, actions) => {

            },

            onError: (err) => {
                this.addError(err);
            },

            onApprove: (data, actions) => {
                // Authorize the transaction, instead of capturing. This will be done after form submit
                actions.order.authorize().then((authorization) => {
                    const authorizationID = authorization.purchase_units[0].payments.authorizations[0].id;

                    this.updateInputs('paypalOrderId', data.orderID);
                    this.updateInputs('paypalAuthId', authorizationID);

                    this.addSuccess(t('Payment authorized. Finalise the form to complete payment.'));
                });
            },
        }).render(this.$input);
    }

    onAfterSubmit(e) {
        // Reset all hidden inputs
        this.updateInputs('paypalOrderId', '');
        this.updateInputs('paypalAuthId', '');

        this.removeSuccess();
        this.removeError();
    }
}

window.FormiePayPal = FormiePayPal;
