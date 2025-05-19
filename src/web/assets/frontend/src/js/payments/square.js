import { t, eventKey, ensureVariable } from '../utils/utils';
import { FormiePaymentProvider } from './payment-provider';

export class FormieSquare extends FormiePaymentProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.$input = this.$field.querySelector('[data-fui-square-button]');

        this.applicationId = settings.applicationId;
        this.locationId = settings.locationId;
        this.environment = settings.environment;
        this.squareScriptId = 'FORMIE_SQUARE_SCRIPT';

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
        this.form.removeEventListener(eventKey('onFormiePaymentValidate', 'square'));
        this.form.removeEventListener(eventKey('onAfterFormieSubmit', 'square'));
    }

    initField() {
        // Fetch and attach the script only once - this is in case there are multiple forms on the page.
        // They all go to a single callback which resolves its loaded state
        if (!document.getElementById(this.squareScriptId)) {
            const scriptUrl = this.environment === 'sandbox' ? 'https://sandbox.web.squarecdn.com/v1/square.js' : 'https://web.squarecdn.com/v1/square.js';

            const $script = document.createElement('script');
            $script.id = this.squareScriptId;
            $script.src = scriptUrl;

            $script.async = true;
            $script.defer = true;

            // Wait until JS has loaded, then initialize
            $script.onload = () => {
                this.renderButton();
            };

            document.body.appendChild($script);
        } else {
            // Ensure that PayPal has been loaded and ready to use
            ensureVariable('Square').then(() => {
                this.renderButton();
            });
        }

        // Attach custom event listeners on the form
        // Prevent binding multiple times. This can cause multiple payments!
        if (!this.boundEvents) {
            this.form.addEventListener(this.$form, eventKey('onFormiePaymentValidate', 'square'), this.onValidate.bind(this));
            this.form.addEventListener(this.$form, eventKey('onAfterFormieSubmit', 'square'), this.onAfterSubmit.bind(this));

            this.boundEvents = true;
        }
    }

    async renderButton() {
        try {
            const payments = Square.payments(this.applicationId, this.locationId);

            this.card = await payments.card();

            await this.card.attach(this.$input);
        } catch (error) {
            console.error('Square setup failed:', error);

            this.addError('Unable to initialize payment. Please try again later.');
        }
    }

    async onValidate(e) {
        // Don't validate if we're not submitting (going back, saving)
        // Check if the form has an invalid flag set, don't bother going further
        if (this.form.submitAction !== 'submit' || e.detail.invalid) {
            return;
        }

        e.preventDefault();

        // Save for later to trigger real submit
        this.submitHandler = e.detail.submitHandler;

        try {
            const result = await this.card.tokenize();

            if (result.status === 'OK') {
                this.updateInputs('squarePaymentId', result.token);
                this.submitHandler.submitForm();
            } else {
                this.addError(result.errors?.[0]?.message || 'Payment tokenization failed.');
            }
        } catch (err) {
            console.error('Tokenization error:', err);

            this.addError('Payment tokenization failed. Please try again.');
        }
    }

    onAfterSubmit(e) {
        // Reset all hidden inputs
        this.updateInputs('squarePaymentId', '');
    }
}

window.FormieSquare = FormieSquare;
