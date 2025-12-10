import { t, eventKey, ensureVariable } from '../utils/utils';
import { FormiePaymentProvider } from './payment-provider';

export class FormieMoneris extends FormiePaymentProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.$input = this.$field.querySelector('[data-fui-moneris-frame]');

        this.endpointUrl = settings.endpointUrl;

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
        this.form.removeEventListener(eventKey('onFormiePaymentValidate', 'moneris'));
        this.form.removeEventListener(eventKey('onAfterFormieSubmit', 'moneris'));
        this.form.removeEventListener(eventKey('message', 'moneris'));
    }

    initField() {
        // Attach custom event listeners on the form
        // Prevent binding multiple times. This can cause multiple payments!
        if (!this.boundEvents) {
            this.form.addEventListener(this.$form, eventKey('onFormiePaymentValidate', 'moneris'), this.onValidate.bind(this));
            this.form.addEventListener(this.$form, eventKey('onAfterFormieSubmit', 'moneris'), this.onAfterSubmit.bind(this));
            this.form.addEventListener(window, eventKey('message', 'moneris'), this.onMessage.bind(this));

            this.boundEvents = true;
        }
    }

    onMessage(e) {
        // Check this is the correct message
        if (!e.origin.includes('moneris')) {
            return;
        }

        // After tokenization, grab the data and update inputs
        this.updateInputs('monerisTokenId', e.data);

        // Handle resubmitting the form properly
        if (this.submitHandler) {
            this.processResubmit();
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

        // Initialize the tokenization from the iframe.
        this.$input.contentWindow.postMessage('tokenize', this.endpointUrl);
    }

    onAfterSubmit(e) {
        // Reset all hidden inputs
        this.updateInputs('monerisTokenId', '');
    }
}

window.FormieMoneris = FormieMoneris;
