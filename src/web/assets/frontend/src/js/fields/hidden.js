import { t, eventKey } from '../utils/utils';

import Cookies from 'js-cookie';

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
        const cookieValue = Cookies.get(this.cookieName);

        if (cookieValue) {
            this.$input.value = cookieValue;
        }

        // Update the form hash, so we don't get change warnings
        if (this.form.formTheme) {
            this.form.formTheme.updateFormHash();
        }
    }
}

window.FormieHidden = FormieHidden;
