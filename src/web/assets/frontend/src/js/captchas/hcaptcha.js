import { recaptcha as hcaptcha } from './inc/recaptcha';
import { eventKey } from '../utils/utils';

export class FormieHcaptcha {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.siteKey = settings.siteKey;
        this.size = settings.size;
        this.language = settings.language;
        this.loadingMethod = settings.loadingMethod;
        this.hCaptchaScriptId = 'FORMIE_HCAPTCHA_SCRIPT';

        // Fetch and attach the script only once - this is in case there are multiple forms on the page.
        // They all go to a single callback which resolves its loaded state
        if (!document.getElementById(this.hCaptchaScriptId)) {
            const $script = document.createElement('script');
            $script.id = this.hCaptchaScriptId;
            $script.src = `https://js.hcaptcha.com/1/api.js?onload=formieRecaptchaOnLoadCallback&render=explicit&hl=${this.language}`;

            if (this.loadingMethod === 'async' || this.loadingMethod === 'asyncDefer') {
                $script.async = true;
            }

            if (this.loadingMethod === 'defer' || this.loadingMethod === 'asyncDefer') {
                $script.defer = true;
            }

            document.body.appendChild($script);
        }

        // Wait for/ensure hCaptcha script has been loaded
        hcaptcha.checkRecaptchaLoad();

        // We can have multiple captchas per form, so store them and render only when we need
        this.$placeholders = this.$form.querySelectorAll('[data-hcaptcha-placeholder]');

        if (!this.$placeholders) {
            console.error('Unable to find any hCaptcha placeholders for [data-hcaptcha-placeholder]');

            return;
        }

        // Render the captcha for just this page
        this.renderCaptcha();

        // Attach a custom event listener on the form
        this.form.addEventListener(this.$form, eventKey('onFormieCaptchaValidate', 'Hcaptcha'), this.onValidate.bind(this));
        this.form.addEventListener(this.$form, eventKey('onAfterFormieSubmit', 'Hcaptcha'), this.onAfterSubmit.bind(this));
    }

    renderCaptcha() {
        this.$placeholder = null;

        // Get the active page
        let $currentPage = null;

        // Find the current page, from Formie's JS
        if (this.$form.form.formTheme) {
            // eslint-disable-next-line
            $currentPage = this.$form.form.formTheme.$currentPage;
        }

        const { hasMultiplePages } = this.$form.form.settings;

        // Get the current page's captcha - find the first placeholder that's non-invisible
        this.$placeholders.forEach(($placeholder) => {
            if ($currentPage && $currentPage.contains($placeholder)) {
                this.$placeholder = $placeholder;
            }
        });

        // If a single-page form, get the first placeholder
        if (!hasMultiplePages && this.$placeholder === null) {
            // eslint-disable-next-line
            this.$placeholder = this.$placeholders[0];
        }

        if (this.$placeholder === null) {
            // This is okay in some instances - notably for multi-page forms where the captcha
            // should only be shown on the last step. But its nice to log this anyway.
            if ($currentPage === null) {
                console.log('Unable to find hCaptcha placeholder for [data-hcaptcha-placeholder]');
            }

            return;
        }

        // Remove any existing token input
        const $token = this.$form.querySelector('[name="h-captcha-response"]');

        if ($token) {
            $token.remove();
        }

        // Check if we actually need to re-render this, or just refresh it...
        const currentHcaptchaId = this.$placeholder.getAttribute('data-hcaptcha-id');

        if (currentHcaptchaId !== null) {
            this.hcaptchaId = currentHcaptchaId;

            hcaptcha.reset(this.hcaptchaId);

            return;
        }

        // Render the hCaptcha
        hcaptcha.render(this.$placeholder, {
            sitekey: this.siteKey,
            size: this.size,
            callback: this.onVerify.bind(this),
            'expired-callback': this.onExpired.bind(this),
            'chalexpired-callback': this.onChallengeExpired.bind(this),
            'error-callback': this.onError.bind(this),
            'close-callback': this.onClose.bind(this),
        }, (id) => {
            this.hcaptchaId = id;

            // Update the placeholder with our ID, in case we need to re-render it
            this.$placeholder.setAttribute('data-hcaptcha-id', id);

            // Add a `tabindex` attribute to the iframe to prevent tabbing-to
            const iframe = this.$placeholder.querySelector('iframe');

            if (iframe) {
                iframe.setAttribute('tabindex', '-1');
            }
        });
    }

    onValidate(e) {
        // Don't validate if we're not submitting (going back, saving)
        if (this.form.submitAction !== 'submit' || this.$placeholder === null) {
            return;
        }

        // Check if the form has an invalid flag set, don't bother going further
        if (e.detail.invalid) {
            return;
        }

        e.preventDefault();

        // Save for later to trigger real submit
        this.submitHandler = e.detail.submitHandler;

        // Trigger hCaptcha
        hcaptcha.execute(this.hcaptchaId);
    }

    onVerify(token) {
        // Submit the form - we've hijacked it up until now
        if (this.submitHandler) {
            // Run the next submit action for the form. TODO: make this better!
            if (this.submitHandler.validatePayment()) {
                this.submitHandler.submitForm();
            }
        }
    }

    onAfterSubmit(e) {
        // For a multi-page form, we need to remove the current captcha, then render the next pages.
        // For a single-page form, reset the hCaptcha, in case we want to fill out the form again
        // `renderCaptcha` will deal with both cases
        setTimeout(() => {
            this.renderCaptcha();
        }, 300);
    }

    onExpired() {
        console.log('hCaptcha has expired - reloading.');

        hcaptcha.reset(this.hcaptchaId);
    }

    onChallengeExpired() {
        console.log('hCaptcha has expired challenge - reloading.');

        hcaptcha.reset(this.hcaptchaId);
    }

    onError(error) {
        console.error('hCaptcha was unable to load');
    }

    onClose() {
        if (this.$form.form.formTheme) {
            this.$form.form.formTheme.removeLoading();
        }
    }
}

window.FormieHcaptcha = FormieHcaptcha;
