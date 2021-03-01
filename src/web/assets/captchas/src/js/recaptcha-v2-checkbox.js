import recaptcha from './inc/recaptcha';

export class FormieRecaptchaV2Checkbox {
    constructor(settings = {}) {
        this.formId = settings.formId;
        this.siteKey = settings.siteKey;
        this.theme = settings.theme;
        this.size = settings.size;
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
        this.$form.addEventListener('onBeforeFormieSubmit', this.onBeforeSubmit.bind(this));
        this.$form.addEventListener('onFormieValidate', this.onValidate.bind(this));
        this.$form.addEventListener('onAfterFormieSubmit', this.onAfterSubmit.bind(this));
    }

    renderCaptcha() {
        this.$placeholder = null;

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
            theme: this.theme,
            size: this.size,
            'expired-callback': this.onExpired.bind(this),
            'error-callback': this.onError.bind(this),
        }, id => {
            this.recaptchaId = id;

            // Update the placeholder with our ID, in case we need to re-render it
            this.$placeholder.setAttribute('data-recaptcha-id', id);
        });
    }

    onBeforeSubmit(e) {
        // Save for later to trigger real submit
        this.submitHandler = e.detail.submitHandler;

        this.removeError();
    }

    onValidate(e) {
        // Don't validate if we're going back in the form
        // Or, if there's no captcha on this page
        if (this.$form.goBack || this.$placeholder === null) {
            return;
        }

        var $token = this.$form.querySelector('[name="g-recaptcha-response"]');
        
        // Check to see if there's a valid token, otherwise, keep preventing the form.
        if (!$token || !$token.value.length) {
            this.addError();
    
            e.preventDefault();
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

    addError() {
        // Is there even a captcha field on this page?
        if (this.$placeholder === null) {
            return;
        }

        if (this.submitHandler) {
            this.submitHandler.formSubmitError();
        }

        var $error = document.createElement('div');
        $error.className = 'fui-error-message';
        $error.textContent = t('This field is required.');

        this.$placeholder.appendChild($error);
    }

    removeError() {
        // Is there even a captcha field on this page?
        if (this.$placeholder === null) {
            return;
        }

        var $error = this.$placeholder.querySelector('.fui-error-message');

        if ($error) {
            $error.remove();
        }
    }

    onExpired() {
        console.log('ReCAPTCHA has expired for #' + this.formId + ' - reloading.');

        recaptcha.reset(this.recaptchaId);
    }

    onError(error) {
        console.error('ReCAPTCHA was unable to load for #' + this.formId);
    }
}

window.FormieRecaptchaV2Checkbox = FormieRecaptchaV2Checkbox;
