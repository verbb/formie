import {
    defineCaptchaModule,
} from '#modules/captchas/api';
import { CAPTCHA_EXECUTE_WAIT_FOR_VALUE_MS } from '#modules/captchas/constants';
import {
    loadRecaptchaGlobal,
    type RecaptchaGlobal,
    type RecaptchaProviderOptions,
} from '#modules/captchas/recaptcha-shared';

type RecaptchaWidgetState = {
    id: number | string;
};

async function waitForRecaptchaResponse(api: RecaptchaGlobal, widgetId: number | string, timeoutMs = 1000): Promise<string | undefined> {
    // Invisible reCAPTCHA does not always synchronously pass the final token
    // through the callback at the exact moment we need it. Polling `getResponse`
    // gives us a provider-local fallback without leaking Google-specific logic
    // into shared captcha services.
    const deadline = Date.now() + timeoutMs;

    while (Date.now() < deadline) {
        const response = typeof api.getResponse === 'function' ? api.getResponse(widgetId) : '';

        if (typeof response === 'string' && response.trim() !== '') {
            return response.trim();
        }

        await new Promise((resolve) => {
            window.setTimeout(resolve, 100);
        });
    }

    return undefined;
}

export const recaptchaV2InvisibleModule = defineCaptchaModule<RecaptchaProviderOptions, RecaptchaGlobal, RecaptchaWidgetState>({
    id: 'recaptcha-v2-invisible',
    defaultPlaceholderSelector: '[data-recaptcha-placeholder]',
    defaultTokenFieldNames: ['g-recaptcha-response'],
    load: ({ options }) => {
        return loadRecaptchaGlobal(options.provider);
    },
    mount: ({ api, container, provider, services }) => {
        return new Promise((resolve) => {
            api.ready(() => {
                const widgetId = api.render(container, {
                    sitekey: provider.siteKey || '',
                    badge: provider.badge || 'bottomright',
                    size: 'invisible',
                    callback: (token?: string) => {
                        // Google sometimes passes the token directly and other
                        // times expects consumers to read it back from the
                        // widget response API. Normalize both paths here.
                        const response = typeof token === 'string' && token.trim() !== ''
                            ? token.trim()
                            : (typeof api.getResponse === 'function' ? api.getResponse(widgetId) : '');

                        if (response) {
                            services.tokens.write(response);
                        }

                        services.errors.clear();
                    },
                    'expired-callback': () => {
                        services.tokens.clear();
                        services.errors.clear();
                    },
                    'error-callback': () => {
                        services.tokens.clear();
                    },
                });

                resolve({
                    // Keep the widget id in a tiny object so later lifecycle
                    // methods have a stable shape to work with.
                    id: widgetId,
                });
            });
        });
    },
    screen: async({ api, widget, placeholder, services, stageCtx }) => {
        // If we already have a token, do not re-execute the invisible widget.
        if (services.tokens.has()) {
            return;
        }

        // Invisible mode needs an explicit submit-time execute call.
        api.execute(widget.id);
        const token = await waitForRecaptchaResponse(api, widget.id);

        if (typeof token === 'string' && token.trim() !== '') {
            services.tokens.write(token.trim());
        }

        const hasToken = await services.tokens.wait(CAPTCHA_EXECUTE_WAIT_FOR_VALUE_MS);

        if (!hasToken) {
            const message = services.errors.getDefaultMessage();
            services.errors.show(message, placeholder);
            stageCtx.abort(message);
        }
    },
    unmount: ({ api, widget, services }) => {
        api.reset(widget.id);
        services.tokens.clear();
    },
});
