import { t, eventKey } from '../utils/utils';
import { FormiePaymentProvider } from './payment-provider';

export class FormieMollie extends FormiePaymentProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;

        // Offsite redirect has no heavy init; bind immediately so hidden fields still receive
        // FormiePaymentMollieRedirect (IntersectionObserver never calls onShow when not visible).
        this.initialized = true;
        this.initField();
    }

    onShow() {
        this.initField();
    }

    onHide() {
        // Do not remove the redirect listener. Hidden fields get intersectionRatio 0, so the
        // base observer calls onHide without a prior onShow — removing here would break checkout.
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
