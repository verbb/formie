import { defineCaptchaModule } from '#modules/captchas/api';
import { CAPTCHA_PROVIDER_LOAD_TIMEOUT_MS } from '#modules/captchas/constants';
import { loadScriptAndEnsureGlobal } from '#utils/scripts';

// Captcha.eu has its own widget/global shape, so keep the typed surface local
// to this provider rather than teaching shared captcha services about it.
type CaptchaEuGlobal = {
    init: () => void;
    setup: (publicKey: string) => void;
    WidgetV2: {
        render: (container: HTMLElement) => void;
    };
    on: (eventName: string, callback: (event: CustomEvent<unknown>) => void, container?: HTMLElement) => void;
};

// Only Captcha.eu-specific settings belong here.
type CaptchaEuProviderOptions = {
    publicKey?: string | null;
    endPoint?: string | null;
};

async function loadCaptchaEuGlobal(options: CaptchaEuProviderOptions): Promise<CaptchaEuGlobal> {
    // Captcha.eu can be self-hosted or pointed at a different endpoint, so the
    // SDK URL is derived from provider config rather than hardcoded globally.
    const baseUrl = String(options.endPoint || 'https://www.captcha.eu').trim().replace(/\/+$/, '');

    return loadScriptAndEnsureGlobal<CaptchaEuGlobal>('KROT', {
        id: 'FORMIE_CAPTCHA_EU_SCRIPT',
        src: `${baseUrl}/sdk.js`,
        async: true,
        defer: true,
        timeoutMs: CAPTCHA_PROVIDER_LOAD_TIMEOUT_MS,
    });
}

export const captchaEuModule = defineCaptchaModule<CaptchaEuProviderOptions, CaptchaEuGlobal, HTMLElement>({
    id: 'captcha-eu',
    defaultPlaceholderSelector: '[data-captcha-eu-placeholder]',
    defaultTokenFieldNames: ['captcha-eu-token'],
    load: ({ options }) => {
        return loadCaptchaEuGlobal(options.provider);
    },
    mount: ({ api, container, provider, services }) => {
        // Captcha.eu requires an init/setup/render sequence. That ordering is
        // provider-specific, so keep it here instead of abstracting it away.
        api.init();

        api.setup(String(provider.publicKey || ''));

        api.WidgetV2.render(container);

        api.on('CPT_OK', (event) => {
            // Captcha.eu emits a structured payload in the event detail rather
            // than a simple token string, so we serialize it into the shared
            // transport input the backend already knows how to validate.
            services.tokens.write(JSON.stringify(event.detail || {}), {
                container,
            });

            services.errors.clear();
        }, container);

        api.on('CPT_EXPIRED', () => {
            // Once the provider says the challenge has expired, remove the
            // serialized payload from the transport layer immediately.
            services.tokens.clear();
            services.errors.clear();
        }, container);

        return container;
    },
    screen: async ({ placeholder, services, stageCtx }) => {
        // Captcha.eu solves through its own widget lifecycle and callback. By
        // screen time we only need to wait for the shared transport layer to
        // contain the provider payload.
        const hasToken = await services.tokens.wait();

        if (!hasToken) {
            const message = services.errors.getDefaultMessage();

            services.errors.show(message, placeholder);

            stageCtx.abort(message);
        }
    },
});
