import recaptcha from './inc/recaptcha';
import { isVisible } from './inc/visible';

export class FormieRecaptchaV3 {
    constructor(settings = {}) {
        this.formId = settings.formId;
        this.siteKey = settings.siteKey;
        this.language = settings.language;
        this.recaptchaScriptId = 'FORMIE_RECAPTCHA_SCRIPT';

        // Fetch and attach the script only once - this is in case there are multiple forms on the page.
        // They all go to a single callback which resolves its loaded state
        if (!document.getElementById(this.recaptchaScriptId)) {
            var $script = document.createElement('script');
            $script.id = this.recaptchaScriptId;
            $script.src = 'https://www.recaptcha.net/recaptcha/api.js?onload=formieRecaptchaOnLoadCallback&render=' + this.siteKey + '&hl=' + this.language;
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

        // Technically, there's nothing to render for V3, but we want to keep track of whether
        // we need to submit to recaptcha for this particular page or not. This is what V2 does
        // with the placeholder for render, so we might as well copy that!
        this.renderCaptcha();

        // Attach a custom event listener on the form
        this.$form.addEventListener('onFormieValidate', this.onValidate.bind(this));
    }

    renderCaptcha() {
        this.$placeholder = null;

        // Get the current page's captcha - find the first placeholder that's non-invisible
        this.$placeholders.forEach($placeholder => {
            if (isVisible($placeholder)) {
                this.$placeholder = $placeholder;
            }
        });
    }

    onValidate(e) {
        // Don't validate if we're going back in the form
        if (this.$form.goToPage || this.$placeholder === null) {
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
        recaptcha.executeV3(this.siteKey).then((token) => {
            this.onVerify(token);
        });
    }

    onVerify(token) {
        // Check if we should validate on a page
        if (this.$placeholder === null) {
            return;
        }

        var $input = document.createElement('input');
        $input.type = 'hidden';
        $input.name = 'g-recaptcha-response';
        $input.value = token;

        this.$form.appendChild($input);

        // Submit the form - we've hijacked it up until now
        this.submitHandler.submitForm();
    }
}

window.FormieRecaptchaV3 = FormieRecaptchaV3;
