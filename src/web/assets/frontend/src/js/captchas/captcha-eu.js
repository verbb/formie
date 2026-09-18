import { FormieCaptchaProvider } from './captcha-provider';
import { eventKey, ensureVariable } from '../utils/utils';

export class FormieCaptchaEu extends FormieCaptchaProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;
        this.publicKey = settings.publicKey;
        this.mode = settings.mode === 'hidden' ? 'hidden' : 'widget';
        this.scriptId = 'FORMIE_CAPTCHA_EU_SCRIPT';
        this.providerName = 'CaptchaEu';
    }

    getPlaceholders() {
        return this.$form.querySelectorAll('[data-captcha-eu-placeholder]');
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
            $script.src = 'https://w19.captcha.at/sdk.js';
            $script.async = true;
            $script.defer = true;

            // Wait until captcha-eu.js has loaded, then initialize
            $script.onload = () => {
                ensureVariable('KROT', 5000).then(() => {
                    this.renderCaptcha($placeholder);
                });
            };

            document.body.appendChild($script);
        } else {
            // Ensure that captcha-eu has been loaded and ready to use
            ensureVariable('KROT').then(() => {
                this.renderCaptcha($placeholder);
            });
        }
    }

    destroyCaptcha($placeholder) {
        this.form.removeEventListener(eventKey('onAfterFormieSubmit', this.providerName));
        this.form.removeEventListener(eventKey('onFormieSubmitError', this.providerName));

        // Reset the DOM for the placeholder, if it's been rendered
        this.destroyContainer($placeholder);
    }

    renderCaptcha($placeholder) {
        this.$activePlaceholder = $placeholder;

        // Prepare an inner element to render the captcha
        const $container = this.createContainer($placeholder);

        const $input = document.createElement('input');
        $input.setAttribute('type', 'hidden');
        $input.setAttribute('name', 'captcha-eu-token');
        $placeholder.appendChild($input);

        KROT.init();
        KROT.setup(this.publicKey);

        if (this.mode === 'hidden') {
            this.renderHiddenMode($placeholder, $container, $input);
        } else {
            this.renderWidgetMode($container, $input);
        }

        this.form.addEventListener(this.$form, eventKey('onAfterFormieSubmit', this.providerName), this.onAfterSubmit.bind(this));
        this.form.addEventListener(this.$form, eventKey('onFormieSubmitError', this.providerName), this.onSubmitError.bind(this));
    }

    renderWidgetMode($container, $input) {
        KROT.WidgetV2.render($container);

        KROT.on('CPT_OK', (e) => {
            $input.value = JSON.stringify(e.detail);
        }, $container);
    }

    renderHiddenMode($placeholder, $container, $input) {
        // No visible UI. Placeholder collapses; the challenge runs on
        // submit via KROT.getSolution(), populates the hidden field, then
        // continues the Formie submit flow via submitHandler.submitForm().
        // Pattern mirrors recaptcha-v3.js.
        $placeholder.style.display = 'none';
        this.$hiddenInput = $input;

        this.form.addEventListener(this.$form, eventKey('onFormieCaptchaValidate', this.providerName), this.onHiddenValidate.bind(this));
    }

    onHiddenValidate(e) {
        // When not using Formie's theme JS, nothing else prevents submit -
        // mirror recaptcha-v3.js.
        if (!this.$form.form.formTheme) {
            e.preventDefault();
            this.form.submitAction = this.$form.querySelector('[name="submitAction"]')?.value || 'submit';
        }

        // Skip on non-submit actions (previous page, save-for-later).
        if (this.form.submitAction !== 'submit') {
            return;
        }

        // Bail early if the form is already invalid.
        if (e.detail.invalid) {
            return;
        }

        // If a solution is already present (retry after error), re-use it.
        if (this.$hiddenInput && this.$hiddenInput.value) {
            return;
        }

        e.preventDefault();

        const submitHandler = e.detail.submitHandler;

        KROT.getSolution().then((solution) => {
            if (this.$hiddenInput) {
                this.$hiddenInput.value = JSON.stringify(solution);
            }
            if (submitHandler) {
                submitHandler.submitForm();
            }
        }).catch((err) => {
            console.error('[Formie CaptchaEu] KROT.getSolution failed:', err);
            // Continue the submit anyway; validateSubmission() rejects on
            // missing token and Formie surfaces its normal error, instead
            // of dead-locking the user on a click that produced no feedback.
            if (submitHandler) {
                submitHandler.submitForm();
            }
        });
    }

    onAfterSubmit() {
        this.refreshSinglePageCaptchaWidget();
    }

    onSubmitError() {
        this.refreshSinglePageCaptchaWidget();
    }
}

window.FormieCaptchaEu = FormieCaptchaEu;
