import { t, eventKey } from '../utils/utils';
import { FormiePaymentProvider } from './payment-provider';

export class FormieMollie extends FormiePaymentProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;

        // We can start listening for the field to become visible to initialize it
        this.initialized = true;
    }

    onShow() {
        // Initialize the field only when it's visible
        this.initField();
    }

    onHide() {
        this.boundEvents = false;

        // Remove unique event listeners
        this.form.removeEventListener(eventKey('FormiePaymentMollieRedirect', 'mollie'));
    }

    initField() {
        // Attach custom event listeners on the form
        // Prevent binding multiple times. This can cause multiple payments!
        if (!this.boundEvents) {
            this.form.addEventListener(this.$form, eventKey('FormiePaymentMollieRedirect', 'mollie'), this.onRedirect.bind(this));

            this.boundEvents = true;
        }
    }

    onRedirect(e) {
        const { data } = e.detail;

        if (!data || !data.checkoutUrl) {
            return this.addError('Missing Mollie checkout URL.');
        }

        // Perform a full browser redirect
        window.location.href = data.checkoutUrl;
    }
}

window.FormieMollie = FormieMollie;
