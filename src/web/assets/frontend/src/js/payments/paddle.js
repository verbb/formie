import { t, eventKey, ensureVariable } from '../utils/utils';
import { FormiePaymentProvider } from './payment-provider';

export class FormiePaddle extends FormiePaymentProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;

        this.clientSideToken = settings.clientSideToken;
        this.environment = settings.environment;
        this.paddleScriptId = 'FORMIE_PADDLE_SCRIPT';

        // We can start listening for the field to become visible to initialize it
        this.initialized = true;
    }

    onShow() {
        // Initialize the field only when it's visible
        this.initField();
    }

    onHide() {
        this.boundEvents = false;

        // Field is hidden, so reset everything
        this.onAfterSubmit();

        // Remove unique event listeners
        this.form.removeEventListener(eventKey('FormiePaymentPaddleCheckout', 'paddle'));
        this.form.removeEventListener(eventKey('onAfterFormieSubmit', 'paddle'));
    }

    initField() {
        // Fetch and attach the script only once - this is in case there are multiple forms on the page.
        // They all go to a single callback which resolves its loaded state
        if (!document.getElementById(this.paddleScriptId)) {
            const $script = document.createElement('script');
            $script.id = this.paddleScriptId;
            $script.src = 'https://cdn.paddle.com/paddle/v2/paddle.js';

            $script.async = true;
            $script.defer = true;

            // Wait until JS has loaded, then initialize
            $script.onload = () => {
                this.renderButton();
            };

            document.body.appendChild($script);
        } else {
            // Ensure that PayPal has been loaded and ready to use
            ensureVariable('Paddle').then(() => {
                this.renderButton();
            });
        }

        // Attach custom event listeners on the form
        // Prevent binding multiple times. This can cause multiple payments!
        if (!this.boundEvents) {
            this.form.addEventListener(this.$form, eventKey('FormiePaymentPaddleCheckout', 'paddle'), this.onCheckout.bind(this));
            this.form.addEventListener(this.$form, eventKey('onAfterFormieSubmit', 'paddle'), this.onAfterSubmit.bind(this));

            this.boundEvents = true;
        }
    }

    renderButton() {
        Paddle.Environment.set(this.environment);

        const options = {
            token: this.clientSideToken,
            checkout: {
                settings: {
                    displayMode: 'overlay',
                    variant: 'multi-page',
                },
            },
            eventCallback: this.onCheckoutCallback.bind(this),
        };

        // Emit an "beforeInit" event. This can directly modify the `options` param
        const beforeInitEvent = new CustomEvent('beforeInit', {
            bubbles: true,
            detail: {
                paddle: this,
                options,
            },
        });

        this.$field.dispatchEvent(beforeInitEvent);

        Paddle.Initialize(options);
    }

    onCheckout(e) {
        const { data } = e.detail;

        if (!data || !data.items) {
            return this.addError('Missing Paddle checkout items.');
        }

        Paddle.Checkout.open(data);
    }

    onCheckoutCallback(e) {
        // Emit an event when approved
        const onCheckoutCallbackEvent = new CustomEvent('onCheckoutCallback', {
            bubbles: true,
            detail: {
                paddle: this,
                data: e,
            },
        });

        // Allow events to bail before our handling
        if (!this.$field.dispatchEvent(onCheckoutCallbackEvent)) {
            return;
        }

        if (e.name == 'checkout.completed') {
            this.updateInputs('paddleCheckoutInit', '');
            this.updateInputs('paddleCheckoutData', JSON.stringify(e.data));

            // Delay a little to let the user catch up
            setTimeout(() => {
                Paddle.Checkout.close();

                this.form.submitForm();
            }, 3000);
        }
    }

    onAfterSubmit(e) {
        // Reset all hidden inputs
        this.updateInputs('paddleCheckoutInit', 'true');
        this.updateInputs('paddleCheckoutData', '');
    }
}

window.FormiePaddle = FormiePaddle;
