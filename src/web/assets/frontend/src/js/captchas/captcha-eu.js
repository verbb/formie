import { FormieCaptchaProvider } from './captcha-provider';
import { eventKey, ensureVariable } from '../utils/utils';

export class FormieCaptchaEu extends FormieCaptchaProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;
        this.publicKey = settings.publicKey;
        this.captchaScriptId = 'FORMIE_CAPTCHA_EU_SCRIPT';

        // Fetch and attach the script only once - this is in case there are multiple forms on the page.
        // They all go to a single callback which resolves its loaded state
        if (!document.getElementById(this.captchaScriptId)) {
            const $script = document.createElement('script');
            $script.id = this.captchaScriptId;
            $script.src = 'https://w19.captcha.at/sdk.js';
            $script.async = true;
            $script.defer = true;

            // Wait until captcha-eu.js has loaded, then initialize
            $script.onload = () => {
                this.renderCaptcha();
            };

            document.body.appendChild($script);
        } else {
            // Ensure that captcha-eu has been loaded and ready to use
            ensureVariable('KROT').then(() => {
                this.renderCaptcha();
            });
        }

        // We can have multiple captchas per form, so store them and render only when we need
        this.$placeholders = this.$form.querySelectorAll('[data-captcha-eu-placeholder]');

        if (!this.$placeholders) {
            console.error('Unable to find any CaptchaEu placeholders for [data-captcha-eu-placeholder]');


        }
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
        }

        const $div = this.createInput();

        const $input = document.createElement('input');
        $input.setAttribute('type', 'hidden');
        $input.setAttribute('name', 'captcha-eu-token');
        this.$placeholder.appendChild($input);

        KROT.init();
        KROT.setup(this.publicKey);
        KROT.WidgetV2.render($div);

        KROT.on('CPT_OK', (e) => {
            console.log(e.detail);
            $input.value = JSON.stringify(e.detail);
        }, $div);
    }
}

window.FormieCaptchaEu = FormieCaptchaEu;
