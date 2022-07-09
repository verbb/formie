export class FormieJSCaptcha {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.sessionKey = settings.sessionKey;

        this.$placeholder = this.$form.querySelector('[data-jscaptcha-placeholder]');

        if (!this.$placeholder) {
            console.error('Unable to find JavaScript Captcha placeholder for [data-jscaptcha-placeholder]');

            return;
        }

        // Find the value to add, as appended to the page
        this.value = window[`Formie${this.sessionKey}`];

        if (!this.value) {
            console.error(`Unable to find JavaScript Captcha value for Formie${this.sessionKey}`);

            return;
        }

        const $input = document.createElement('input');
        $input.setAttribute('type', 'hidden');
        $input.setAttribute('name', this.sessionKey);
        $input.value = this.value;

        this.$placeholder.appendChild($input);
    }
}

window.FormieJSCaptcha = FormieJSCaptcha;
