import { FormieCaptchaProvider } from './captcha-provider';
import { t, eventKey, ensureVariable } from '../utils/utils';

export class FormieTurnstile extends FormieCaptchaProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;
        this.siteKey = settings.siteKey;
        this.theme = settings.theme;
        this.size = settings.size;
        this.appearance = settings.appearance;
        this.loadingMethod = settings.loadingMethod;
        this.scriptId = 'FORMIE_TURNSTILE_SCRIPT';
        this.providerName = 'Turnstile';
        this.widgetIds = new Map();
    }

    getPlaceholders() {
        return this.$form.querySelectorAll('[data-turnstile-placeholder]');
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
            $script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';

            if (this.loadingMethod.includes('async')) {
                $script.async = true;
            }

            if (this.loadingMethod.includes('defer')) {
                $script.defer = true;
            }

            // Wait until turnstile.js has loaded, then initialize
            $script.onload = () => {
                ensureVariable('turnstile', 5000).then(() => {
                    this.renderCaptcha($placeholder);
                });
            };

            document.body.appendChild($script);
        } else {
            // Ensure that turnstile has been loaded and ready to use
            ensureVariable('turnstile').then(() => {
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
        // Clear the submit handler (as this has been re-rendered after a successful Ajax submission)
        // as Turnstile will verify on-render and will auto-submit the form again. Because in `onVerify`
        // we have a submit handler, the form will try and submit itself, which we don't want.
        this.submitHandler = null;

        this.$activePlaceholder = $placeholder;

        // Prepare an inner element to render the captcha
        const $container = this.createContainer($placeholder);

        this.form.addEventListener(this.$form, eventKey('onFormieCaptchaValidate', this.providerName), this.onValidate.bind(this));
        this.form.addEventListener(this.$form, eventKey('onAfterFormieSubmit', this.providerName), this.onAfterSubmit.bind(this));

        try {
            const widgetId = turnstile.render($container, {
                sitekey: this.siteKey,
                callback: this.onVerify.bind(this),
                'expired-callback': this.onExpired.bind(this),
                'timeout-callback': this.onTimeout.bind(this),
                'error-callback': this.onError.bind(this),
                'close-callback': this.onClose.bind(this),
                theme: this.theme,
                size: this.size,
                appearance: this.appearance,
            });

            this.widgetIds.set($placeholder, widgetId);
        } catch (e) {
            console.error('Failed to render Turnstile:', e);
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

        turnstile.execute(widgetId);
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
        console.log('Turnstile has expired - reloading.');

        if (!this.$activePlaceholder) {
            return;
        }

        const widgetId = this.widgetIds.get(this.$activePlaceholder);

        if (widgetId !== undefined) {
            turnstile.reset(widgetId);
        }
    }

    onTimeout() {
        console.log('Turnstile has expired challenge - reloading.');

        if (!this.$activePlaceholder) {
            return;
        }

        const widgetId = this.widgetIds.get(this.$activePlaceholder);

        if (widgetId !== undefined) {
            turnstile.reset(widgetId);
        }
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
