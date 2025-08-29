import { FormieCaptchaProvider } from './captcha-provider';
import { t, eventKey, ensureVariable } from '../utils/utils';

export class FormieRecaptchaV2Invisible extends FormieCaptchaProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;
        this.siteKey = settings.siteKey;
        this.badge = settings.badge;
        this.language = settings.language;
        this.loadingMethod = settings.loadingMethod;
        this.scriptId = 'FORMIE_RECAPTCHA_SCRIPT';
        this.providerName = 'RecaptchaV2Invisible';
        this.widgetIds = new Map();
    }

    getPlaceholders() {
        return this.$form.querySelectorAll('[data-recaptcha-placeholder]');
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
            $script.src = `https://www.recaptcha.net/recaptcha/api.js?render=explicit&hl=${this.language}`;

            if (this.loadingMethod.includes('async')) {
                $script.async = true;
            }

            if (this.loadingMethod.includes('defer')) {
                $script.defer = true;
            }

            // Wait until Recaptcha.js has loaded, then initialize
            $script.onload = () => {
                ensureVariable('grecaptcha', 5000).then(() => {
                    this.renderCaptcha($placeholder);
                });
            };

            document.body.appendChild($script);
        } else {
            // Ensure that Recaptcha has been loaded and ready to use
            ensureVariable('grecaptcha').then(() => {
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
        this.$activePlaceholder = $placeholder;

        // Prepare an inner element to render the captcha
        const $container = this.createContainer($placeholder);

        this.form.addEventListener(this.$form, eventKey('onFormieCaptchaValidate', this.providerName), this.onValidate.bind(this));
        this.form.addEventListener(this.$form, eventKey('onAfterFormieSubmit', this.providerName), this.onAfterSubmit.bind(this));

        try {
            grecaptcha.ready(() => {
                const widgetId = grecaptcha.render($container, {
                    sitekey: this.siteKey,
                    badge: this.badge,
                    size: 'invisible',
                    callback: this.onVerify.bind(this),
                    'expired-callback': this.onExpired.bind(this),
                    'error-callback': this.onError.bind(this),
                });

                this.widgetIds.set($placeholder, widgetId);
            });
        } catch (e) {
            console.error('Failed to render ReCAPTCHA:', e);
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

        grecaptcha.execute(widgetId);
    }

    onVerify(token) {
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
        console.log('ReCAPTCHA has expired - reloading.');
    }

    onError(error) {
        console.error('ReCAPTCHA was unable to load');
    }
}

window.FormieRecaptchaV2Invisible = FormieRecaptchaV2Invisible;
