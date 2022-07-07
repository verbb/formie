import { eventKey } from '../utils/utils';

export class FormieHidden {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.$input = this.$field.querySelector('input');
        this.cookieName = settings.cookieName;

        if (this.$input) {
            this.initHiddenField();
        } else {
            console.error('Unable to find hidden input.');
        }
    }

    initHiddenField() {
        // Populate the input with the cookie value.
        const cookieValue = this.getCookie(this.cookieName);

        if (cookieValue) {
            this.$input.value = cookieValue;
        }

        // Update the form hash, so we don't get change warnings
        if (this.form.formTheme) {
            this.form.formTheme.updateFormHash();
        }
    }

    getCookie(name) {
        const match = document.cookie.match(new RegExp(`(^| )${name}=([^;]+)`));

        if (match) {
            return match[2];
        }
    }
}

window.FormieHidden = FormieHidden;
