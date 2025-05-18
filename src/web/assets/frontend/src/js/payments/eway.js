import { t, eventKey } from '../utils/utils';
import { FormiePaymentProvider } from './payment-provider';

export class FormieEway extends FormiePaymentProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;

        this.cseKey = settings.cseKey;
        this.ewayScriptId = 'FORMIE_EWAY_SCRIPT';

        if (!this.cseKey) {
            console.error('Missing cseKey for Eway.');

            return;
        }

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
        this.form.removeEventListener(eventKey('onFormiePaymentValidate', 'eway'));
        this.form.removeEventListener(eventKey('onAfterFormieSubmit', 'eway'));
    }

    initField() {
        // Fetch and attach the script only once - this is in case there are multiple forms on the page.
        // They all go to a single callback which resolves its loaded state
        if (!document.getElementById(this.ewayScriptId)) {
            const $script = document.createElement('script');
            $script.id = this.ewayScriptId;
            $script.src = 'https://secure.ewaypayments.com/scripts/eCrypt.min.js';

            $script.async = true;
            $script.defer = true;

            document.body.appendChild($script);
        }

        // Attach custom event listeners on the form
        // Prevent binding multiple times. This can cause multiple payments!
        if (!this.boundEvents) {
            this.form.addEventListener(this.$form, eventKey('onFormiePaymentValidate', 'eway'), this.onValidate.bind(this));
            this.form.addEventListener(this.$form, eventKey('onAfterFormieSubmit', 'eway'), this.onAfterSubmit.bind(this));

            this.boundEvents = true;
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

        // Get the data from the client
        const cardDetails = {
            cardholderName: this.$field.querySelector('[data-eway-card="cardholder-name"]').value,
            cardNumber: eCrypt.encryptValue(this.$field.querySelector('[data-eway-card="card-number"]').value, this.cseKey),
            expiryDate: this.$field.querySelector('[data-eway-card="expiry-date"]').value,
            securityCode: eCrypt.encryptValue(this.$field.querySelector('[data-eway-card="security-code"]').value, this.cseKey),
        };

        this.updateInputs('ewayTokenData', JSON.stringify(cardDetails));

        this.submitHandler.submitForm();
    }

    onAfterSubmit(e) {
        // Reset all hidden inputs
        this.updateInputs('ewayTokenData', '');
    }
}

window.FormieEway = FormieEway;
