import { t, eventKey } from '../utils/utils';
import { FormiePaymentProvider } from './payment-provider';

export class FormieGoCardless extends FormiePaymentProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;

        this.initialized = true;
        this.initField();
    }

    onShow() {
        this.initField();
    }

    onHide() {
        // See FormieMollie: hidden fields must keep the offsite redirect listener attached.
    }

    initField() {
        // Attach custom event listeners on the form
        // Prevent binding multiple times. This can cause multiple payments!
        if (!this.boundEvents) {
            this.form.addEventListener(this.$form, eventKey('FormiePaymentGoCardlessRedirect', 'goCardless'), this.onRedirect.bind(this));

            this.boundEvents = true;
        }
    }

    onRedirect(e) {
        const { data } = e.detail;

        if (!data || !data.redirectUrl) {
            return this.addError('Missing GoCardless redirect URL.');
        }

        if (this.form.formTheme) {
            this.form.formTheme.updateFormHash();
        }

        window.location.href = data.redirectUrl;
    }
}

window.FormieGoCardless = FormieGoCardless;
