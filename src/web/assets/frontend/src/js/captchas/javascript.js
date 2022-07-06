export class FormieJSCaptcha {
    constructor(settings = {}) {
        this.formId = settings.formId;
        this.sessionKey = settings.sessionKey;

        this.$form = document.querySelector('#' + this.formId);

        if (!this.$form) {
            console.error('Unable to find form #' + this.formId);

            return;
        }

        this.$placeholder = this.$form.querySelector('[data-jscaptcha-placeholder]');

        if (!this.$placeholder) {
            console.error('Unable to find JavaScript Captcha placeholder for #' + this.formId);

            return;
        }

        // Find the value to add, as appended to the page
        this.value = window['Formie' + this.sessionKey];

        if (!this.value) {
            console.error('Unable to find JavaScript Captcha value for Formie' + this.sessionKey);

            return;
        }

        var $input = document.createElement('input');
        $input.setAttribute('type', 'hidden');
        $input.setAttribute('name', this.sessionKey);
        $input.value = this.value;

        this.$placeholder.appendChild($input);
    }
}

window.FormieJSCaptcha = FormieJSCaptcha;
