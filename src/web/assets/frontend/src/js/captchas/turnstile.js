import { turnstile } from './inc/turnstile';
import { FormieCaptchaProvider } from './captcha-provider';
import { t, eventKey, ensureVariable } from '../utils/utils';

export class FormieTurnstile extends FormieCaptchaProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;
        this.siteKey = settings.siteKey;
        this.loadingMethod = settings.loadingMethod;
        this.turnstileScriptId = 'FORMIE_TURNSTILE_SCRIPT';

        // We can start listening for the field to become visible to initialize it
        this.initialized = true;
    }

    getPlaceholders() {
        // We can have multiple captchas per form, so store them and render only when we need
        return this.$placeholders = this.$form.querySelectorAll('[data-turnstile-placeholder]');
    }

    onShow() {
        // Initialize the captcha only when it's visible
        this.initCaptcha();
    }

    onHide() {
        // Captcha is hidden, so reset everything
        this.onAfterSubmit();

        // Remove unique event listeners
        this.form.removeEventListener(eventKey('onFormieCaptchaValidate', 'Turnstile'));
        this.form.removeEventListener(eventKey('onAfterFormieSubmit', 'Turnstile'));
    }

    initCaptcha() {
        // Fetch and attach the script only once - this is in case there are multiple forms on the page.
        // They all go to a single callback which resolves its loaded state
        if (!document.getElementById(this.turnstileScriptId)) {
            const $script = document.createElement('script');
            $script.id = this.turnstileScriptId;
            $script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?onload=formieTurnstileOnLoadCallback&render=explicit';

            if (this.loadingMethod === 'async' || this.loadingMethod === 'asyncDefer') {
                $script.async = true;
            }

            if (this.loadingMethod === 'defer' || this.loadingMethod === 'asyncDefer') {
                $script.defer = true;
            }

            // Wait until Recaptcha.js has loaded, then initialize
            $script.onload = () => {
                this.renderCaptcha();
            };

            document.body.appendChild($script);
        } else {
            // Ensure that Recaptcha has been loaded and ready to use
            ensureVariable('turnstile').then(() => {
                this.renderCaptcha();
            });
        }

        if (!this.$placeholders.length) {
            console.error('Unable to find any Turnstile placeholders for [data-turnstile-placeholder]');

            return;
        }

        // Attach a custom event listener on the form
        this.form.addEventListener(this.$form, eventKey('onFormieCaptchaValidate', 'Turnstile'), this.onValidate.bind(this));
        this.form.addEventListener(this.$form, eventKey('onAfterFormieSubmit', 'Turnstile'), this.onAfterSubmit.bind(this));
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
                console.log('Unable to find turnstile placeholder for [data-turnstile-placeholder]');
            }

            return;
        }

        // Remove any existing token input
        const $token = this.$form.querySelector('[name="cf-turnstile-response"]');

        if ($token) {
            $token.remove();
        }

        // Check if we actually need to re-render this, or just refresh it...
        const currentTurnstileId = this.$placeholder.getAttribute('data-turnstile-id');

        if (currentTurnstileId !== null) {
            this.turnstileId = currentTurnstileId;

            turnstile.remove(this.turnstileId);

            // Clear the submit handler (as this has been re-rendered after a successful Ajax submission)
            // as Turnstile will verify on-render and will auto-submit the form again. Because in `onVerify`
            // we have a submit handler, the form will try and submit itself, which we don't want.
            this.submitHandler = null;
        }

        // Render the turnstile
        turnstile.render(this.$placeholder, {
            sitekey: this.siteKey,
            callback: this.onVerify.bind(this),
            'expired-callback': this.onExpired.bind(this),
            'timeout-callback': this.onTimeout.bind(this),
            'error-callback': this.onError.bind(this),
            'close-callback': this.onClose.bind(this),
        }, (id) => {
            this.turnstileId = id;

            // // Update the placeholder with our ID, in case we need to re-render it
            this.$placeholder.setAttribute('data-turnstile-id', id);
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

        // Trigger turnstile
        turnstile.execute(this.turnstileId);
    }

    onVerify(token) {
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
        // For a single-page form, reset the Turnstile, in case we want to fill out the form again
        // `renderCaptcha` will deal with both cases
        setTimeout(() => {
            this.renderCaptcha();
        }, 300);
    }

    onExpired() {
        console.log('Turnstile has expired - reloading.');

        turnstile.reset(this.turnstileId);
    }

    onTimeout() {
        console.log('Turnstile has expired challenge - reloading.');

        turnstile.reset(this.turnstileId);
    }

    onError(error) {
        console.error('Turnstile was unable to load');
    }

    onClose() {
        if (this.$form.form.formTheme) {
            this.$form.form.formTheme.removeLoading();
        }
    }
}

window.FormieTurnstile = FormieTurnstile;
