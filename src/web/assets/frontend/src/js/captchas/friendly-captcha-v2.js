import { FriendlyCaptchaSDK } from '@friendlycaptcha/sdk';

import { FormieCaptchaProvider } from './captcha-provider';
import { eventKey } from '../utils/utils';

// Friendly Captcha documents one SDK instance per page; call `createWidget()` for each widget.
// Store on `window` so multiple loads of this bundle (one per Formie embed) still share one SDK.
const FRIENDLY_CAPTCHA_SDK_GLOBAL = '__formieFriendlyCaptchaSdkV2__';

function getSharedFriendlyCaptchaSdk() {
    if (!window[FRIENDLY_CAPTCHA_SDK_GLOBAL]) {
        window[FRIENDLY_CAPTCHA_SDK_GLOBAL] = new FriendlyCaptchaSDK();
    }

    return window[FRIENDLY_CAPTCHA_SDK_GLOBAL];
}

export class FormieFriendlyCaptcha extends FormieCaptchaProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;
        this.siteKey = settings.siteKey;
        this.language = settings.language;
        this.startMode = settings.startMode;
        this.providerName = 'FriendlyCaptcha';
        this.widgets = new Map();
        this.sdk = getSharedFriendlyCaptchaSdk();

        // Stable listener references so deduped add/remove via EventManager stays predictable.
        this._onValidate = this.onValidate.bind(this);
        this._onAfterSubmit = this.onAfterSubmit.bind(this);
        this._onSubmitError = this.onSubmitError.bind(this);
    }

    getPlaceholders() {
        return this.$form.querySelectorAll('[data-friendly-captcha-placeholder]');
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
        this.renderCaptcha($placeholder);
    }

    destroyCaptcha($placeholder) {
        const widget = this.widgets.get($placeholder);

        if (widget) {
            widget.destroy();
            this.widgets.delete($placeholder);
        }

        this.token = null;

        // Reset the DOM for the placeholder, if it's been rendered
        this.destroyContainer($placeholder);

        // Remove all events
        this.form.removeEventListener(eventKey('onFormieCaptchaValidate', this.providerName));
        this.form.removeEventListener(eventKey('onAfterFormieSubmit', this.providerName));
        this.form.removeEventListener(eventKey('onFormieSubmitError', this.providerName));
    }

    renderCaptcha($placeholder) {
        // Clear the submit handler to present Start Mode Auto from automatically re-submitting the form
        this.submitHandler = null;

        this.$activePlaceholder = $placeholder;

        // Prepare an inner element to render the captcha
        const $container = this.createContainer($placeholder);

        this.form.addEventListener(this.$form, eventKey('onFormieCaptchaValidate', this.providerName), this._onValidate);
        this.form.addEventListener(this.$form, eventKey('onAfterFormieSubmit', this.providerName), this._onAfterSubmit);
        this.form.addEventListener(this.$form, eventKey('onFormieSubmitError', this.providerName), this._onSubmitError);

        try {
            const widget = this.sdk.createWidget({
                element: $container,
                sitekey: this.siteKey,
                startMode: this.startMode,
                language: this.language,
            });

            widget.addEventListener('frc:widget.complete', (event) => {
                this.onVerify(event.detail.response);
            });

            widget.addEventListener('frc:widget.error', (event) => {
                this.onError(event.detail.error);
            });

            widget.addEventListener('frc:widget.expire', () => {
                this.token = null;
            });

            this.widgets.set($placeholder, widget);
        } catch (e) {
            console.error('Failed to render Friendly Captcha:', e);
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

        const widget = this.widgets.get(this.$activePlaceholder);

        if (typeof widget === 'undefined') {
            console.warn('No widget ID found for the visible captcha placeholder.');
            return;
        }

        // Trigger captcha - unless we've already verified
        if (this.token) {
            // The user has verified manually, before pressing submit.
            this.onVerify(this.token);
        } else {
            widget.start();
        }
    }

    onVerify(token) {
        // Save the token in case we've clicked on the verification, and not the submit button
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

    onError(error) {
        console.error('Friendly Captcha was unable to load');
    }
}

window.FormieFriendlyCaptcha = FormieFriendlyCaptcha;
