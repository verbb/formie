import { t, eventKey, getAjaxClient } from '../utils/utils';
import { FormiePaymentProvider } from './payment-provider';
import { dialog } from '@rynpsc/dialog';
import Payment from 'payment';

export class FormieOpayo extends FormiePaymentProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;

        this.boundEvents = false;
        this.useSandbox = settings.useSandbox;
        this.integrationHandle = settings.handle;
        this.currency = settings.currency;
        this.amountType = settings.amountType;
        this.amountFixed = settings.amountFixed;
        this.amountVariable = settings.amountVariable;
        this.opayoScriptId = 'FORMIE_OPAYO_SCRIPT';

        // We can start listening for the field to become visible to initialize it
        this.initialized = true;
    }

    onShow() {
        // Initialize the field only when it's visible
        this.initField();
    }

    onHide() {
        this.boundEvents = false;

        // Field is hidden, so reset everything
        this.onAfterSubmit();

        // Remove unique event listeners
        this.form.removeEventListener(this.eventKey('onFormiePaymentValidate', 'opayo'));
        this.form.removeEventListener(this.eventKey('onAfterFormieSubmit', 'opayo'));
        this.form.removeEventListener(this.eventKey('FormiePaymentOpayo3DS', 'opayo'));
        this.form.removeEventListener(this.eventKey('message', 'opayo'));
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
                $script.src = 'https://pi-live.sagepay.com/api/v1/js/sagepay.js';
            }

            $script.async = true;
            $script.defer = true;

            document.body.appendChild($script);
        }

        // Attach custom event listeners on the form
        // Prevent binding multiple times. This can cause multiple payments!
        if (!this.boundEvents) {
            this.form.addEventListener(this.$form, this.eventKey('onFormiePaymentValidate', 'opayo'), this.onValidate.bind(this));
            this.form.addEventListener(this.$form, this.eventKey('onAfterFormieSubmit', 'opayo'), this.onAfterSubmit.bind(this));
            this.form.addEventListener(this.$form, this.eventKey('FormiePaymentOpayo3DS', 'opayo'), this.onValidate3DS.bind(this));
            this.form.addEventListener(window, this.eventKey('message', 'opayo'), this.onMessage.bind(this));

            this.boundEvents = true;
        }

        // Add input masking and validation for some fields
        Payment.formatCardNumber(this.$field.querySelector('[data-opayo-card="card-number"]'));
        Payment.formatCardExpiry(this.$field.querySelector('[data-opayo-card="expiry-date"]'));
        Payment.formatCardCVC(this.$field.querySelector('[data-opayo-card="security-code"]'));
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

        try {
            // Fetch/generate the merchant ID first via an Ajax request
            const action = this.$form.getAttribute('action');
            const xhr = getAjaxClient(this.$form, 'POST', action ? action : window.location.href, true);

            xhr.ontimeout = () => {
                self.addError(t('The request timed out.'));
            };

            xhr.onerror = (e) => {
                self.addError(t('The request encountered a network error. Please try again.'));
            };

            xhr.onload = () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const response = JSON.parse(xhr.responseText);

                        // Get the data from the client
                        const cardDetails = {
                            cardholderName: this.$field.querySelector('[data-opayo-card="cardholder-name"]').value,
                            cardNumber: this.$field.querySelector('[data-opayo-card="card-number"]').value,
                            expiryDate: this.$field.querySelector('[data-opayo-card="expiry-date"]').value,
                            securityCode: this.$field.querySelector('[data-opayo-card="security-code"]').value,
                        };

                        // Remove formatting
                        cardDetails.cardNumber = cardDetails.cardNumber.replace(/[\s/]/g, '');
                        cardDetails.expiryDate = cardDetails.expiryDate.replace(/[\s/]/g, '');

                        // With the `merchantSessionKey`, now tokenize the credit card form and trigger submit
                        sagepayOwnForm({
                            merchantSessionKey: response.merchantSessionKey,
                        }).tokeniseCardDetails({
                            cardDetails,

                            onTokenised: (result) => {
                                if (result.success) {
                                    // Append an input so it's not namespaced with Twig
                                    this.updateInputs('opayoTokenId', result.cardIdentifier);
                                    this.updateInputs('opayoSessionKey', response.merchantSessionKey);

                                    this.submitHandler.submitForm();
                                } else {
                                    console.error(result);

                                    this.addError(result.errors[0].message);
                                }
                            },
                        });
                    } catch (e) {
                        this.addError(t('Unable to parse response `{e}`.', { e }));
                    }
                } else {
                    this.addError(`${xhr.status}: ${xhr.statusText}`);
                }
            };

            const data = new FormData();
            data.append('action', 'formie/payment-webhooks/process-callback');
            data.append('merchantSessionKey', true);
            data.append('handle', this.integrationHandle);

            xhr.send(data);
        } catch (ex) {
            console.error(ex);

            this.addError(ex);
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
        // Check this is the correct message
        if (e.data.message !== 'FormiePaymentOpayo3DSResponse') {
            return;
        }

        if (this.dialog) {
            this.dialog.close();
        }

        this.removeError();

        if (e.data.value.error) {
            this.removeLoading();

            return this.addError(e.data.value.error.message);
        }

        // Add a flag for server-side to check and finalise
        this.updateInputs('opayo3DSComplete', e.data.value.transactionId);

        // Handle resubmitting the form properly
        this.processResubmit();
    }

    onValidate3DS(e) {
        try {
            const { data } = e.detail;

            // Keep the spinner going for 3DS
            this.addLoading();

            const dialogId = `fui-opayo-dialog-${(Math.random() + 1).toString(36).substring(7)}`;

            const $dialog = document.createElement('div');
            $dialog.setAttribute('class', 'fui-modal');
            $dialog.setAttribute('id', dialogId);

            const $dialogBackdrop = document.createElement('div');
            $dialogBackdrop.setAttribute('class', 'fui-modal-backdrop');
            $dialogBackdrop.setAttribute('data-dialog-close', 'dialog');
            $dialog.appendChild($dialogBackdrop);

            const $dialogContent = document.createElement('div');
            $dialogContent.setAttribute('class', 'fui-modal-content');
            $dialog.appendChild($dialogContent);

            const $dialogLoading = document.createElement('div');
            $dialogLoading.setAttribute('class', 'fui-loading fui-loading-large');
            $dialogLoading.setAttribute('style', '--fui-loading-width: 3rem; --fui-loading-height: 3rem; --fui-loading-border-width: 4px; top: 50%; margin-top: -1.5rem;');
            $dialogContent.appendChild($dialogLoading);

            const $iframe = document.createElement('iframe');
            $iframe.setAttribute('width', '100%');
            $iframe.setAttribute('height', '100%');
            $iframe.setAttribute('style', 'width: 100%; height: 100%; position: relative; z-index: 1;');

            const html = `<form action="${data.acsUrl}" method="post">
                <input type="hidden" name="creq" value="${data.creq}" />
                <input type="hidden" name="threeDSSessionData" value="${data.threeDSSessionData}" />
                <input type="hidden" name="MD" value="${this.merchantSessionKey}" />
                <input type="hidden" name="TermUrl" value="${data.redirectUrl}" />
                <input type="hidden" name="ThreeDSNotificationURL" value="${data.redirectUrl}" />
            </form>
            <script>document.forms[0].submit();</script>`;

            $dialogContent.appendChild($iframe);
            document.body.appendChild($dialog);

            $iframe.contentWindow.document.open();
            $iframe.contentWindow.document.write(html);
            $iframe.contentWindow.document.close();

            this.dialog = dialog(dialogId);

            if (this.dialog) {
                this.dialog.create();

                this.dialog.open();
            }
        } catch (ex) {
            console.error(ex);

            self.addError(ex);
        }
    }

    onAfterSubmit(e) {
        // Reset all hidden inputs
        this.updateInputs('opayoTokenId', '');
        this.updateInputs('opayoSessionKey', '');
        this.updateInputs('opayo3DSComplete', '');
    }
}

window.FormieOpayo = FormieOpayo;
