import { recaptcha } from './inc/recaptcha';
import { eventKey } from '../utils/utils';

export class FormieRecaptchaV3 {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.siteKey = settings.siteKey;
        this.badge = settings.badge;
        this.language = settings.language;
        this.loadingMethod = settings.loadingMethod;
        this.recaptchaScriptId = 'FORMIE_RECAPTCHA_SCRIPT';

        // Fetch and attach the script only once - this is in case there are multiple forms on the page.
        // They all go to a single callback which resolves its loaded state
        if (!document.getElementById(this.recaptchaScriptId)) {
            const $script = document.createElement('script');
            $script.id = this.recaptchaScriptId;
            $script.src = `https://www.recaptcha.net/recaptcha/api.js?onload=formieRecaptchaOnLoadCallback&render=explicit&hl=${this.language}`;

            if (this.loadingMethod === 'async' || this.loadingMethod === 'asyncDefer') {
                $script.async = true;
            }

            if (this.loadingMethod === 'defer' || this.loadingMethod === 'asyncDefer') {
                $script.defer = true;
            }

            document.body.appendChild($script);
        }

        // Wait for/ensure recaptcha script has been loaded
        recaptcha.checkRecaptchaLoad();

        // We can have multiple captchas per form, so store them and render only when we need
        this.$placeholders = this.$form.querySelectorAll('[data-recaptcha-placeholder]');

        if (!this.$placeholders.length) {
            console.error('Unable to find any ReCAPTCHA placeholders for [data-recaptcha-placeholder]');

            return;
        }

        // Render the captcha for just this page
        this.renderCaptcha();

        // Attach a custom event listener on the form
        this.form.addEventListener(this.$form, eventKey('onFormieCaptchaValidate', 'RecaptchaV3'), this.onValidate.bind(this));
        this.form.addEventListener(this.$form, eventKey('onAfterFormieSubmit', 'RecaptchaV3'), this.onAfterSubmit.bind(this));
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
            // should only be shown on the last step. But its nice to log this anyway
            if ($currentPage === null) {
                console.log('Unable to find ReCAPTCHA placeholder for [data-recaptcha-placeholder]');
            }

            return;
        }

        // Remove any existing token input
        const $token = this.$form.querySelector('[name="g-recaptcha-response"]');

        if ($token) {
            $token.remove();
        }

        // Check if we actually need to re-render this, or just refresh it...
        const currentRecaptchaId = this.$placeholder.getAttribute('data-recaptcha-id');

        if (currentRecaptchaId !== null) {
            this.recaptchaId = currentRecaptchaId;

            recaptcha.reset(this.recaptchaId);

            return;
        }

        // Render the recaptcha
        recaptcha.render(this.$placeholder, {
            sitekey: this.siteKey,
            badge: this.badge,
            size: 'invisible',
            callback: this.onVerify.bind(this),
            'expired-callback': this.onExpired.bind(this),
            'error-callback': this.onError.bind(this),
        }, (id) => {
            this.recaptchaId = id;

            // Update the placeholder with our ID, in case we need to re-render it
            this.$placeholder.setAttribute('data-recaptcha-id', id);

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

        // Trigger recaptcha
        recaptcha.execute(this.recaptchaId);
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
        // For a single-page form, reset the recaptcha, in case we want to fill out the form again
        // `renderCaptcha` will deal with both cases
        setTimeout(() => {
            this.renderCaptcha();
        }, 300);
    }

    onExpired() {
        console.log('ReCAPTCHA has expired - reloading.');

        recaptcha.reset(this.recaptchaId);
    }

    onError(error) {
        console.error('ReCAPTCHA was unable to load');
    }
}

window.FormieRecaptchaV3 = FormieRecaptchaV3;
