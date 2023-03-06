import { eventKey } from '../utils/utils';
import { FormiePaymentProvider } from './payment-provider';
import MicroModal from 'micromodal';

export class FormieOpayo extends FormiePaymentProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.$input = this.$field.querySelector('[data-fui-opayo-button]');

        if (!this.$input) {
            console.error('Unable to find Opayo placeholder for [data-fui-opayo-button]');

            return;
        }

        this.merchantSessionKey = settings.merchantSessionKey;
        this.useSandbox = settings.useSandbox;
        this.currency = settings.currency;
        this.amountType = settings.amountType;
        this.amountFixed = settings.amountFixed;
        this.amountVariable = settings.amountVariable;
        this.opayoScriptId = 'FORMIE_OPAYO_SCRIPT';

        if (!this.merchantSessionKey) {
            console.error('Missing merchantSessionKey for Opayo.');

            return;
        }

        this.initField();
    }

    initField() {
        // Fetch and attach the script only once - this is in case there are multiple forms on the page.
        // They all go to a single callback which resolves its loaded state
        if (!document.getElementById(this.opayoScriptId)) {
            const $script = document.createElement('script');
            $script.id = this.opayoScriptId;

            if (this.useSandbox) {
                $script.src = 'https://pi-test.sagepay.com/api/v1/js/sagepay.js';
            } else {
                $script.src = 'https://pi.sagepay.com/api/v1/js/sagepay.js';
            }

            $script.async = true;
            $script.defer = true;

            // Wait until Opayo.js has loaded, then initialize
            $script.onload = () => {
                this.mountCard();
            };

            document.body.appendChild($script);
        } else {
            this.mountCard();
        }

        // Attach custom event listeners on the form
        this.form.addEventListener(this.$form, eventKey('onFormiePaymentValidate', 'opayo'), this.onValidate.bind(this));
        this.form.addEventListener(this.$form, eventKey('onAfterFormieSubmit', 'opayo'), this.onAfterSubmit.bind(this));
        this.form.addEventListener(this.$form, eventKey('FormiePaymentOpayo3DS', 'opayo'), this.onValidate3DS.bind(this));

        // Listen to events sent from the iframe to complete 3DS challenge
        window.addEventListener('message', this.onMessage.bind(this), false);
    }

    mountCard() {
        try {
            this.provider = sagepayOwnForm({
                merchantSessionKey: this.merchantSessionKey,
            });
        } catch (ex) {
            console.error(ex);

            this.addError(ex);
        }
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

        const self = this;

        try {
            this.provider.tokeniseCardDetails({
                cardDetails: {
                    cardholderName: 'CHALLENGE',
                    cardNumber: '4929000000006',
                    expiryDate: '0126',
                    securityCode: '123',
                },

                onTokenised(result) {
                    if (result.success) {
                        // Append an input so it's not namespaced with Twig
                        self.updateInputs('opayoTokenId', result.cardIdentifier);
                        self.updateInputs('opayoSessionKey', self.merchantSessionKey);

                        self.submitHandler.submitForm();
                    } else {
                        console.error(result);

                        self.addError(result.errors[0].message);
                    }
                },
            });
        } catch (ex) {
            console.error(ex);

            self.addError(ex);
        }
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

    onMessage(e) {
        console.log(e);

        // Check this is the correct message
        if (e.data.message !== 'FormiePaymentOpayo3DSResponse') {
            return;
        }

        this.removeError();

        if (e.data.value.error) {
            this.removeLoading();

            return this.addError(e.data.value.error.message);
        }

        // Add a flag for server-side to check and finalise
        this.updateInputs('opayo3DSComplete', e.data.value.transactionId);

        this.submitHandler.submitForm();
    }

    onValidate3DS(e) {
        const { data } = e.detail;

        // Keep the spinner going for 3DS
        this.addLoading();

        // MicroModal.init();


        console.log(data);

        const inputs = `<input type="hidden" name="creq" value="${data.creq}" />
             <input type="hidden" name="threeDSSessionData" value="${data.threeDSSessionData}" />
             <input type="hidden" name="MD" value="${this.merchantSessionKey}" />
             <input type="hidden" name="TermUrl" value="${data.redirectUrl}" />
             <input type="hidden" name="ThreeDSNotificationURL" value="${data.redirectUrl}" />`;

        const iframe = document.createElement('iframe');
        const html = `<form action="${data.acsUrl}" method="post">${inputs}</form><script>document.forms[0].submit();</script>`;

        document.querySelector('.iframe-placeholder').appendChild(iframe);

        iframe.contentWindow.document.open();
        iframe.contentWindow.document.write(html);
        iframe.contentWindow.document.close();


        // const form = document.createElement('form');
        // form.action = data.acsUrl;
        // form.method = 'post';
        // form.innerHTML = `${data.inputs}<p>Please click button below to proceed to 3D secure.</p> <input type="submit" value="Go"/>`;

        // document.body.appendChild(form);

        // if (this.form.formTheme) {
        //     this.form.formTheme.updateFormHash();
        // }

        // form.submit();


        // if (data.subscription_id) {
        //     this.stripe.handleCardPayment(data.client_secret).then((result) => {
        //         this.removeError();

        //         if (result.error) {
        //             this.removeLoading();

        //             return this.addError(result.error.message);
        //         }

        //         // Append an input so it's not namespaced with Twig
        //         this.updateInputs('stripeSubscriptionId', data.subscription_id);

        //         this.submitHandler.submitForm();
        //     });
        // } else {
        //     this.stripe.handleCardAction(data.client_secret).then((result) => {
        //         this.removeError();

        //         if (result.error) {
        //             this.removeLoading();

        //             return this.addError(result.error.message);
        //         }

        //         // Append an input so it's not namespaced with Twig
        //         this.updateInputs('stripePaymentIntentId', result.paymentIntent.id);

        //         this.submitHandler.submitForm();
        //     });
        // }
    }

    onAfterSubmit(e) {
        // Reset all hidden inputs
        this.updateInputs('opayoTokenId', '');
        this.updateInputs('opayoSessionKey', '');
        this.updateInputs('opayo3DSComplete', '');
    }
}

window.FormieOpayo = FormieOpayo;
