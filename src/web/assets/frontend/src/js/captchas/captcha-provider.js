import { t, eventKey } from '../utils/utils';

export class FormieCaptchaProvider {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.settings = settings;

        // Watch for when the input is visible/hidden, in the context of the form. But wait a little to start watching
        // to prevent double binding when still loading the form, or hidden behind conditions.
        setTimeout(() => {
            this.initObserver();
        }, 500);
    }

    initObserver() {
        // Only initialize the field if it's visible. Use `IntersectionObserver` to check when visible
        // and also when hidden (navigating to other pages) to destroy it.
        const observer = new IntersectionObserver((entries) => {
            for (const entry of entries) {
                const $placeholder = entry.target;

                if (entry.intersectionRatio === 0) {
                    this.onHide($placeholder);
                } else {
                    this.onShow($placeholder);
                }
            }
        }, {
            root: this.$form,

            // Include a large margin to cater for when other elements might cover the placeholder
            // as `IntersectionObserver` won't deem the placeholder in view if under something else
            rootMargin: '50px',
        });

        this.getPlaceholders().forEach(($placeholder) => {
            return observer.observe($placeholder);
        });
    }

    getPlaceholders() {
        return [];
    }

    onShow($placeholder) {

    }

    onHide($placeholder) {

    }

    createContainer($placeholder) {
        const $div = document.createElement('div');

        // We need to handle re-initializing, so always empty the placeholder to start fresh to prevent duplicate captchas
        $placeholder.innerHTML = '';
        $placeholder.appendChild($div);

        return $div;
    }

    destroyContainer($placeholder) {
        $placeholder.innerHTML = '';
    }
}

window.FormieCaptchaProvider = FormieCaptchaProvider;
