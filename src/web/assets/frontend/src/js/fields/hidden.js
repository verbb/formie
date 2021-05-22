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
        this.$input.value = this.getCookie(this.cookieName);

        // Update the form hash, so we don't get change warnings
        if (this.form.formTheme) {
            this.form.formTheme.updateFormHash();
        }
    }

    getCookie(name) {
        var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        
        if (match) {
            return match[2];
        }
    }
}

window.FormieHidden = FormieHidden;
