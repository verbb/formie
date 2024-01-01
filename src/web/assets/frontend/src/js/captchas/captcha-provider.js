import { t, eventKey } from '../utils/utils';

export class FormieCaptchaProvider {
    constructor(settings = {}) {
        this.initialized = false;
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.isVisible = false;

        // Only initialize the field if it's visible. Use `IntersectionObserver` to check when visible
        // and also when hidden (navigating to other pages) to destroy it.
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].intersectionRatio == 0) {
                this.isVisible = false;

                // Only call the events if ready
                if (this.initialized) {
                    this.onHide();
                }
            } else {
                this.isVisible = true;

                // Only call the events if ready
                if (this.initialized) {
                    this.onShow();
                }
            }
        }, { root: this.$form });

        // Watch for when the input is visible/hidden, in the context of the form. But wait a little to start watching
        // to prevent double binding when still loading the form, or hidden behind conditions.
        setTimeout(() => {
            this.getPlaceholders().forEach(($placeholder) => {
                observer.observe($placeholder);
            });
        }, 500);
    }

    onShow() {

    }

    onHide() {

    }

    createInput() {
        const $div = document.createElement('div');

        // We need to handle re-initializing, so always empty the placeholder to start fresh to prevent duplicate captchas
        this.$placeholder.innerHTML = '';
        this.$placeholder.appendChild($div);

        return $div;
    }
}

window.FormieCaptchaProvider = FormieCaptchaProvider;
