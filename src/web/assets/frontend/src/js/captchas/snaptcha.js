import { FormieCaptchaProvider } from './captcha-provider';
import { eventKey } from '../utils/utils';

export class FormieSnaptchaCaptcha extends FormieCaptchaProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;
        this.sessionKey = settings.sessionKey;
        this.value = settings.value;

        this.$placeholder = this.$form.querySelector('[data-snaptcha-captcha-placeholder]');

        if (!this.$placeholder) {
            console.error('Unable to find Snaptcha Captcha placeholder for [data-snaptcha-captcha-placeholder]');

            return;
        }

        this.createInput();

        // Each form submission, we should fetch a new token
        this.form.addEventListener(this.$form, eventKey('onAfterFormieSubmit', 'SnaptchaCaptcha'), this.onAfterSubmit.bind(this));
    }

    createInput() {
        // We need to handle re-initializing, so always empty the placeholder to start fresh to prevent duplicate captchas
        this.$placeholder.innerHTML = '';

        const $input = document.createElement('input');
        $input.setAttribute('type', 'hidden');
        $input.setAttribute('name', this.sessionKey);
        $input.value = this.value;

        this.$placeholder.appendChild($input);
    }

    onAfterSubmit(e) {
        // Refresh all tokens, but just grab the Snaptcha token
        Formie.refreshFormTokens(this.form, (data) => {
            this.sessionKey = data.captchas?.snaptcha?.sessionKey;
            this.value = data.captchas?.snaptcha?.value;

            this.createInput();

            // Update the form's hash (if using Formie's themed JS)
            if (this.form.formTheme) {
                this.form.formTheme.updateFormHash();
            }
        }, false);
    }
}

window.FormieSnaptchaCaptcha = FormieSnaptchaCaptcha;
