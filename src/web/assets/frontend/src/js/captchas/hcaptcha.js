import { FormieCaptchaProvider } from './captcha-provider';
import { t, eventKey, ensureVariable } from '../utils/utils';

export class FormieHcaptcha extends FormieCaptchaProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;
        this.siteKey = settings.siteKey;
        this.size = settings.size;
        this.language = settings.language;
        this.loadingMethod = settings.loadingMethod;
        this.scriptId = 'FORMIE_HCAPTCHA_SCRIPT';
        this.providerName = 'Hcaptcha';
        this.widgetIds = new Map();
    }

    getPlaceholders() {
        return this.$form.querySelectorAll('[data-hcaptcha-placeholder]');
    }

    onShow($placeholder) {
        super.onShow($placeholder);

        this.initCaptcha($placeholder);
    }

    onHide($placeholder) {
        super.onHide($placeholder);

        this.destroyCaptcha($placeholder);
    }

    initCaptcha($placeholder) {
        // Fetch and attach the script only once - this is in case there are multiple forms on the page.
        // They all go to a single callback which resolves its loaded state
        if (!document.getElementById(this.scriptId)) {
            const $script = document.createElement('script');
            $script.id = this.scriptId;
            $script.src = `https://js.hcaptcha.com/1/api.js?recaptchacompat=off&render=explicit&hl=${this.language}`;

            if (this.loadingMethod.includes('async')) {
                $script.async = true;
            }

            if (this.loadingMethod.includes('defer')) {
                $script.defer = true;
            }

            // Wait until hcaptcha.js has loaded, then initialize
            $script.onload = () => {
                ensureVariable('hcaptcha', 5000).then(() => {
                    this.renderCaptcha($placeholder);
                });
            };

            document.body.appendChild($script);
        } else {
            // Ensure that hcaptcha has been loaded and ready to use
            ensureVariable('hcaptcha').then(() => {
                this.renderCaptcha($placeholder);
            });
        }
    }

    destroyCaptcha($placeholder) {
        // Reset the DOM for the placeholder, if it's been rendered
        this.destroyContainer($placeholder);

        // Remove all events
        this.form.removeEventListener(eventKey('onFormieCaptchaValidate', this.providerName));
        this.form.removeEventListener(eventKey('onAfterFormieSubmit', this.providerName));
    }

    renderCaptcha($placeholder) {
        // Reset certain things about the captcha, if we're re-running this on the same page without refresh
        this.token = null;
        this.submitHandler = null;
        this.$activePlaceholder = $placeholder;

        // Prepare an inner element to render the captcha
        const $container = this.createContainer($placeholder);

        this.form.addEventListener(this.$form, eventKey('onFormieCaptchaValidate', this.providerName), this.onValidate.bind(this));
        this.form.addEventListener(this.$form, eventKey('onAfterFormieSubmit', this.providerName), this.onAfterSubmit.bind(this));

        try {
            const widgetId = hcaptcha.render($container, {
                sitekey: this.siteKey,
                size: this.size,
                callback: this.onVerify.bind(this),
                'expired-callback': this.onExpired.bind(this),
                'chalexpired-callback': this.onChallengeExpired.bind(this),
                'error-callback': this.onError.bind(this),
                'close-callback': this.onClose.bind(this),
            });

            this.widgetIds.set($placeholder, widgetId);
        } catch (e) {
            console.error('Failed to render Hcaptcha:', e);
        }
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
        if (this.form.submitAction !== 'submit') {
            return;
        }

        // Check if the form has an invalid flag set, don't bother going further
        if (e.detail.invalid) {
            return;
        }

        e.preventDefault();

        // Save for later to trigger real submit
        this.submitHandler = e.detail.submitHandler;

        // Find the visible placeholder
        if (!this.$activePlaceholder) {
            console.warn('No visible captcha placeholder found to execute.');
            return;
        }

        const widgetId = this.widgetIds.get(this.$activePlaceholder);

        if (typeof widgetId === 'undefined') {
            console.warn('No widget ID found for the visible captcha placeholder.');
            return;
        }

        // Check if the captcha has already been solved (someone clicking on the tick), otherwise the captcha triggeres twice
        if (this.token) {
            this.onVerify(this.token);
        } else {
            // Trigger hCaptcha - or check
            hcaptcha.execute(widgetId);
        }
    }

    onVerify(token) {
        // Store the token for a potential next time. This is useful if the user is clicking the tick on the captcha, then
        // submitting, which would trigger the captcha multiple times
        this.token = token;

        // Submit the form - we've hijacked it up until now
        if (this.submitHandler) {
            // Run the next submit action for the form. TODO: make this better!
            if (this.submitHandler.validatePayment()) {
                if (this.submitHandler.validateCustom()) {
                    this.submitHandler.submitForm();
                }
            }
        }
    }

    onAfterSubmit() {
        const { hasMultiplePages } = this.form.settings;

        // If a single-captcha form, re-render. Multi-captchas will handle themselves via onShow/onHide
        if (!hasMultiplePages && this.$activePlaceholder) {
            setTimeout(() => {
                this.destroyCaptcha(this.$activePlaceholder);

                this.renderCaptcha(this.$activePlaceholder);
            }, 300);
        }
    }

    onExpired() {
        console.log('hCaptcha has expired - reloading.');

        if (!this.$activePlaceholder) {
            return;
        }

        const widgetId = this.widgetIds.get(this.$activePlaceholder);

        if (widgetId !== undefined) {
            hcaptcha.reset(widgetId);
        }

        this.token = null;
    }

    onChallengeExpired() {
        console.log('hCaptcha has expired challenge - reloading.');

        if (!this.$activePlaceholder) {
            return;
        }

        const widgetId = this.widgetIds.get(this.$activePlaceholder);

        if (widgetId !== undefined) {
            hcaptcha.reset(widgetId);
        }

        this.token = null;
    }

    onError(error) {
        console.error('hCaptcha was unable to load');
    }

    onClose() {
        if (this.$form.form.formTheme) {
            this.$form.form.formTheme.removeLoading();
        }

        this.token = null;
    }
}

window.FormieHcaptcha = FormieHcaptcha;
