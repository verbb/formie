import { WidgetInstance } from 'friendly-challenge';

import { t, eventKey } from '../utils/utils';

export class FormieFriendlyCaptcha {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.siteKey = settings.siteKey;

        // We can have multiple captchas per form, so store them and render only when we need
        this.$placeholders = this.$form.querySelectorAll('[data-friendly-captcha-placeholder]');

        if (!this.$placeholders) {
            console.error('Unable to find any Friendly Captcha placeholders for [data-friendly-captcha-placeholder]');

            return;
        }

        // Render the captcha for just this page
        this.renderCaptcha();

        // Attach a custom event listener on the form
        this.form.addEventListener(this.$form, eventKey('onFormieCaptchaValidate', 'FriendlyCaptcha'), this.onValidate.bind(this));
        this.form.addEventListener(this.$form, eventKey('onAfterFormieSubmit', 'FriendlyCaptcha'), this.onAfterSubmit.bind(this));
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
            // should only be shown on the last step. But its nice to log this anyway.
            if ($currentPage === null) {
                console.log('Unable to find Friendly Captcha placeholder for [data-friendly-captcha-placeholder]');
            }

            return;
        }

        // Remove any existing token input
        const $token = this.$form.querySelector('[name="frc-captcha-solution"]');

        if ($token) {
            $token.remove();
        }

        if (this.widget) {
            this.widget.reset();
        }

        // Render the captcha
        this.widget = new WidgetInstance(this.$placeholder, {
            sitekey: this.siteKey,
            startMode: 'none',
            doneCallback: this.onVerify.bind(this),
            errorCallback: this.onError.bind(this),
        });
    }

    onValidate(e) {
        // When not using Formie's theme JS, there's nothing preventing the form from submitting (the theme does).
        // And when the form is submitting, we can't query DOM elements, so stop early so the normal checks work.
        if (!this.$form.form.formTheme) {
            e.preventDefault();

            // Get the submit action from the form hidden input. This is normally taken care of by the theme
            this.form.submitAction = this.$form.querySelector('[name="submitAction"]').value || 'submit';
        }

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

        // Trigger captcha - unless we've already verified
        if (this.token) {
            // The user has verified manually, before pressing submit.
            this.onVerify(this.token);
        } else {
            this.widget.start();
        }
    }

    onVerify(token) {
        // Save the token in case we've clicked on the verification, and not the submit button
        this.token = token;

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
        // For a single-page form, reset the hCaptcha, in case we want to fill out the form again
        // `renderCaptcha` will deal with both cases
        setTimeout(() => {
            this.renderCaptcha();
        }, 300);
    }

    onError(error) {
        console.error('Friendly Captcha was unable to load');
    }
}

window.FormieFriendlyCaptcha = FormieFriendlyCaptcha;
