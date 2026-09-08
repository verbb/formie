import { FormieCaptchaProvider } from './captcha-provider';
import { eventKey } from '../utils/utils';

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
        this.onloadCallbackName = 'formieHcaptchaOnLoad';
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

    getHcaptchaApi() {
        // hCaptcha defines `window.hcaptcha` before its JS API is fully ready. Waiting on the
        // global alone (or script.onload) races and can leave widgets in a broken state where
        // `execute()` never calls back — especially when the script is first loaded as a modal
        // opens. Use hCaptcha's own `onload` callback, and share one promise for every form.
        if (window.formieHcaptchaReady) {
            return window.formieHcaptchaReady;
        }

        window.formieHcaptchaReady = new Promise((resolve, reject) => {
            const finish = () => {
                delete window[this.onloadCallbackName];
                resolve(window.hcaptcha);
            };

            // Preloaded (or already-initialised) API — safe to use immediately.
            if (document.getElementById(this.scriptId) && window.hcaptcha) {
                finish();
                return;
            }

            window[this.onloadCallbackName] = finish;

            if (!document.getElementById(this.scriptId)) {
                const $script = document.createElement('script');
                $script.id = this.scriptId;
                $script.src = `https://js.hcaptcha.com/1/api.js?recaptchacompat=off&render=explicit&onload=${this.onloadCallbackName}&hl=${this.language}`;

                if (this.loadingMethod.includes('async')) {
                    $script.async = true;
                }

                if (this.loadingMethod.includes('defer')) {
                    $script.defer = true;
                }

                $script.onerror = () => {
                    delete window.formieHcaptchaReady;
                    delete window[this.onloadCallbackName];
                    reject(new Error('Failed to load hCaptcha script'));
                };

                document.body.appendChild($script);
            } else {
                // Script tag is already on the page (e.g. site preload) but the API is not ready
                // yet, and we cannot retrofit `onload` onto an existing tag. Wait for the global;
                // a preload started at page load is unlikely to hit the early-define race that
                // happens when injecting at modal-open time.
                const start = Date.now();
                const waitForGlobal = () => {
                    if (window.hcaptcha) {
                        finish();
                    } else if ((Date.now() - start) >= 10000) {
                        delete window.formieHcaptchaReady;
                        delete window[this.onloadCallbackName];
                        reject(new Error('Timed out waiting for hCaptcha'));
                    } else {
                        setTimeout(waitForGlobal, 30);
                    }
                };

                waitForGlobal();
            }
        });

        return window.formieHcaptchaReady;
    }

    initCaptcha($placeholder) {
        this.getHcaptchaApi().then(() => {
            this.renderCaptcha($placeholder);
        }).catch((error) => {
            console.error(error);
        });
    }

    destroyCaptcha($placeholder) {
        // Reset the DOM for the placeholder, if it's been rendered
        this.destroyContainer($placeholder);

        // Remove all events
        this.form.removeEventListener(eventKey('onFormieCaptchaValidate', this.providerName));
        this.form.removeEventListener(eventKey('onAfterFormieSubmit', this.providerName));
        this.form.removeEventListener(eventKey('onFormieSubmitError', this.providerName));
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
        this.form.addEventListener(this.$form, eventKey('onFormieSubmitError', this.providerName), this.onSubmitError.bind(this));

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
        this.refreshSinglePageCaptchaWidget();
    }

    onSubmitError() {
        this.refreshSinglePageCaptchaWidget();
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
