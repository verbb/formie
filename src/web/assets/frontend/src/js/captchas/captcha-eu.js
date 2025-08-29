import { FormieCaptchaProvider } from './captcha-provider';
import { eventKey, ensureVariable } from '../utils/utils';

export class FormieCaptchaEu extends FormieCaptchaProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;
        this.publicKey = settings.publicKey;
        this.scriptId = 'FORMIE_CAPTCHA_EU_SCRIPT';
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
        KROT.WidgetV2.render($container);

        KROT.on('CPT_OK', (e) => {
            $input.value = JSON.stringify(e.detail);
        }, $container);
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
}

window.FormieCaptchaEu = FormieCaptchaEu;
