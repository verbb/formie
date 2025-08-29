import { FormieCaptchaProvider } from './captcha-provider';
import { t, eventKey, ensureVariable } from '../utils/utils';

export class FormieRecaptchaV2Checkbox extends FormieCaptchaProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;
        this.siteKey = settings.siteKey;
        this.theme = settings.theme;
        this.size = settings.size;
        this.language = settings.language;
        this.loadingMethod = settings.loadingMethod;
        this.errorMessageClass = this.form.getClasses('errorMessage');
        this.scriptId = 'FORMIE_RECAPTCHA_SCRIPT';
        this.providerName = 'RecaptchaV2Checkbox';
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
        this.form.removeEventListener(eventKey('onBeforeFormieSubmit', this.providerName));
        this.form.removeEventListener(eventKey('onFormieCaptchaValidate', this.providerName));
        this.form.removeEventListener(eventKey('onAfterFormieSubmit', this.providerName));
    }

    renderCaptcha($placeholder) {
        this.$activePlaceholder = $placeholder;

        // Prepare an inner element to render the captcha
        const $container = this.createContainer($placeholder);

        this.form.addEventListener(this.$form, eventKey('onBeforeFormieSubmit', this.providerName), this.onBeforeSubmit.bind(this));
        this.form.addEventListener(this.$form, eventKey('onFormieCaptchaValidate', this.providerName), this.onValidate.bind(this));
        this.form.addEventListener(this.$form, eventKey('onAfterFormieSubmit', this.providerName), this.onAfterSubmit.bind(this));

        try {
            grecaptcha.ready(() => {
                const widgetId = grecaptcha.render($container, {
                    sitekey: this.siteKey,
                    theme: this.theme,
                    size: this.size,
                    'expired-callback': this.onExpired.bind(this),
                    'error-callback': this.onError.bind(this),
                });

                this.widgetIds.set($placeholder, widgetId);
            });
        } catch (e) {
            console.error('Failed to render ReCAPTCHA:', e);
        }
    }

    onBeforeSubmit(e) {
        // Save for later to trigger real submit
        this.submitHandler = e.detail.submitHandler;

        this.removeError();
    }

    onValidate(e) {
        // When not using Formie's theme JS, there's nothing preventing the form from submitting (the theme does).
        // And when the form is submitting, we can't query DOM elements, so stop early so the normal checks work.
        if (!this.$form.form.formTheme) {
            // Get the submit action from the form hidden input. This is normally taken care of by the theme
            this.form.submitAction = this.$form.querySelector('[name="submitAction"]').value || 'submit';
        }

        // Don't validate if we're not submitting (going back, saving)
        if (this.form.submitAction !== 'submit') {
            return;
        }

        const $token = this.$form.querySelector('[name="g-recaptcha-response"]');

        // Check to see if there's a valid token, otherwise, keep preventing the form.
        if (!$token || !$token.value.length) {
            this.addError();

            e.preventDefault();
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

    addError() {
        // Is there even a captcha on this page?
        if (!this.$activePlaceholder) {
            return;
        }

        if (this.submitHandler) {
            this.submitHandler.formSubmitError();
        }

        const $error = document.createElement('div');
        $error.className = this.form.getClasses('fieldError');
        $error.setAttribute('data-recaptcha-error', '');
        $error.textContent = t('This field is required.');

        this.$activePlaceholder.appendChild($error);
    }

    removeError() {
        // Is there even a captcha on this page?
        if (!this.$activePlaceholder) {
            return;
        }

        const $error = this.$activePlaceholder.querySelector('[data-recaptcha-error]');

        if ($error) {
            $error.remove();
        }
    }

    onExpired() {
        console.log('ReCAPTCHA has expired - reloading.');

        if (!this.$activePlaceholder) {
            return;
        }

        const widgetId = this.widgetIds.get(this.$activePlaceholder);

        if (widgetId !== undefined) {
            grecaptcha.reset(widgetId);
        }
    }

    onError(error) {
        console.error('ReCAPTCHA was unable to load');
    }
}

window.FormieRecaptchaV2Checkbox = FormieRecaptchaV2Checkbox;
