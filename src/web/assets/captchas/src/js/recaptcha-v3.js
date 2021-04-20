import recaptcha from './inc/recaptcha';

export class FormieRecaptchaV3 {
    constructor(settings = {}) {
        this.formId = settings.formId;
        this.siteKey = settings.siteKey;
        this.badge = settings.badge;
        this.language = settings.language;
        this.recaptchaScriptId = 'FORMIE_RECAPTCHA_SCRIPT';

        // Fetch and attach the script only once - this is in case there are multiple forms on the page.
        // They all go to a single callback which resolves its loaded state
        if (!document.getElementById(this.recaptchaScriptId)) {
            var $script = document.createElement('script');
            $script.id = this.recaptchaScriptId;
            $script.src = 'https://www.recaptcha.net/recaptcha/api.js?onload=formieRecaptchaOnLoadCallback&render=explicit&hl=' + this.language;
            $script.async = true;
            $script.defer = true;

            document.body.appendChild($script);
        }

        // Wait for/ensure recaptcha script has been loaded
        recaptcha.checkRecaptchaLoad();

        this.$form = document.querySelector('#' + this.formId);

        if (!this.$form) {
            console.error('Unable to find form #' + this.formId);

            return;
        }

        // We can have multiple captchas per form (for ajax), so store them and render only when we need
        this.$placeholders = this.$form.querySelectorAll('.formie-recaptcha-placeholder');

        if (!this.$placeholders) {
            console.error('Unable to find any ReCAPTCHA placeholders for #' + this.formId);

            return;
        }

        // Render the captcha for just this page
        this.renderCaptcha();

        // Attach a custom event listener on the form
        this.$form.addEventListener('onFormieValidate', this.onValidate.bind(this));
        this.$form.addEventListener('onAfterFormieSubmit', this.onAfterSubmit.bind(this));
    }

    renderCaptcha() {
        // Default to the first placeholder available.
        // eslint-disable-next-line
        this.$placeholder = this.$placeholders[0];

        // Get the active page
        var { $currentPage } = this.$form.form.formTheme;

        // Get the current page's captcha - find the first placeholder that's non-invisible
        this.$placeholders.forEach($placeholder => {
            if ($currentPage && $currentPage.contains($placeholder)) {
                this.$placeholder = $placeholder;
            }
        });

        if (this.$placeholder === null) {
            // This is okay in some instances - notably for multi-page forms where the captcha
            // should only be shown on the last step. But its nice to log this anyway
            console.log('Unable to find ReCAPTCHA placeholder for #' + this.formId);

            return;
        }

        // Remove any existing token input (more for ajax multi-pages)
        var $token = this.$form.querySelector('[name="g-recaptcha-response"]');

        if ($token) {
            $token.remove();
        }

        // Check if we actually need to re-render this, or just refresh it...
        var currentRecaptchaId = this.$placeholder.getAttribute('data-recaptcha-id');

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
        }, id => {
            this.recaptchaId = id;

            // Update the placeholder with our ID, in case we need to re-render it
            this.$placeholder.setAttribute('data-recaptcha-id', id);
        });
    }

    onValidate(e) {
        // Don't validate if we're going back in the form
        if (this.$form.goBack || this.$placeholder === null) {
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
            this.submitHandler.submitForm();
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
        console.log('ReCAPTCHA has expired for #' + this.formId + ' - reloading.');

        recaptcha.reset(this.recaptchaId);
    }

    onError(error) {
        console.error('ReCAPTCHA was unable to load for #' + this.formId);
    }
}

window.FormieRecaptchaV3 = FormieRecaptchaV3;
